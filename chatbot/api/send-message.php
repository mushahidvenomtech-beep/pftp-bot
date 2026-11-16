<?php
/**
 * API: Send Visitor Message & Receive Intelligent Answer
 * Endpoint: /chatbot/api/send-message.php
 * Method: POST
 *
 * Orchestrates incoming messages through the Intelligent Query Understanding
 * & Self-Analysis Engine (QueryAnalyzer -> KnowledgeRetriever -> ResponseGenerator).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/static_provider.php';
require_once __DIR__ . '/../ai/ai-client.php';

setupApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed. Only POST requests are accepted.', 405);
}

$payload = getRequestPayload();

// Retrieve session token from header or body
$sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? ($payload['session_token'] ?? '');
if (!validateSessionToken($sessionToken)) {
    jsonError('Invalid or missing session token. Please register or reload the chat.', 401);
}

// Validate message
$messageRaw = (string) ($payload['message'] ?? '');
$msgVal = validateMessageText($messageRaw);
if (!$msgVal['valid']) {
    jsonError($msgVal['error'], 422, ['field' => 'message']);
}

// Cloud / Offline Static Fallback Mode (Runs if MySQL is not connected)
if (!isDBConnected()) {
    $conversation = getStaticConversationByToken($sessionToken);
    if (!$conversation) {
        jsonError('Conversation session not found or expired. Please re-enter your details.', 404);
    }

    $convId = (int) $conversation['id'];
    $visitorId = (int) $conversation['visitor_id'];

    // 1. Record visitor message
    $userMsg = addStaticMessage($sessionToken, 'visitor', $msgVal['value']);

    // 2. Process AI Pipeline in static mode
    $aiClient = new AiClient(null);
    $visitorName = $conversation['full_name'] ?? null;
    $aiResult = $aiClient->processMessage($msgVal['value'], $convId, $visitorId, $visitorName);

    // 3. Record bot message
    $botMsg = addStaticMessage(
        $sessionToken,
        'bot',
        $aiResult['message'],
        $aiResult['matched_kb_id'],
        (bool) $aiResult['is_fallback']
    );

    jsonSuccess('Message processed successfully.', [
        'user_message' => [
            'id'         => $userMsg['id'],
            'message'    => $msgVal['value'],
            'created_at' => $userMsg['created_at']
        ],
        'bot_response' => [
            'id'               => $botMsg['id'],
            'message'          => $aiResult['message'],
            'response_type'    => $aiResult['response_type'] ?? 'direct_answer',
            'matched_kb_id'    => $aiResult['matched_kb_id'],
            'is_fallback'      => $aiResult['is_fallback'] ? 1 : 0,
            'possible_careers' => $aiResult['possible_careers'],
            'category'         => $aiResult['category'],
            'confidence'       => $aiResult['confidence'],
            'created_at'       => $botMsg['created_at']
        ],
        'bot_message' => [
            'id'               => $botMsg['id'],
            'message'          => $aiResult['message'],
            'matched_kb_id'    => $aiResult['matched_kb_id'],
            'is_fallback'      => $aiResult['is_fallback'] ? 1 : 0,
            'possible_careers' => $aiResult['possible_careers'],
            'category'         => $aiResult['category'],
            'confidence'       => $aiResult['confidence'],
            'created_at'       => $botMsg['created_at']
        ]
    ]);
}

try {
    $db = getDBConnection();

    // Verify conversation
    $conversation = getConversationByToken($db, $sessionToken);
    if (!$conversation) {
        jsonError('Conversation session not found or expired. Please re-enter your details.', 404);
    }

    $convId = (int) $conversation['conversation_id'];
    $visitorId = (int) $conversation['visitor_id'];

    if ($conversation['conversation_status'] === 'closed') {
        jsonError('This conversation has been closed by administration.', 403);
    }

    // Rate Limiting
    $rateLimitSetting = (int) (getChatbotSetting('rate_limit_per_minute', '12') ?: 12);
    if (!checkRateLimit($db, $convId, $rateLimitSetting)) {
        jsonError('You are sending messages too quickly. Please pause for a moment before sending another query.', 429);
    }

    // 1. Insert Visitor Message
    $visitorMsgStmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_type, message, created_at)
        VALUES (:cid, 'visitor', :msg, NOW())
    ");
    $visitorMsgStmt->execute([
        ':cid' => $convId,
        ':msg' => $msgVal['value']
    ]);
    $visitorMsgId = (int) $db->lastInsertId();

    // 2. Execute Intelligent AI Query Understanding & Self-Analysis Pipeline
    $aiClient = new AiClient($db);
    $visitorName = $conversation['full_name'] ?? null;
    $aiResult = $aiClient->processMessage($msgVal['value'], $convId, $visitorId, $visitorName);

    // 3. Insert Bot Response Message
    $botMsgStmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_type, message, matched_kb_id, is_fallback, created_at)
        VALUES (:cid, 'bot', :msg, :kb_id, :is_fallback, NOW())
    ");
    $botMsgStmt->execute([
        ':cid'         => $convId,
        ':msg'         => $aiResult['message'],
        ':kb_id'       => $aiResult['matched_kb_id'],
        ':is_fallback' => $aiResult['is_fallback'] ? 1 : 0
    ]);
    $botMsgId = (int) $db->lastInsertId();

    // Touch conversation updated_at
    $touchStmt = $db->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = :id");
    $touchStmt->execute([':id' => $convId]);

    // Return final response (Internal reasoning/analysis is never exposed to visitors)
    jsonSuccess('Message processed successfully.', [
        'user_message' => [
            'id'         => $visitorMsgId,
            'message'    => $msgVal['value'],
            'created_at' => date('Y-m-d H:i:s')
        ],
        'bot_response' => [
            'id'               => $botMsgId,
            'message'          => $aiResult['message'],
            'response_type'    => $aiResult['response_type'] ?? 'direct_answer',
            'is_fallback'      => $aiResult['is_fallback'],
            'category'         => $aiResult['category'],
            'possible_careers' => $aiResult['possible_careers'],
            'confidence'       => $aiResult['confidence'],
            'created_at'       => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Throwable $e) {
    error_log('[Send Message API Error] ' . $e->getMessage());
    jsonError('An internal server error occurred while retrieving answer. Please try again.', 500);
}
