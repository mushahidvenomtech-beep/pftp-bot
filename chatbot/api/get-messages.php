<?php
/**
 * API: Retrieve Conversation Message History
 * Endpoint: /chatbot/api/get-messages.php
 * Method: GET
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/session.php';

setupApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed. Only GET requests are accepted.', 405);
}

$sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? ($_GET['session_token'] ?? '');
if (!validateSessionToken($sessionToken)) {
    jsonError('Invalid or missing session token.', 401);
}

try {
    $db = getDBConnection();
    $conv = getConversationByToken($db, $sessionToken);

    if (!$conv) {
        jsonError('Conversation not found or expired.', 404);
    }

    $convId = (int) $conv['conversation_id'];

    $stmt = $db->prepare("
        SELECT
            m.id,
            m.sender_type,
            m.message,
            m.is_fallback,
            m.created_at,
            kb.category,
            kb.possible_careers
        FROM messages m
        LEFT JOIN knowledge_base kb ON m.matched_kb_id = kb.id
        WHERE m.conversation_id = :cid
        ORDER BY m.id ASC
    ");
    $stmt->execute([':cid' => $convId]);
    $messages = $stmt->fetchAll();

    jsonSuccess('Messages loaded.', [
        'conversation' => [
            'id'         => $convId,
            'status'     => $conv['conversation_status'],
            'created_at' => $conv['conversation_created_at']
        ],
        'visitor' => [
            'full_name'  => $conv['full_name'],
            'email'      => $conv['email']
        ],
        'messages' => $messages
    ]);

} catch (Throwable $e) {
    error_log('[Get Messages API Error] ' . $e->getMessage());
    jsonError('An internal server error occurred while retrieving messages.', 500);
}

