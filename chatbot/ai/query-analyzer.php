<?php
/**
 * AI Query Understanding & Self-Analysis Engine
 *
 * Internally analyzes visitor messages to understand underlying intent,
 * detects language, resolves pronouns/follow-ups using context memory,
 * flags manipulation attempts, and outputs a validated structured analysis.
 */

declare(strict_types=1);

require_once __DIR__ . '/context-manager.php';
require_once __DIR__ . '/intent-detector.php';

class QueryAnalyzer
{
    private ContextManager $contextManager;
    private IntentDetector $intentDetector;

    public function __construct(ContextManager $contextManager, ?IntentDetector $intentDetector = null)
    {
        $this->contextManager = $contextManager;
        $this->intentDetector = $intentDetector ?? new IntentDetector();
    }

    /**
     * Analyze user query and return validated structured analysis array
     *
     * @param string $rawQuery
     * @param int $conversationId
     * @param array $context
     * @return array Structured analysis conforming to Section 4 requirements
     */
    public function analyze(string $rawQuery, int $conversationId, array $context): array
    {
        $clean = trim($rawQuery);
        $language = $this->detectLanguage($clean);
        $normalized = $this->normalizeText($clean);

        // 1. Check for prompt injection / manipulation attempts
        $isManipulation = $this->intentDetector->detectManipulation($clean);
        if ($isManipulation) {
            $analysis = [
                'raw_query'            => $clean,
                'normalized_query'     => $normalized,
                'resolved_query'       => $clean,
                'intent'               => 'manipulation_attempt',
                'primary_intent'       => 'manipulation_attempt',
                'secondary_intent'     => null,
                'topic'                => 'security_defense',
                'language'             => $language,
                'entities'             => [],
                'is_follow_up'         => false,
                'needs_clarification'  => false,
                'clarification_prompt' => null,
                'search_query'         => $clean,
                'confidence'           => 1.0,
                'is_manipulation'      => true
            ];
            $this->validateAnalysisStructure($analysis);
            return $analysis;
        }

        // 2. Pronoun & Anaphora resolution using conversation context
        $pronounResolution = $this->contextManager->resolvePronouns($clean, $context);
        $resolvedQuery = $pronounResolution['query'];
        $wasResolved = $pronounResolution['was_resolved'];
        $needsClarification = $pronounResolution['needs_clarification'];
        $clarificationPrompt = $pronounResolution['clarification_prompt'] ?? null;

        // 3. Detect intent and entities
        $intentResult = $this->intentDetector->detectIntent($resolvedQuery, $language, $context);
        $entities = $this->intentDetector->extractEntities($resolvedQuery);

        // If a course was extracted or resolved, track it in entities
        if (!empty($pronounResolution['course']) && empty($entities['course'])) {
            $entities['course'] = $pronounResolution['course'];
        }

        // 4. Determine topic / category
        $topic = $this->determineTopic($intentResult['primary'], $entities, $context);

        // 5. Determine whether query is a follow-up
        $isFollowUp = $wasResolved
            || !empty($context['last_mentioned_course'])
            || !empty($context['current_topic'])
            || in_array($intentResult['primary'], ['course_duration', 'fee_inquiry', 'certificate_inquiry', 'course_details']);

        // 6. Build optimized search query for knowledge retrieval
        $searchQuery = $this->buildSearchQuery($clean, $resolvedQuery, $entities, $topic);

        $analysis = [
            'raw_query'            => $clean,
            'normalized_query'     => $normalized,
            'resolved_query'       => $resolvedQuery,
            'intent'               => $intentResult['primary'],
            'primary_intent'       => $intentResult['primary'],
            'secondary_intent'     => $intentResult['secondary'],
            'topic'                => $topic,
            'language'             => $language,
            'entities'             => array_values($entities),
            'is_follow_up'         => $isFollowUp,
            'needs_clarification'  => $needsClarification,
            'clarification_prompt' => $clarificationPrompt,
            'search_query'         => $searchQuery,
            'confidence'           => $intentResult['confidence'],
            'is_manipulation'      => false,
            'greeting_type'        => $intentResult['greeting_type'] ?? null
        ];

        // 7. Validate JSON structure
        $this->validateAnalysisStructure($analysis);

        return $analysis;
    }

    /**
     * Detect input language: 'ur' (Urdu script), 'roman_ur' (Roman Urdu), or 'en' (English)
     */
    public function detectLanguage(string $text): string
    {
        // 1. Urdu script Unicode block check
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'ur';
        }

        // 2. Roman Urdu markers (excluding ambiguous English words like 'or')
        $romanUrduMarkers = [
            'kyun', 'karna', 'chahiye', 'kaise', 'kese', 'mujhe', 'mujhy', 'konsa', 'konsay',
            'kon', 'sy', 'se', 'batao', 'batayein', 'batain', 'hain', 'hein', 'kya', 'kia',
            'meri', 'mera', 'apka', 'apki', 'karo', 'sakta', 'sakti', 'hoga', 'hogi', 'milega',
            'kitni', 'kitny', 'kitne', 'parhna', 'seekhna', 'sikhaty', 'sikhate', 'apne',
            'karein', 'zaroorat', 'chahte', 'bary', 'baare', 'mein', 'my', 'krna', 'parhai',
            'chor', 'chhor', 'mukammal', 'khatam', 'dakhla', 'pohanch', 'dhoondein',
            'tum', 'aap', 'ho', 'ab', 'bhi', 'kisi', 'kuch', 'hum', 'pehle'
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
     * Normalize text for consistent comparisons
     */
    public function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Replace multiple punctuation / spaces
        $text = preg_replace('/[?!.,;:]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Map intent and entities to institutional topic
     */
    private function determineTopic(string $intent, array $entities, array $context): string
    {
        if ($intent === 'alternative_course_recommendations') {
            return 'Alternative Course Recommendations';
        }

        if ($intent === 'course_execution_and_completion_process') {
            return 'Course Execution, Training & Completion Process';
        }

        if ($intent === 'course_execution_and_completion_process') {
            return 'Course Execution, Training & Completion Process';
        }

        if ($intent === 'freelancing_and_online_earning_guidance') {
            return 'Learning Freelancing and Online Earning';
        }

        if ($intent === 'physical_classes_inquiry') {
            return 'Physical Classes / On-Campus Training';
        }

        if (!empty($entities['course'])) {
            return $entities['course'];
        }

        switch ($intent) {
            case 'alternative_course_recommendations':
                return 'Alternative Course Recommendations';
            case 'course_execution_and_completion_process':
                return 'Course Execution, Training & Completion Process';
            case 'course_execution_and_completion_process':
                return 'Course Execution, Training & Completion Process';
            case 'freelancing_and_online_earning_guidance':
                return 'Learning Freelancing and Online Earning';
            case 'physical_classes_inquiry':
                return 'Physical Classes / On-Campus Training';
            case 'course_inquiry':
                return 'Courses Overview';
            case 'admission_process':
                return 'Admission Process';
            case 'payment_instructions':
            case 'fee_inquiry':
                return !empty($context['last_mentioned_course']) ? $context['last_mentioned_course'] : 'Fees & Payments';
            case 'company_overview':
                return 'About PFTP';
            case 'education_status':
                return 'Student Discovery';
            case 'policy_job_guarantee':
                return 'Policies & Disclaimers';
            case 'greeting':
                return 'Greeting';
            case 'human_handoff':
                return 'Human Assistance';
            default:
                return $context['current_topic'] ?? 'General';
        }
    }

    /**
     * Build optimized search query for KB matching
     */
    private function buildSearchQuery(string $raw, string $resolved, array $entities, string $topic): string
    {
        if (!empty($entities['course']) && mb_stripos($raw, $entities['course']) === false) {
            return $raw . ' ' . $entities['course'];
        }

        return $resolved;
    }

    /**
     * Validates that the structured analysis contains all required fields and correct types
     *
     * @throws RuntimeException if schema validation fails
     */
    public function validateAnalysisStructure(array $analysis): void
    {
        $requiredKeys = [
            'raw_query'            => 'string',
            'normalized_query'     => 'string',
            'resolved_query'       => 'string',
            'intent'               => 'string',
            'topic'                => 'string',
            'language'             => 'string',
            'entities'             => 'array',
            'is_follow_up'         => 'boolean',
            'needs_clarification'  => 'boolean',
            'search_query'         => 'string',
            'confidence'           => ['double', 'integer']
        ];

        foreach ($requiredKeys as $key => $expectedType) {
            if (!array_key_exists($key, $analysis)) {
                throw new RuntimeException("Query analysis schema error: missing required key '{$key}'");
            }

            $type = gettype($analysis[$key]);
            if (is_array($expectedType)) {
                if (!in_array($type, $expectedType, true)) {
                    throw new RuntimeException("Query analysis schema error: key '{$key}' expected types " . implode('/', $expectedType) . ", got {$type}");
                }
            } elseif ($type !== $expectedType) {
                throw new RuntimeException("Query analysis schema error: key '{$key}' expected type {$expectedType}, got {$type}");
            }
        }
    }
}
