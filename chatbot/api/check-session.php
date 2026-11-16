<?php
/**
 * API: Check Session & Status Provider
 * Endpoint: /chatbot/api/check-session.php
 * Method: GET
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/static_provider.php';

setupApiHeaders();

$sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? ($_GET['session_token'] ?? '');

$botSettings = [
    'bot_name'        => getChatbotSetting('bot_name', 'PFTP Course Advisor'),
    'bot_title'       => getChatbotSetting('bot_title', 'Student Counselling & Admissions'),
    'bot_status'      => getChatbotSetting('bot_status', 'online'),
    'theme_color'     => getChatbotSetting('theme_color', '#07613F'),
    'welcome_message' => getChatbotSetting('welcome_message', 'Hello! 👋 Welcome to PFTP (Professional Freelancing Training Program). I am your Student Advisor. How can I help you?'),
    'office_hours'    => getChatbotSetting('office_hours', '9:00 AM - 6:00 PM'),
    'support_phone'   => getChatbotSetting('support_phone', '+92 300 1234567'),
    'support_email'   => getChatbotSetting('support_email', 'counselling@pftp.com.pk'),
    'disclaimer_text' => getChatbotSetting('disclaimer_text', 'PFTP provides skill training. Outcomes and jobs are not guaranteed.')
];

$quickReplies = [
    ['label' => '🇬🇧 English Roadmap', 'query' => 'What is the complete 12-week roadmap for course completion and admission?'],
    ['label' => '🇵🇰 Roman Urdu Roadmap', 'query' => 'Course completion ka mukammal roadmap kya hai aur dakhla kaise milega?'],
    ['label' => '🇵🇰 اردو داخلہ روڈ میپ', 'query' => 'کورس کی تکمیل کا مکمل 12 ہفتوں کا روڈ میپ کیا ہے اور داخلہ کیسے ہوگا؟'],
    ['label' => '🎓 Course Suggestion', 'query' => 'I have completed intermediate / ICS. Which course is best suited for my study details?'],
    ['label' => '📝 How to Apply', 'query' => 'Where should I go to apply for admission?'],
    ['label' => '💳 Fee & How to Pay', 'query' => 'How do I pay the admission processing fee?'],
    ['label' => '📚 All 16 Courses', 'query' => 'What courses are offered at PFTP?'],
    ['label' => '💻 Web Development', 'query' => 'Why should I take the Full Stack Web Development course?']
];

if (empty($sessionToken) || !validateSessionToken($sessionToken)) {
    jsonSuccess('No active session provided.', [
        'is_authenticated' => false,
        'settings'         => $botSettings,
        'quick_replies'    => $quickReplies
    ]);
}

// Cloud / Offline Static Fallback Mode (Runs if MySQL is not connected)
if (!isDBConnected()) {
    $conv = getStaticConversationByToken($sessionToken);
    if (!$conv) {
        jsonSuccess('Session expired or not found.', [
            'is_authenticated' => false,
            'settings'         => $botSettings,
            'quick_replies'    => $quickReplies
        ]);
    }

    jsonSuccess('Active session verified.', [
        'is_authenticated' => true,
        'settings'         => $botSettings,
        'quick_replies'    => $quickReplies,
        'visitor'          => [
            'id'        => (int) ($conv['visitor']['id'] ?? $conv['visitor_id']),
            'full_name' => $conv['visitor']['full_name'] ?? $conv['full_name'],
            'email'     => $conv['visitor']['email'] ?? $conv['email'],
            'phone'     => $conv['visitor']['phone'] ?? null
        ],
        'conversation'     => [
            'id'         => (int) $conv['id'],
            'status'     => $conv['status'],
            'created_at' => $conv['created_at']
        ],
        'messages'         => $conv['messages'] ?? []
    ]);
}

try {
    $db = getDBConnection();
    $conv = getConversationByToken($db, $sessionToken);

    if (!$conv) {
        jsonSuccess('Session expired or not found.', [
            'is_authenticated' => false,
            'settings'         => $botSettings,
            'quick_replies'    => $quickReplies
        ]);
    }

    $convId = (int) $conv['conversation_id'];

    // Load recent messages
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

    jsonSuccess('Active session verified.', [
        'is_authenticated' => true,
        'settings'         => $botSettings,
        'quick_replies'    => $quickReplies,
        'visitor'          => [
            'id'        => (int) $conv['visitor_id'],
            'full_name' => $conv['full_name'],
            'email'     => $conv['email'],
            'phone'     => $conv['phone']
        ],
        'conversation'     => [
            'id'     => $convId,
            'status' => $conv['conversation_status']
        ],
        'messages'         => $messages
    ]);

} catch (Throwable $e) {
    error_log('[Check Session Error] ' . $e->getMessage());
    jsonError('Failed to verify session.', 500);
}
