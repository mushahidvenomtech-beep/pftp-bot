<?php
/**
 * Automated Verification: Standalone Static & Offline Fallback Mode
 * Verifies that the entire chatbot, AI Q&A matching, visitor conversation restoration,
 * and admin authentication function completely even without a connected database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../chatbot/config/database.php';
require_once __DIR__ . '/../chatbot/includes/static_provider.php';
require_once __DIR__ . '/../chatbot/includes/auth.php';
require_once __DIR__ . '/../chatbot/includes/matching_engine.php';
require_once __DIR__ . '/../chatbot/ai/ai-client.php';
require_once __DIR__ . '/../chatbot/admin/qa-service.php';

$passed = 0;
$failed = 0;

function assertStatic(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name} - {$detail}\n";
    }
}

echo "======================================================\n";
echo "PFTP AI Chatbot - Offline & Static Mode Verification\n";
echo "======================================================\n\n";

// Test 1: Static Knowledge Base Loading
echo "Test 1: Static Knowledge Base Preload\n";
$kb = getStaticKBRecords();
assertStatic("Loaded 239 KB records from static_data.json", count($kb) === 239, "Got " . count($kb));

// Test 2: Pre-existing Testing Visitor Conversation Recovery
echo "\nTest 2: Seeded Testing Visitor Conversation Recovery (Muhammad Bilal)\n";
$visResult = findOrCreateStaticVisitor("Muhammad Bilal", "bilal.student@example.com");
assertStatic("Visitor recognized as existing", $visResult['is_existing'] === true);
assertStatic("Visitor ID matches seed ID 1", $visResult['visitor']['id'] === 1);

$conv = getOrCreateStaticConversation(1, false);
assertStatic("Conversation found for visitor", !empty($conv['id']));
assertStatic("Existing conversation has multiple messages", count($conv['messages']) >= 8, "Got " . count($conv['messages']));
assertStatic("Conversation contains Python job inquiry", strpos(json_encode($conv['messages']), 'guarantee') !== false);

// Test 3: Live Tester Recovery
echo "\nTest 3: Live Tester Recovery\n";
$testerResult = findOrCreateStaticVisitor("Live Tester", "tester@example.com");
assertStatic("Live Tester recognized as existing", $testerResult['is_existing'] === true);
assertStatic("Live Tester ID is 2", $testerResult['visitor']['id'] === 2);

// Test 4: New Visitor Creation in Static Mode
echo "\nTest 4: Dynamic Visitor Registration in Static Mode\n";
$newVis = findOrCreateStaticVisitor("Hamza Sheikh", "hamza@example.com", "+923000000000");
assertStatic("New visitor registered", $newVis['is_existing'] === false);
$newConv = getOrCreateStaticConversation($newVis['visitor']['id'], true);
assertStatic("New conversation created", !empty($newConv['session_token']));
assertStatic("New conversation has welcome message", count($newConv['messages']) === 1);

// Test 5: Message Sending & Bot Response in Zero-DB Mode
echo "\nTest 5: AI Matching & Message Processing in Zero-DB Mode\n";
$userMsg = addStaticMessage($newConv['session_token'], 'visitor', "Why should I take the Full Stack Web Development course?");
assertStatic("Visitor message appended", $userMsg['message'] === "Why should I take the Full Stack Web Development course?");

$aiClient = new AiClient(null); // null PDO
$aiRes = $aiClient->processMessage($userMsg['message'], (int)$newConv['id'], (int)$newVis['visitor']['id'], "Hamza Sheikh");
assertStatic("AI processed message without DB error", !empty($aiRes['message']));
assertStatic("AI matched Full Stack Web Development category", $aiRes['category'] === 'Full Stack Web Development', "Got: " . $aiRes['category']);
assertStatic("AI confidence is high", $aiRes['confidence'] > 0.5);

$botMsg = addStaticMessage($newConv['session_token'], 'bot', $aiRes['message'], $aiRes['matched_kb_id'], (bool)$aiRes['is_fallback']);
assertStatic("Bot message stored in conversation", $botMsg['sender_type'] === 'bot');

// Test 6: Trilingual Matching Engine with null PDO
echo "\nTest 6: Trilingual Engine in Zero-DB Mode\n";
$matcher = new MatchingEngine(null);

// Urdu script
$urRes = $matcher->findAnswer("آپ کو گرافک ڈیزائننگ کا کورس کیوں کرنا چاہیے؟");
assertStatic("Urdu Script matched Graphic Design", $urRes['category'] === 'Graphic Design', "Got: " . $urRes['category']);

// Roman Urdu
$romanRes = $matcher->findAnswer("Aap ko Python Programming course kyun karna chahiye?");
assertStatic("Roman Urdu matched Python Programming", $romanRes['category'] === 'Python Programming', "Got: " . $romanRes['category']);

// Typo tolerance
$typoRes = $matcher->findAnswer("frelancing kaisay seekhein?");
assertStatic("Typo 'frelancing' matched Freelancing", $typoRes['category'] === 'Freelancing' || strpos($typoRes['category'], 'Freelanc') !== false, "Got: " . $typoRes['category']);

// Test 7: Administrator Authentication in Zero-DB Mode
echo "\nTest 7: Admin Authentication in Zero-DB Mode\n";
$authRes = adminLogin(null, 'admin', 'AdminPassword123!');
assertStatic("Admin authenticated with default credentials", $authRes['success'] === true);
assertStatic("Admin username is admin", $authRes['admin']['username'] === 'admin');

$failRes = adminLogin(null, 'admin', 'WrongPass!');
assertStatic("Wrong password rejected", $failRes['success'] === false);

// Test 8: Admin Q&A Service & Statistics in Zero-DB Mode
echo "\nTest 8: Admin QAService in Zero-DB Mode\n";
$qaService = new QAService(null);
$unifiedList = $qaService->getUnifiedList([], 1, 25);
assertStatic("Unified list returned total items count", $unifiedList['total'] >= 45, "Got " . $unifiedList['total']);
assertStatic("Unified list has records array", count($unifiedList['records']) > 0);

$stats = getStaticDashboardStats();
assertStatic("Dashboard stats reported 239 KB entries", $stats['totalKB'] === 239);
assertStatic("Dashboard stats reported visitors", $stats['totalVisitors'] >= 5);

echo "\n------------------------------------------------------\n";
echo "SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
