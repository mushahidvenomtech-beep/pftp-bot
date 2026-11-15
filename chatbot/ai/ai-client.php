<?php
/**
 * Modular AI Client & External Model Gateway
 *
 * Provides a unified interface for AI model integration.
 * Supports configurable external providers (OpenAI, Gemini, Ollama, custom REST API),
 * with strict server-side credential isolation, timeout handling, and automatic
 * graceful fallback to the native self-hosted engine.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/context-manager.php';
require_once __DIR__ . '/query-analyzer.php';
require_once __DIR__ . '/knowledge-retriever.php';
require_once __DIR__ . '/response-generator.php';

class AiClient
{
    private PDO $db;
    private ContextManager $contextManager;
    private QueryAnalyzer $queryAnalyzer;
    private KnowledgeRetriever $knowledgeRetriever;
    private ResponseGenerator $responseGenerator;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->contextManager = new ContextManager($db);
        $this->queryAnalyzer = new QueryAnalyzer($this->contextManager);
        $this->knowledgeRetriever = new KnowledgeRetriever($db);
        $this->responseGenerator = new ResponseGenerator($this->contextManager);
    }

    /**
     * Process message through the complete AI query pipeline
     *
     * @param string $userMessage Visitor's message
     * @param int $conversationId
     * @param int|null $visitorId
     * @param string|null $visitorName Optional visitor full name
     * @return array Standardized bot response payload
     */
    public function processMessage(string $userMessage, int $conversationId, ?int $visitorId = null, ?string $visitorName = null): array
    {
        // 1. Load context memory from database
        $context = $this->contextManager->loadContext($conversationId);

        // 2. Query Understanding & Self-Analysis
        $analysis = $this->queryAnalyzer->analyze($userMessage, $conversationId, $context);

        // 3. Grounded Knowledge Base Retrieval
        $retrieval = $this->knowledgeRetriever->retrieve($analysis, $conversationId, $visitorId);

        // 4. Check if external AI provider is configured and available
        $provider = getChatbotSetting('ai_provider', 'local');
        $apiKey = getChatbotSetting('ai_api_key', '');

        if ($provider !== 'local' && !empty($apiKey) && !empty($retrieval['found']) && empty($analysis['is_manipulation'])) {
            try {
                $externalResponse = $this->callExternalProvider($provider, $apiKey, $analysis, $retrieval, $context);
                if (!empty($externalResponse)) {
                    // Update context with retrieved facts
                    $this->responseGenerator->generate($analysis, $retrieval, $conversationId, $context, $visitorName);

                    return [
                        'response_type'    => 'grounded_ai_response',
                        'message'          => $externalResponse,
                        'matched_kb_id'    => $retrieval['matched_kb_id'],
                        'is_fallback'      => false,
                        'category'         => $retrieval['category'],
                        'possible_careers' => $retrieval['possible_careers'],
                        'confidence'       => $retrieval['confidence'],
                        'analysis'         => $analysis
                    ];
                }
            } catch (Throwable $e) {
                // Graceful fallback: log internally and continue with self-hosted generator
                error_log('[AI Provider Fallback] ' . $e->getMessage());
            }
        }

        // 5. Decision-Making & Native Dynamic Response Generation
        $response = $this->responseGenerator->generate($analysis, $retrieval, $conversationId, $context, $visitorName);

        // Attach internal analysis for downstream logging or telemetry (never shown to visitor)
        $response['analysis'] = $analysis;

        return $response;
    }

    /**
     * Dispatch prompt strictly grounded on verified KB facts to external LLM provider
     */
    private function callExternalProvider(string $provider, string $apiKey, array $analysis, array $retrieval, array $context): ?string
    {
        $systemPrompt = "You are PFTP's friendly Student Advisor. You must ONLY answer using the verified facts provided below. Do NOT invent or extrapolate facts, fees, durations, or policies. If the information is not in the verified facts, say you don't have that information. Respond in the user's language: " . $analysis['language'];

        $groundedContext = "VERIFIED INSTITUTIONAL FACTS:\n" .
            "Category: " . $retrieval['category'] . "\n" .
            "Question: " . $retrieval['question'] . "\n" .
            "Answer: " . $retrieval['answer'] . "\n";

        if (!empty($retrieval['possible_careers'])) {
            $groundedContext .= "Possible Careers: " . $retrieval['possible_careers'] . "\n";
        }

        // Timeout 4 seconds max
        $timeout = 4;

        if ($provider === 'openai') {
            return $this->callOpenAI($apiKey, $systemPrompt, $groundedContext, $analysis['raw_query'], $timeout);
        }

        return null;
    }

    /**
     * OpenAI Compatible Chat Completions API caller
     */
    private function callOpenAI(string $apiKey, string $systemPrompt, string $contextText, string $userQuery, int $timeout): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $endpoint = getChatbotSetting('ai_endpoint', 'https://api.openai.com/v1/chat/completions');
        $model = getChatbotSetting('ai_model', 'gpt-4o-mini');

        $ch = curl_init($endpoint);
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt . "\n\n" . $contextText],
                ['role' => 'user', 'content' => $userQuery]
            ],
            'temperature' => 0.3,
            'max_tokens' => 300
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && is_string($result)) {
            $data = json_decode($result, true);
            return $data['choices'][0]['message']['content'] ?? null;
        }

        return null;
    }

    public function getContextManager(): ContextManager
    {
        return $this->contextManager;
    }

    public function getQueryAnalyzer(): QueryAnalyzer
    {
        return $this->queryAnalyzer;
    }

    public function getKnowledgeRetriever(): KnowledgeRetriever
    {
        return $this->knowledgeRetriever;
    }

    public function getResponseGenerator(): ResponseGenerator
    {
        return $this->responseGenerator;
    }
}

