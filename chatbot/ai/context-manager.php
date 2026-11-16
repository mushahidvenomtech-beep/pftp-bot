<?php
/**
 * AI Context Manager & Conversation State Memory
 *
 * Persists and retrieves conversation context in MySQL (conversation_context table),
 * handles pronoun & anaphora resolution, and tracks user topic history.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class ContextManager
{
    private ?PDO $db;

    // The 16 canonical PFTP course names & aliases
    private const COURSE_CATALOG = [
        'Full Stack Web Development'    => ['full stack', 'web development', 'web dev', 'web designing', 'full stack web development', 'html css js', 'coding'],
        'Digital Marketing'             => ['digital marketing', 'digital media marketing', 'social media marketing', 'smm', 'meta ads', 'facebook ads', 'google ads'],
        'Artificial Intelligence (AI)'  => ['artificial intelligence', 'ai', 'generative ai', 'prompt engineering', 'chatgpt', 'ai tools'],
        'Graphic Design'                => ['graphic design', 'graphic designing', 'photoshop', 'illustrator', 'logo design'],
        'Video Editing'                 => ['video editing', 'premiere pro', 'capcut', 'reels editing', 'shorts editing'],
        'YouTube Automation'            => ['youtube automation', 'faceless youtube', 'faceless channel', 'youtube channel'],
        'Content Writing'               => ['content writing', 'copywriting', 'blog writing', 'article writing'],
        'Search Engine Optimization (SEO)' => ['seo', 'search engine optimization', 'google ranking', 'on-page seo', 'backlinks'],
        'Cyber Security'                => ['cyber security', 'ethical hacking', 'network security', 'penetration testing'],
        'Data Science'                  => ['data science', 'data analysis', 'data analytics', 'power bi'],
        'Python Programming'            => ['python', 'python programming', 'python coding'],
        'E-Commerce'                    => ['e-commerce', 'ecommerce', 'shopify', 'daraz', 'online store', 'dropshipping'],
        'QuickBooks'                    => ['quickbooks', 'bookkeeping', 'accounting software'],
        'English Language'              => ['english language', 'spoken english', 'english speaking', 'communication skills'],
        'IELTS Preparation'             => ['ielts', 'ielts preparation', 'study abroad english'],
        'Freelancing'                   => ['freelancing', 'fiverr', 'upwork', 'freelance']
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
    }

    public function getDb(): ?PDO
    {
        return $this->db;
    }

    /**
     * Load existing context from database or session storage
     */
    public function loadContext(int $conversationId): array
    {
        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("
                    SELECT current_topic, last_mentioned_course, user_education_status,
                           last_intent, pending_clarification, context_data
                    FROM conversation_context
                    WHERE conversation_id = :cid
                    LIMIT 1
                ");
                $stmt->execute([':cid' => $conversationId]);
                $row = $stmt->fetch();

                if ($row) {
                    $contextData = !empty($row['context_data']) ? json_decode($row['context_data'], true) : [];
                    return [
                        'current_topic'          => $row['current_topic'],
                        'last_mentioned_course'  => $row['last_mentioned_course'],
                        'user_education_status'  => $row['user_education_status'],
                        'last_intent'            => $row['last_intent'],
                        'pending_clarification'  => $row['pending_clarification'],
                        'recent_entities'        => $contextData['recent_entities'] ?? [],
                        'discussed_courses'      => $contextData['discussed_courses'] ?? []
                    ];
                }
            } catch (Throwable $e) {
                error_log('[ContextManager Load Error] ' . $e->getMessage());
            }
        } elseif (isset($_SESSION['pftp_context'][$conversationId])) {
            return $_SESSION['pftp_context'][$conversationId];
        }

        return [
            'current_topic'          => null,
            'last_mentioned_course'  => null,
            'user_education_status'  => null,
            'last_intent'            => null,
            'pending_clarification'  => null,
            'recent_entities'        => [],
            'discussed_courses'      => []
        ];
    }

    /**
     * Save updated context state to database or session storage
     */
    public function saveContext(int $conversationId, array $context): void
    {
        if ($this->db !== null) {
            try {
                $contextData = json_encode([
                    'recent_entities'   => array_slice($context['recent_entities'] ?? [], -8),
                    'discussed_courses' => array_values(array_unique($context['discussed_courses'] ?? []))
                ], JSON_UNESCAPED_UNICODE);

                $stmt = $this->db->prepare("
                    INSERT INTO conversation_context
                    (conversation_id, current_topic, last_mentioned_course, user_education_status,
                     last_intent, pending_clarification, context_data, updated_at)
                    VALUES
                    (:cid, :topic, :course, :edu, :intent, :clar, :cdata, NOW())
                    ON DUPLICATE KEY UPDATE
                        current_topic = VALUES(current_topic),
                        last_mentioned_course = VALUES(last_mentioned_course),
                        user_education_status = VALUES(user_education_status),
                        last_intent = VALUES(last_intent),
                        pending_clarification = VALUES(pending_clarification),
                        context_data = VALUES(context_data),
                        updated_at = NOW()
                ");

                $stmt->execute([
                    ':cid'    => $conversationId,
                    ':topic'  => $context['current_topic'] ?? null,
                    ':course' => $context['last_mentioned_course'] ?? null,
                    ':edu'    => $context['user_education_status'] ?? null,
                    ':intent' => $context['last_intent'] ?? null,
                    ':clar'   => $context['pending_clarification'] ?? null,
                    ':cdata'  => $contextData
                ]);
            } catch (Throwable $e) {
                error_log('[ContextManager Save Error] ' . $e->getMessage());
            }
        } else {
            $_SESSION['pftp_context'][$conversationId] = $context;
        }
    }

    /**
     * Detect course entity mentioned in message
     */
    public function extractCourseMention(string $message): ?string
    {
        $clean = mb_strtolower($message, 'UTF-8');

        // Check longest alias first to prevent shorter partial overlaps
        $candidates = [];
        foreach (self::COURSE_CATALOG as $canonicalName => $aliases) {
            foreach ($aliases as $alias) {
                $candidates[] = [
                    'canonical' => $canonicalName,
                    'alias'     => $alias,
                    'len'       => mb_strlen($alias)
                ];
            }
        }

        // Sort descending by length
        usort($candidates, fn($a, $b) => $b['len'] <=> $a['len']);

        foreach ($candidates as $cand) {
            $alias = $cand['alias'];
            // Safe Latin word boundary
            $pattern = '/(?<![a-zA-Z0-9])' . preg_quote($alias, '/') . '(?![a-zA-Z0-9])/iu';
            if (preg_match($pattern, $clean)) {
                return $cand['canonical'];
            }
        }

        // Urdu script check
        $urduMap = [
            'ڈیجیٹل مارکیٹنگ' => 'Digital Marketing',
            'ویب ڈویلپمنٹ'   => 'Full Stack Web Development',
            'گرافک ڈیزائننگ'  => 'Graphic Design',
            'گرافک ڈیزائن'   => 'Graphic Design',
            'ویڈیو ایڈیٹنگ'   => 'Video Editing',
            'فری لانسنگ'     => 'Freelancing',
            'آرٹیفیشل انٹیلیجنس' => 'Artificial Intelligence (AI)',
            'سائبر سیکیورٹی' => 'Cyber Security',
            'پائتھن'        => 'Python Programming',
            'ایس ای او'      => 'Search Engine Optimization (SEO)',
            'ڈیٹا سائنس'     => 'Data Science',
            'ای کامرس'      => 'E-Commerce',
            'انگلش لینگویج'  => 'English Language',
            'آئلٹس'         => 'IELTS Preparation'
        ];

        foreach ($urduMap as $urduPhrase => $canonical) {
            if (mb_strpos($clean, $urduPhrase) !== false) {
                return $canonical;
            }
        }

        // Fuzzy typo matching for course keywords
        $tokens = preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean));
        $anchors = [
            'python'       => 'Python Programming',
            'freelancing'  => 'Freelancing',
            'photoshop'    => 'Graphic Design',
            'marketing'    => 'Digital Marketing',
            'editing'      => 'Video Editing',
            'security'     => 'Cyber Security',
            'quickbooks'   => 'QuickBooks',
            'ecommerce'    => 'E-Commerce',
            'shopify'      => 'E-Commerce',
            'automation'   => 'YouTube Automation',
            'intelligence' => 'Artificial Intelligence (AI)',
            'development'  => 'Full Stack Web Development',
            'analytics'    => 'Data Science'
        ];

        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 5) {
                foreach ($anchors as $anchor => $canonical) {
                    $dist = levenshtein($token, $anchor);
                    $maxLen = max(mb_strlen($token), mb_strlen($anchor));
                    $sim = 1.0 - ($dist / $maxLen);
                    if ($dist <= 2 && $sim >= 0.72) {
                        return $canonical;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve pronouns and relative references using conversation memory
     * e.g., "what is its duration?", "uski fee kitni hai?"
     *
     * @return array [resolved_query, was_resolved, needs_clarification]
     */
    public function resolvePronouns(string $rawMessage, array $context): array
    {
        $clean = trim($rawMessage);
        $lower = mb_strtolower($clean, 'UTF-8');

        // Pronoun and relative reference triggers in English and Roman Urdu
        $pronounPatterns = [
            '/\b(its|it|this|that|the course)\b/i',
            '/\b(uski|iski|uska|iska|is course ki|iss course ka|ye course|woh course|is ka|us ka)\b/i',
            '/اس کی|اس کورس کا|اس کورس کی/u'
        ];

        $hasPronoun = false;
        foreach ($pronounPatterns as $pat) {
            if (preg_match($pat, $lower)) {
                $hasPronoun = true;
                break;
            }
        }

        // Implicit incomplete queries like "duration?", "duration kitni hai?", "what about fee?"
        $implicitFollowUp = preg_match('/^(what about|duration|fee|timing|schedule|classes|kitne mahine|fees kitni hai|kahan apply karein)[\s\?\.]*$/iu', $lower);

        if ($hasPronoun || $implicitFollowUp) {
            $lastCourse = $context['last_mentioned_course'] ?? null;
            $discussedCourses = $context['discussed_courses'] ?? [];

            // Case A: Last course is known with high confidence
            if (!empty($lastCourse)) {
                $resolved = $clean . " for {$lastCourse}";
                return [
                    'query'               => $resolved,
                    'was_resolved'        => true,
                    'needs_clarification' => false,
                    'course'              => $lastCourse
                ];
            }

            // Case B: Multiple courses were discussed and reference is ambiguous
            if (count($discussedCourses) > 1) {
                return [
                    'query'               => $clean,
                    'was_resolved'        => false,
                    'needs_clarification' => true,
                    'clarification_prompt' => 'Which course are you inquiring about? You recently discussed ' . implode(' and ', array_slice($discussedCourses, -2)) . '.'
                ];
            }

            // Case C: No course has been mentioned yet
            if (empty($lastCourse)) {
                return [
                    'query'               => $clean,
                    'was_resolved'        => false,
                    'needs_clarification' => true,
                    'clarification_prompt' => 'Which course are you referring to? We offer 16 courses, including Web Development, AI, Digital Marketing, Graphic Design, and Freelancing.'
                ];
            }
        }

        return [
            'query'               => $clean,
            'was_resolved'        => false,
            'needs_clarification' => false,
            'course'              => null
        ];
    }

    /**
     * Retrieve recent message history for context window
     */
    public function getRecentHistory(int $conversationId, int $limit = 6): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT sender_type, message, created_at
                FROM messages
                WHERE conversation_id = :cid
                ORDER BY id DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return array_reverse($stmt->fetchAll());
        } catch (Throwable $e) {
            error_log('[ContextManager History Error] ' . $e->getMessage());
            return [];
        }
    }
}
