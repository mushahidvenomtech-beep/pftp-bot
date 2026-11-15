<?php
/**
 * AI Knowledge Retriever & Fact Grounding Engine
 *
 * Implements multi-tier verified knowledge retrieval against the MySQL knowledge base,
 * evaluating exact match, variations, TF-keyword overlap, entity-scoping, and typo tolerance.
 * Enforces zero-fabrication grounding standards.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class KnowledgeRetriever
{
    private PDO $db;
    private float $acceptanceThreshold;

    public function __construct(PDO $db, float $acceptanceThreshold = 22.0)
    {
        $this->db = $db;
        $this->acceptanceThreshold = $acceptanceThreshold;
    }

    /**
     * Retrieve best matching verified knowledge item for analyzed query
     *
     * @param array $analysis Structured output from QueryAnalyzer
     * @param int|null $conversationId
     * @param int|null $visitorId
     * @return array Grounded retrieval result
     */
    public function retrieve(array $analysis, ?int $conversationId = null, ?int $visitorId = null): array
    {
        // If analysis flagged manipulation attempt or unrelated query, do not query KB
        if (!empty($analysis['is_manipulation']) || ($analysis['primary_intent'] ?? '') === 'unrelated_query') {
            return [
                'found'            => false,
                'matched_kb_id'    => null,
                'category'         => 'Unrelated',
                'question'         => null,
                'answer'           => null,
                'possible_careers' => null,
                'language'         => $analysis['language'],
                'confidence'       => 0.0,
                'raw_score'        => 0.0,
                'conflict'         => false
            ];
        }

        $searchQuery = $analysis['search_query'] ?? $analysis['raw_query'];
        $userLanguage = $analysis['language'] ?? 'en';
        $normalizedInput = $this->normalizeText($searchQuery);
        $userTokens = $this->tokenize($normalizedInput);

        // Fetch all active KB records
        $stmt = $this->db->prepare("
            SELECT id, category, language, question, question_variations, answer, keywords, possible_careers
            FROM knowledge_base
            WHERE status = 'active'
        ");
        $stmt->execute();
        $records = $stmt->fetchAll();

        $bestScore = 0.0;
        $secondBestScore = 0.0;
        $bestRecord = null;

        foreach ($records as $record) {
            $score = $this->calculateRecordScore($normalizedInput, $userTokens, $record, $userLanguage, $analysis);

            if ($score > $bestScore) {
                $secondBestScore = $bestScore;
                $bestScore = $score;
                $bestRecord = $record;
            } elseif ($score > $secondBestScore) {
                $secondBestScore = $score;
            }
        }

        // Check for potential conflict if top two records have very close scores on different categories
        $hasConflict = false;
        if ($bestScore >= $this->acceptanceThreshold && ($bestScore - $secondBestScore) < 2.0 && $secondBestScore > 25.0) {
            $hasConflict = true;
        }

        if ($bestRecord !== null && $bestScore >= $this->acceptanceThreshold) {
            // Increment view counter
            $this->incrementViews((int) $bestRecord['id']);

            $normalizedConfidence = round(min(1.0, $bestScore / 70.0), 2);

            return [
                'found'            => true,
                'matched_kb_id'    => (int) $bestRecord['id'],
                'category'         => $bestRecord['category'],
                'question'         => $bestRecord['question'],
                'answer'           => $bestRecord['answer'],
                'possible_careers' => !empty($bestRecord['possible_careers']) ? $bestRecord['possible_careers'] : null,
                'language'         => $bestRecord['language'],
                'confidence'       => $normalizedConfidence,
                'raw_score'        => $bestScore,
                'conflict'         => $hasConflict
            ];
        }

        // Fallback: log unanswered query for administrator review
        $this->logUnansweredQuery($analysis['raw_query'], $conversationId, $visitorId);

        return [
            'found'            => false,
            'matched_kb_id'    => null,
            'category'         => 'Unverified',
            'question'         => null,
            'answer'           => null,
            'possible_careers' => null,
            'language'         => $userLanguage,
            'confidence'       => round(min(1.0, $bestScore / 70.0), 2),
            'raw_score'        => $bestScore,
            'conflict'         => false
        ];
    }

    /**
     * Calculate score between user input and KB record
     */
    private function calculateRecordScore(string $input, array $tokens, array $record, string $detectedLang, array $analysis): float
    {
        $score = 0.0;

        $normQuestion = $this->normalizeText($record['question']);
        $normVariations = !empty($record['question_variations'])
            ? explode('|', $this->normalizeText($record['question_variations']))
            : [];

        // 1. Exact match on question (+100)
        if ($input === $normQuestion) {
            return 100.0;
        }

        // 2. Exact match on variations (+95)
        foreach ($normVariations as $variation) {
            $cleanVar = trim($variation);
            if ($cleanVar !== '' && $input === $cleanVar) {
                return 95.0;
            }
        }

        // 3. Substring containment (+45)
        if (mb_strlen($input) > 8 && mb_strpos($normQuestion, $input) !== false) {
            $score += 45.0;
        }
        foreach ($normVariations as $variation) {
            $cleanVar = trim($variation);
            if ($cleanVar !== '' && mb_strlen($cleanVar) > 8 && (mb_strpos($cleanVar, $input) !== false || mb_strpos($input, $cleanVar) !== false)) {
                $score += 40.0;
                break;
            }
        }

        // 4. Token & Keyword Match with TF weighting
        $kbText = $normQuestion . ' ' . implode(' ', $normVariations);
        if (!empty($record['keywords'])) {
            $kbText .= ' ' . $this->normalizeText($record['keywords']);
        }

        $matchedTokens = 0;
        foreach ($tokens as $token) {
            if (mb_strlen($token) <= 2) {
                continue;
            }

            if (mb_strpos($kbText, $token) !== false) {
                $matchedTokens++;
                $score += (mb_strlen($token) > 5) ? 8.0 : 5.0;
            } else {
                // Typo / Fuzzy tolerance: check Levenshtein on longer words (>= 5 chars)
                if (mb_strlen($token) >= 5) {
                    $kbWords = preg_split('/\s+/u', $kbText);
                    foreach ($kbWords as $kw) {
                        if (mb_strlen($kw) >= 5) {
                            $dist = levenshtein($token, $kw);
                            $maxLen = max(mb_strlen($token), mb_strlen($kw));
                            $sim = 1.0 - ($dist / $maxLen);

                            if ($dist <= 2 && $sim >= 0.72) {
                                $matchedTokens++;
                                $score += 6.0;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Density bonus
        if (count($tokens) > 0) {
            $ratio = $matchedTokens / count($tokens);
            $score += ($ratio * 25.0);
        }

        // If no tokens matched and no substring/exact match occurred, record has 0 relevance
        if ($score === 0.0 || $matchedTokens === 0) {
            return 0.0;
        }

        // Require at least 2 token matches for longer queries (or 1 match for 1-token query)
        // to prevent a single accidental match from dominating scoring
        if (count($tokens) >= 3 && $matchedTokens < 2 && mb_strlen($input) > 15) {
            return 0.0;
        }

        // 5. Entity & Topic Affinity Bonus (+20)
        if (!empty($analysis['topic']) && mb_strtolower($record['category']) === mb_strtolower($analysis['topic'])) {
            $score += 20.0;
        }

        // 6. Language Affinity Bonus
        if ($record['language'] === $detectedLang) {
            $score += 20.0;
        } elseif ($detectedLang === 'ur' && $record['language'] !== 'ur') {
            $score -= 30.0;
        } elseif ($detectedLang === 'roman_ur' && $record['language'] === 'en') {
            $score -= 10.0;
        } elseif ($detectedLang === 'en' && $record['language'] === 'roman_ur') {
            $score -= 10.0;
        }

        return $score;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[?!.,;:|\\/()\-]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    private function tokenize(string $text): array
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = [
            'is', 'the', 'a', 'an', 'and', 'to', 'in', 'of', 'for', 'on', 'with', 'at', 'by',
            'what', 'which', 'how', 'where', 'who', 'when', 'why', 'tell', 'me', 'about',
            'can', 'could', 'would', 'should', 'do', 'does', 'did', 'please', 'i', 'you',
            'we', 'they', 'it', 'this', 'that', 'there', 'here',
            'ki', 'ka', 'ke', 'ko', 'se', 'par', 'mein', 'hai', 'hain', 'k', 'my', 'me',
            'batao', 'batayein', 'kya', 'kia', 'konsa', 'konsay', 'kaise', 'kese',
            'کا', 'کی', 'کے', 'کو', 'سے', 'پر', 'میں', 'ہے', 'ہیں', 'کیا', 'کونسا', 'کیسے'
        ];

        return array_values(array_filter($words, function ($w) use ($stopWords) {
            return !in_array($w, $stopWords, true);
        }));
    }

    private function incrementViews(int $kbId): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE knowledge_base SET views_count = views_count + 1 WHERE id = :id");
            $stmt->execute([':id' => $kbId]);
        } catch (Throwable $e) {
            error_log('[KB Views Increment Error] ' . $e->getMessage());
        }
    }

    private function logUnansweredQuery(string $query, ?int $convId, ?int $visitorId): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO unanswered_queries (conversation_id, visitor_id, query_text, status, created_at)
                VALUES (:cid, :vid, :query, 'pending', NOW())
            ");
            $stmt->execute([
                ':cid'   => $convId,
                ':vid'   => $visitorId,
                ':query' => mb_substr(trim($query), 0, 1000)
            ]);
        } catch (Throwable $e) {
            error_log('[Unanswered Query Log Error] ' . $e->getMessage());
        }
    }
}
