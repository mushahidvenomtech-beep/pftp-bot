<?php
/**
 * Test: Full Muhammad Bilal chat loading and continuous messaging flow without database
 */

// Simulate Vercel environment
putenv('VERCEL=1');
$_SERVER['VERCEL'] = '1';

require_once __DIR__ . '/../chatbot/config/database.php';
require_once __DIR__ . '/../chatbot/includes/static_provider.php';
require_once __DIR__ . '/../chatbot/ai/ai-client.php';

echo "=== 1. Testing DB is offline detection on serverless ===\n";
$isDb = isDBConnected();
echo "isDBConnected: " . ($isDb ? "TRUE (unexpected)" : "FALSE (expected)") . "\n";

echo "\n=== 2. Testing Registration for Muhammad Bilal ===\n";
// Case A: exact email
$resA = findOrCreateStaticVisitor('Muhammad Bilal', 'bilal.student@example.com');
echo "Case A (exact): visitor=" . $resA['visitor']['full_name'] . ", is_existing=" . ($resA['is_existing'] ? 'YES' : 'NO') . "\n";

$convA = getOrCreateStaticConversation((int)$resA['visitor']['id'], !$resA['is_existing']);
echo "Case A: token=" . $convA['session_token'] . ", initial messages count=" . count($convA['messages']) . "\n";

// Case B: different email, name has Bilal
$resB = findOrCreateStaticVisitor('Muhammad Bilal', 'bilal.new@gmail.com');
echo "Case B (bilal email variant): visitor=" . $resB['visitor']['full_name'] . ", is_existing=" . ($resB['is_existing'] ? 'YES' : 'NO') . "\n";

echo "\n=== 3. Testing Continuous Messaging on Muhammad Bilal Conversation ===\n";
$token = $convA['session_token'];
$testQueries = [
    'Tell me about Python course',
    'Where should I go to apply for admission?',
    'How do I pay the admission processing fee?',
    'داخلے کے لیے مجھے ویب سائٹ پر کہاں جانا ہوگا؟'
];

$aiClient = new AiClient(null);

foreach ($testQueries as $query) {
    echo "\n>> User: " . $query . "\n";
    $conv = getStaticConversationByToken($token);
    if (!$conv) {
        echo "ERROR: Conv not found for token $token\n";
        exit(1);
    }
    
    // Add user message
    $userMsg = addStaticMessage($token, 'visitor', $query);
    
    // AI processing
    $aiResult = $aiClient->processMessage($query, (int)$conv['id'], (int)$conv['visitor_id'], $conv['full_name']);
    
    // Add bot message
    $botMsg = addStaticMessage($token, 'bot', $aiResult['message'], $aiResult['matched_kb_id'], (bool)$aiResult['is_fallback']);
    
    echo "<< Bot (KB #" . ($aiResult['matched_kb_id'] ?? 'none') . ", conf=" . $aiResult['confidence'] . "):\n";
    echo "   " . substr($aiResult['message'], 0, 120) . "...\n";
    if (!empty($aiResult['possible_careers'])) {
        echo "   Careers: " . $aiResult['possible_careers'] . "\n";
    }
}

$convFinal = getStaticConversationByToken($token);
echo "\n=== 4. Final Conversation State ===\n";
echo "Total messages now in conversation: " . count($convFinal['messages']) . " (started with 9, added 4 user + 4 bot = 17)\n";

if (count($convFinal['messages']) === 17) {
    echo "SUCCESS: All 17 messages present, continuous chat working smoothly!\n";
} else {
    echo "WARNING: Expected 17 messages, got " . count($convFinal['messages']) . "\n";
}
