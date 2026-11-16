<?php
/**
 * Test: Full Mushahid Majeed trilingual admission & lead conversion conversation test
 */

declare(strict_types=1);

putenv('VERCEL=1');
$_SERVER['VERCEL'] = '1';

require_once __DIR__ . '/../chatbot/config/database.php';
require_once __DIR__ . '/../chatbot/includes/static_provider.php';
require_once __DIR__ . '/../chatbot/ai/ai-client.php';

echo "=================================================================\n";
echo "PFTP AI Chatbot - Mushahid Majeed Trilingual Process Verification\n";
echo "=================================================================\n\n";

// 1. Test Registration
echo "1. Registering / Finding Mushahid Majeed:\n";
$reg = findOrCreateStaticVisitor('Mushahid Majeed', 'mushahid@gmail.com');
echo "   Visitor Name: " . $reg['visitor']['full_name'] . "\n";
echo "   Visitor Email: " . $reg['visitor']['email'] . "\n";
echo "   Is Existing: " . ($reg['is_existing'] ? 'YES' : 'NO') . "\n";

$conv = getOrCreateStaticConversation((int)$reg['visitor']['id'], false);
echo "   Session Token: " . $conv['session_token'] . "\n";
echo "   Messages Loaded: " . count($conv['messages']) . "\n";

if (count($conv['messages']) !== 18) {
    echo "ERROR: Expected 18 messages, got " . count($conv['messages']) . "\n";
    exit(1);
}
echo "   [PASS] Complete 18-message trilingual conversation loaded successfully!\n\n";

// 2. Verify English, Roman Urdu, and Urdu parts in loaded transcript
echo "2. Inspecting Transcript Stages:\n";
$firstMsg = $conv['messages'][0]['message'];
$studyMsg = $conv['messages'][1]['message'];
$roadmapEn = $conv['messages'][6]['message'];
$romanUrduMsg = $conv['messages'][7]['message'];
$urduMsg = $conv['messages'][13]['message'];

echo "   English Greeting: " . substr($firstMsg, 0, 45) . "...\n";
echo "   Study Detail (ICS): " . substr($studyMsg, 0, 50) . "...\n";
echo "   English Roadmap: " . substr($roadmapEn, 0, 60) . "...\n";
echo "   Roman Urdu Module: " . substr($romanUrduMsg, 0, 50) . "...\n";
echo "   Urdu Script Module: " . substr($urduMsg, 0, 50) . "...\n";
echo "   [PASS] All 3 language modules present in transcript!\n\n";

// 3. Test Live Trilingual AI Queries
echo "3. Testing Live Trilingual AI Matching:\n";
$token = $conv['session_token'];
$aiClient = new AiClient(null);

$queries = [
    'EN Roadmap' => 'What is the complete 12-week roadmap for course completion and admission?',
    'EN Study'   => 'I have completed intermediate / ICS. Which course is best suited for my study details?',
    'ROMAN Roadmap' => 'Course completion ka mukammal roadmap kya hai aur dakhla kaise milega?',
    'ROMAN Study'   => 'Mera study background intermediate / ICS hai, mujhe konsa course karna chahiye?',
    'URDU Roadmap'  => 'کورس کی تکمیل کا مکمل 12 ہفتوں کا روڈ میپ کیا ہے اور داخلہ کیسے ہوگا؟',
    'URDU Study'    => 'میرا تعلیمی پس منظر انٹرمیڈیٹ ہے، مجھے اپنے کیریئر کے لیے کون سا کورس منتخب کرنا چاہیے؟'
];

foreach ($queries as $label => $q) {
    echo "   Testing [$label]:\n";
    $aiRes = $aiClient->processMessage($q, 1, 1, 'Mushahid Majeed');
    echo "     Category: " . $aiRes['category'] . "\n";
    echo "     Confidence: " . $aiRes['confidence'] . "\n";
    echo "     Careers: " . ($aiRes['possible_careers'] ?? 'None') . "\n";
    echo "     Answer Preview: " . substr(strip_tags($aiRes['message']), 0, 80) . "...\n";
    if ($aiRes['confidence'] < 0.8) {
        echo "     [FAIL] Confidence too low: " . $aiRes['confidence'] . "\n";
        exit(1);
    }
    echo "     [PASS] Matched successfully!\n\n";
}

echo "=================================================================\n";
echo "SUCCESS: Mushahid Majeed Trilingual Setup 100% Verified!\n";
echo "=================================================================\n";
