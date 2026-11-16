<?php
/**
 * Dynamic Question-Answer Matching & NLP Engine
 *
 * Implements:
 * 1. Exact & case-insensitive matching
 * 2. Question variations matching
 * 3. Multi-word keyword scoring with TF-weighting
 * 4. Linguistic language detection (English, Urdu script, Roman Urdu)
 * 5. Typo & fuzzy distance matching (Levenshtein with length & similarity ratio guards)
 * 6. Intent shortcuts (greetings, course menu, human handoff)
 * 7. Logging of unmatched/fallback queries for admin knowledge expansion
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class MatchingEngine
{
    private ?PDO $db;
    private string $fallbackResponse;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
        $this->fallbackResponse = getChatbotSetting(
            'fallback_response',
            "I'm sorry, I couldn't find an accurate answer to that question. Please rephrase your question or leave your query so our team can assist you."
        );
    }

    /**
     * Find best matching answer for a user's question
     */
    public function findAnswer(string $userInput, ?int $conversationId = null, ?int $visitorId = null): array
    {
        $cleanInput = trim($userInput);
        if ($cleanInput === '') {
            return [
                'answer'           => $this->fallbackResponse,
                'matched_kb_id'    => null,
                'is_fallback'      => true,
                'possible_careers' => null,
                'category'         => 'Fallback',
                'confidence'       => 0.0
            ];
        }

        // 1. Detect language / script
        $detectedLang = $this->detectLanguage($cleanInput);
        $normalizedInput = $this->normalizeText($cleanInput);

        // 2. Intent shortcuts: Greetings
        $greetingMatch = $this->checkGreetingIntent($normalizedInput, $detectedLang);
        if ($greetingMatch !== null) {
            return $greetingMatch;
        }

        // 3. Fetch all active KB records for scoring
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("
                    SELECT id, category, language, question, question_variations, answer, keywords, possible_careers
                    FROM knowledge_base
                    WHERE status = 'active'
                ");
                $stmt->execute();
                $records = $stmt->fetchAll();
            } catch (Throwable $e) {
                require_once __DIR__ . '/static_provider.php';
                $records = getStaticKBRecords();
            }
        } else {
            require_once __DIR__ . '/static_provider.php';
            $records = getStaticKBRecords();
        }

        $bestScore = 0.0;
        $bestRecord = null;

        $userTokens = $this->tokenize($normalizedInput);

        foreach ($records as $record) {
            $score = $this->calculateMatchScore($normalizedInput, $userTokens, $record, $detectedLang);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRecord = $record;
            }
        }

        // Strict acceptance threshold
        $threshold = 22.0;

        if ($bestRecord !== null && $bestScore >= $threshold) {
            // Update views count for the matched KB item
            if ($this->db !== null) {
                $this->incrementViews((int) $bestRecord['id']);
            }

            return [
                'answer'           => $bestRecord['answer'],
                'matched_kb_id'    => (int) $bestRecord['id'],
                'is_fallback'      => false,
                'possible_careers' => !empty($bestRecord['possible_careers']) ? $bestRecord['possible_careers'] : null,
                'category'         => $bestRecord['category'],
                'confidence'       => round(min(1.0, $bestScore / 70.0), 2)
            ];
        }

        // 4. Fallback: Log unanswered question for admin review
        if ($this->db !== null) {
            $this->logUnansweredQuery($userInput, $conversationId, $visitorId);
        }

        return [
            'answer'           => $this->fallbackResponse,
            'matched_kb_id'    => null,
            'is_fallback'      => true,
            'possible_careers' => null,
            'category'         => 'Fallback',
            'confidence'       => round(min(1.0, $bestScore / 70.0), 2)
        ];
    }

    /**
     * Detect input language: 'ur' (Urdu script), 'roman_ur' (Roman Urdu), or 'en' (English)
     */
    public function detectLanguage(string $text): string
    {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ur';
        }

        $romanUrduMarkers = [
            'kyun', 'karna', 'chahiye', 'kaise', 'kese', 'mujhe', 'mujhy', 'konsa', 'konsay',
            'batao', 'bataen', 'batayein', 'hain', 'hein', 'kya', 'meri', 'mera', 'mere',
            'karo', 'sakta', 'sakti', 'sakte', 'hoga', 'hogi', 'honge', 'milega', 'kitni', 'kitna',
            'parhna', 'seekhna', 'sikhna', 'sikhaty', 'sikhte', 'apne', 'aur', 'karein',
            'zaroorat', 'chahte', 'main', 'mein', 'ne', 'hai', 'hoon', 'hun', 'ki', 'ka', 'ke',
            'ko', 'se', 'par', 'pe', 'kaun', 'koun', 'sa', 'si', 'bhi', 'shauq', 'rahega', 'rahegi',
            'raha', 'rahe', 'rahi', 'kahan', 'kab', 'kis', 'kisi', 'liye', 'liya', 'kiya'
        ];

        $lower = mb_strtolower($text, 'UTF-8');
        $words = preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lower));

        $markerCount = 0;
        foreach ($words as $w) {
            if (in_array($w, $romanUrduMarkers, true)) {
                $markerCount++;
            }
        }

        return ($markerCount >= 1) ? 'roman_ur' : 'en';
    }

    /**
     * Calculate similarity score between input and a KB record
     */
    public function calculateMatchScore(string $input, array $tokens, array $record, string $detectedLang): float
    {
        $score = 0.0;

        $normQuestion = $this->normalizeText($record['question']);
        $normVariations = !empty($record['question_variations'])
            ? explode('|', $this->normalizeText($record['question_variations']))
            : [];
        $normKeywords = !empty($record['keywords'])
            ? array_map('trim', explode(',', $this->normalizeText($record['keywords'])))
            : [];
        $normCategory = $this->normalizeText($record['category']);

        $isLangMatch = ($record['language'] === $detectedLang);
        $langBonus = $isLangMatch ? 20.0 : -10.0;

        // A. Exact Question Match (Top Priority)
        if ($input === $normQuestion) {
            return 150.0 + $langBonus;
        }

        // B. Exact Variation Match
        foreach ($normVariations as $var) {
            $var = trim($var);
            if ($var !== '' && $input === $var) {
                return 130.0 + $langBonus;
            }
            if ($var !== '' && mb_strlen($input) > 5 && strpos($var, $input) !== false) {
                $score += 25.0;
            }
        }

        // C. Direct Category Match
        if ($input === $normCategory || (mb_strlen($input) > 3 && (strpos($input, $normCategory) !== false || strpos($normCategory, $input) !== false))) {
            $score += 30.0;
        }

        // C2. Category token overlap (boost when specific category keywords like engineering, medical, etc. match)
        $catTokens = array_diff($this->tokenize($normCategory), ['counselling', 'overview', 'general', 'process', 'track']);
        $commonCat = array_intersect($tokens, $catTokens);
        if (!empty($commonCat)) {
            $score += (count($commonCat) * 12.0);
        }

        // D. Token Overlap with Question
        $qTokens = $this->tokenize($normQuestion);
        $commonQ = array_intersect($tokens, $qTokens);
        $score += (count($commonQ) * 8.0);

        // E. Keyword Overlap
        foreach ($normKeywords as $kw) {
            if ($kw === '') continue;
            if ($input === $kw) {
                $score += 28.0;
            } elseif (mb_strlen($kw) > 4 && strpos($input, $kw) !== false) {
                $score += 18.0;
            } else {
                $kwTokens = $this->tokenize($kw);
                $commonKw = array_intersect($tokens, $kwTokens);
                $score += (count($commonKw) * 6.0);
            }
        }

        // F. Fuzzy / Typo tolerance on category words (for misspellings where lev > 0)
        $catWords = $this->tokenize($normCategory);
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 5) continue;
            foreach ($catWords as $cw) {
                if (mb_strlen($cw) < 5) continue;
                $lev = levenshtein($token, $cw);
                $maxLen = max(mb_strlen($token), mb_strlen($cw));
                $sim = 1 - ($lev / $maxLen);

                if ($lev > 0 && $lev <= 2 && $sim >= 0.72) {
                    $score += (20.0 - ($lev * 5.0));
                }
            }
        }

        // Add language affinity bonus only if there was a real semantic match
        if ($score >= 12.0) {
            $score += $langBonus;
        }

        return $score;
    }

    /**
     * Check if user typed a greeting or pleasantry
     */
    private function checkGreetingIntent(string $normInput, string $detectedLang): ?array
    {
        $greetingsEn = ['hi', 'hello', 'hey', 'good morning', 'good evening', 'good afternoon', 'how are you'];
        $greetingsUr = ['salam', 'assalam o alaikum', 'assalam alaikum', 'aoa', 'kese ho', 'kaise ho', 'kia hal he', 'kya haal hai'];

        if (in_array($normInput, $greetingsEn, true) || in_array($normInput, $greetingsUr, true) || $normInput === 'سلام' || $normInput === 'السلام علیکم') {
            $botName = getChatbotSetting('bot_name', 'PFTP Course Advisor');
            $greetingMsg = ($detectedLang === 'ur')
                ? "وعلیکم السلام! میں {$botName} ہوں۔ میں آپ کو بہترین کورس کے انتخاب اور رہنمائی میں مدد کے لیے حاضر ہوں۔ آپ کس کورس یا فیلڈ کے بارے میں جاننا چاہتے ہیں؟"
                : (($detectedLang === 'roman_ur')
                    ? "Walaikum Assalam! Main {$botName} hoon. Main aapki course selection aur admissions counselling mein madad kar sakta hoon. Aap kis field ya course mein interest rakhte hain?"
                    : "Hello and welcome! 👋 I am the {$botName}. How can I assist you today? You can ask about any of our 16 courses, such as Full Stack Web Development, AI, Digital Marketing, Graphic Design, or Freelancing!");

            return [
                'answer'           => $greetingMsg,
                'matched_kb_id'    => null,
                'is_fallback'      => false,
                'possible_careers' => null,
                'category'         => 'Greeting',
                'confidence'       => 1.0
            ];
        }

        return null;
    }

    /**
     * Normalize text for uniform comparison
     */
    public function normalizeText(string $text): string
    {
        // Collapse acronyms with internal dots (e.g. B.Com -> BCom, I.Com -> ICom, F.Sc -> FSc)
        $text = preg_replace('/(?<=[a-zA-Z])\.(?=[a-zA-Z])/u', '', $text);
        $text = mb_strtolower($text, 'UTF-8');
        // Replace punctuation with spaces
        $text = preg_replace('/[?!.,;:\-_=\+\(\)\[\]\{\}\/\\\"\'`~@#\$%\^&\*]/u', ' ', $text);
        // Replace multiple spaces with a single space
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Split text into tokens and filter out common stopwords
     */
    public function tokenize(string $text): array
    {
        $words = explode(' ', $text);
        $stopwords = [
            'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'she', 'it', 'they',
            'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are',
            'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until', 'while',
            'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through',
            'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in',
            'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once',
            // Common Roman Urdu stop words
            'ka', 'ki', 'ke', 'ko', 'koee', 'koi', 'kuch', 'aur', 'par', 'pe', 'se', 'mein',
            'kya', 'kyun', 'kese', 'kaise', 'batao', 'chahiye', 'karna', 'karo', 'mujhe', 'hum',
            'main', 'ne', 'hai', 'hain', 'hein', 'hoon', 'hun', 'kaun', 'konsa', 'konsay', 'koun',
            'sa', 'si', 'liye', 'mere', 'meri', 'mera', 'kiya', 'rahega', 'rahegi', 'kahan', 'kab', 'kis'
        ];

        $tokens = [];
        foreach ($words as $w) {
            $w = trim($w);
            if ($w !== '' && mb_strlen($w) > 1 && !in_array($w, $stopwords, true)) {
                $tokens[] = $w;
            }
        }

        return array_unique($tokens);
    }

    /**
     * Record unmatched question into unanswered_queries
     */
    private function logUnansweredQuery(string $query, ?int $conversationId, ?int $visitorId): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO unanswered_queries (conversation_id, visitor_id, query_text, status)
                VALUES (:cid, :vid, :query, 'pending')
            ");
            $stmt->execute([
                ':cid'   => $conversationId,
                ':vid'   => $visitorId,
                ':query' => mb_substr(trim($query), 0, 1000)
            ]);
        } catch (Throwable $e) {
            error_log('[Log Unanswered Error] ' . $e->getMessage());
        }
    }

    /**
     * Increment views_count for KB item
     */
    private function incrementViews(int $kbId): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE knowledge_base SET views_count = views_count + 1 WHERE id = :id");
            $stmt->execute([':id' => $kbId]);
        } catch (Throwable $e) {
            // Silently ignore counter update failures
        }
    }
}
