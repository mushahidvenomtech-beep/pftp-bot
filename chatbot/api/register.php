<?php
/**
 * API: Visitor Registration & Conversation Initiation
 * Endpoint: /chatbot/api/register.php
 * Method: POST
 *
 * Implements:
 * - Checks if visitor's email and name already exist in database.
 * - If already exists: restores and returns their previous conversation and message history.
 * - If does not exist: creates a new visitor record and opens a fresh blank conversation window.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/session.php';

setupApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed. Only POST requests are accepted.', 405);
}

$payload = getRequestPayload();

$fullNameRaw = (string) ($payload['full_name'] ?? '');
$emailRaw    = (string) ($payload['email'] ?? '');
$phoneRaw    = isset($payload['phone']) ? (string) $payload['phone'] : null;

// Validate Full Name
$nameVal = validateFullName($fullNameRaw);
if (!$nameVal['valid']) {
    jsonError($nameVal['error'], 422, ['field' => 'full_name']);
}

// Validate Email
$emailVal = validateEmail($emailRaw);
if (!$emailVal['valid']) {
    jsonError($emailVal['error'], 422, ['field' => 'email']);
}

$phoneClean = $phoneRaw !== null ? sanitizeString($phoneRaw, 30) : null;
$cleanEmail = mb_strtolower(trim($emailVal['value']));
$cleanName  = trim($nameVal['value']);

// Quick replies for easy student navigation
$quickReplies = [
    ['label' => '📝 How to Apply', 'query' => 'Where should I go to apply for admission?'],
    ['label' => '💳 Fee & How to Pay', 'query' => 'How do I pay the admission processing fee?'],
    ['label' => '📚 All 16 Courses', 'query' => 'What courses are offered at PFTP?'],
    ['label' => '💻 Web Development', 'query' => 'Why should I take the Full Stack Web Development course?'],
    ['label' => '🤖 Artificial Intelligence', 'query' => 'Why should I take the Artificial Intelligence (AI) course?'],
    ['label' => '📈 Digital Marketing', 'query' => 'Why should I take the Digital Marketing course?'],
    ['label' => '💼 Freelancing', 'query' => 'Why should I take the Freelancing course?'],
    ['label' => '🇵🇰 داخلہ کیسے لیں؟', 'query' => 'داخلے کے لیے مجھے ویب سائٹ پر کہاں جانا ہوگا؟']
];

try {
    $db = getDBConnection();

    // Check if visitor with this email already exists
    $stmt = $db->prepare("
        SELECT id, full_name, email, phone
        FROM visitors
        WHERE LOWER(email) = LOWER(:email)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':email' => $cleanEmail]);
    $existingVisitor = $stmt->fetch();

    $isExistingVisitor = false;
    $visitorId = null;

    if ($existingVisitor) {
        $dbName = mb_strtolower(trim($existingVisitor['full_name']));
        $inputName = mb_strtolower($cleanName);
        // Match if names are equal or share a significant substring
        if ($dbName === $inputName || mb_stripos($dbName, $inputName) !== false || mb_stripos($inputName, $dbName) !== false) {
            $isExistingVisitor = true;
            $visitorId = (int) $existingVisitor['id'];
        }
    }

    // SCENARIO 1: Visitor Email & Name ALREADY EXIST -> Restore Old Conversation
    if ($isExistingVisitor && $visitorId !== null) {
        $convStmt = $db->prepare("
            SELECT id, session_token, status, created_at
            FROM conversations
            WHERE visitor_id = :vid
            ORDER BY id DESC
            LIMIT 1
        ");
        $convStmt->execute([':vid' => $visitorId]);
        $existingConv = $convStmt->fetch();

        if ($existingConv) {
            $convId = (int) $existingConv['id'];
            $sessionToken = $existingConv['session_token'];

            // If conversation was closed by admin, reactivate so visitor can continue
            if ($existingConv['status'] === 'closed') {
                $reopenStmt = $db->prepare("UPDATE conversations SET status = 'active', updated_at = NOW() WHERE id = :id");
                $reopenStmt->execute([':id' => $convId]);
            }

            // Fetch previous message history
            $msgStmt = $db->prepare("
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
            $msgStmt->execute([':cid' => $convId]);
            $messages = $msgStmt->fetchAll();

            jsonSuccess('Existing conversation restored.', [
                'conversation_id'     => $convId,
                'session_token'       => $sessionToken,
                'visitor'             => [
                    'id'        => $visitorId,
                    'full_name' => $existingVisitor['full_name'],
                    'email'     => $existingVisitor['email']
                ],
                'is_existing_visitor' => true,
                'is_new'              => false,
                'messages'            => $messages,
                'quick_replies'       => $quickReplies
            ]);
        }
    }

    // SCENARIO 2: Visitor Does NOT Exist -> Create New & Show Blank Window
    $insertVisitor = $db->prepare("
        INSERT INTO visitors (full_name, email, phone, ip_address, user_agent, created_at)
        VALUES (:full_name, :email, :phone, :ip, :ua, NOW())
    ");
    $insertVisitor->execute([
        ':full_name' => $cleanName,
        ':email'     => $cleanEmail,
        ':phone'     => $phoneClean,
        ':ip'        => getClientIp(),
        ':ua'        => sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? '', 250)
    ]);
    $newVisitorId = (int) $db->lastInsertId();

    $newToken = generateSessionToken();
    $convInsert = $db->prepare("
        INSERT INTO conversations (visitor_id, session_token, status, created_at, updated_at)
        VALUES (:vid, :token, 'active', NOW(), NOW())
    ");
    $convInsert->execute([
        ':vid'   => $newVisitorId,
        ':token' => $newToken
    ]);
    $newConvId = (int) $db->lastInsertId();

    // Seed initial greeting message
    $welcomeText = getChatbotSetting(
        'welcome_message',
        "Hello! 👋 Welcome to PFTP (Professional Freelancing Training Program). I am your Student Advisor. How can I help you?"
    );

    $welcomeStmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_type, message, created_at)
        VALUES (:cid, 'bot', :msg, NOW())
    ");
    $welcomeStmt->execute([
        ':cid' => $newConvId,
        ':msg' => $welcomeText
    ]);
    $welcomeMsgId = (int) $db->lastInsertId();

    jsonSuccess('New conversation started.', [
        'conversation_id'     => $newConvId,
        'session_token'       => $newToken,
        'visitor'             => [
            'id'        => $newVisitorId,
            'full_name' => $cleanName,
            'email'     => $cleanEmail
        ],
        'is_existing_visitor' => false,
        'is_new'              => true,
        'messages'            => [
            [
                'id'               => $welcomeMsgId,
                'sender_type'      => 'bot',
                'message'          => $welcomeText,
                'is_fallback'      => 0,
                'created_at'       => date('Y-m-d H:i:s'),
                'category'         => 'Welcome',
                'possible_careers' => null
            ]
        ],
        'quick_replies'       => $quickReplies
    ]);

} catch (Throwable $e) {
    error_log('[Registration API Error] ' . $e->getMessage());
    jsonError('An internal server error occurred while processing registration. Please try again.', 500);
}
