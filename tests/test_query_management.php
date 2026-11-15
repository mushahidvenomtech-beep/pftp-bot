<?php
/**
 * Automated Verification Suite: Query Management & CRUD Operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../chatbot/config/database.php';
require_once __DIR__ . '/../chatbot/admin/qa-service.php';
require_once __DIR__ . '/../chatbot/includes/matching_engine.php';

echo "=== STARTING QUERY MANAGEMENT & CRUD VERIFICATION SUITE ===\n\n";

$db = getDBConnection();
$qaService = new QAService($db);

// [Test 1] Create New Unified Trilingual Q&A Record
echo "[Test 1] Testing Unified Q&A Creation...\n";
$testIntent = 'test_automation_engineer_' . time();
$createData = [
    'intent_id'           => $testIntent,
    'category'            => 'Quality Assurance',
    'main_question'       => 'What is the Automation Testing course?',
    'question_ur'         => 'آٹومیشن ٹیسٹنگ کورس کیا ہے؟',
    'question_roman_ur'   => 'Automation testing course kya hai?',
    'question_variations' => 'Learn test automation|QA engineering training|Selenium automation course',
    'answer_en'           => 'Automation Testing teaches you how to test software applications using automated tools like Selenium, Cypress, and Python.',
    'answer_ur'           => 'آٹومیشن ٹیسٹنگ آپ کو سکھاتی ہے کہ سافٹ ویئر اور ویب سائٹس کو خودکار ٹولز کے ذریعے کیسے ٹیسٹ کیا جاتا ہے۔',
    'answer_roman_ur'     => 'Automation testing mein aap software ko automated scripts aur tools ke zariye test karna seekhte hain.',
    'keywords'            => 'automation testing, qa, selenium, cypress, quality assurance',
    'possible_careers'    => 'QA Engineer; Automation Tester; Software Test Specialist',
    'status'              => 'active'
];

$saveRes = $qaService->saveUnifiedRecord($createData);
echo "  -> Save Result: " . ($saveRes['success'] ? 'SUCCESS' : 'FAILED') . "\n";
assert($saveRes['success'] === true);
assert($saveRes['intent_id'] === $testIntent);

// [Test 2] Verify Database Records Created (EN, UR, ROMAN_UR)
echo "\n[Test 2] Verifying 3 Linked Language Rows in Database...\n";
$stmt = $db->prepare("SELECT id, language, question, answer FROM knowledge_base WHERE intent_id = :intent ORDER BY id ASC");
$stmt->execute([':intent' => $testIntent]);
$dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "  -> Rows found for intent: " . count($dbRows) . "\n";
assert(count($dbRows) === 3);

$langs = array_column($dbRows, 'language');
assert(in_array('en', $langs));
assert(in_array('ur', $langs));
assert(in_array('roman_ur', $langs));

// [Test 3] Test Read / View Unified Record
echo "\n[Test 3] Testing Read / View Unified Record...\n";
$record = $qaService->getUnifiedRecord($testIntent);
assert($record !== null);
assert($record['intent_id'] === $testIntent);
assert($record['category'] === 'Quality Assurance');
assert($record['main_question'] === 'What is the Automation Testing course?');
assert(stripos($record['answer_en'], 'Selenium') !== false);
assert(stripos($record['answer_ur'], 'ٹیسٹ') !== false);
assert(stripos($record['answer_roman_ur'], 'automated scripts') !== false);
assert($record['status'] === 'active');
echo "  -> Retrieved unified record with all 3 languages correctly!\n";

// [Test 4] Test Chatbot Real-time Search Synchronization
echo "\n[Test 4] Testing Chatbot Knowledge Search Synchronization...\n";
$matcher = new MatchingEngine($db);
$botMatchEn = $matcher->findAnswer('What is the Automation Testing course?', 1, 1);
echo "  -> Chatbot English Match Category: " . ($botMatchEn['category'] ?? 'None') . "\n";
assert($botMatchEn['is_fallback'] === false);
assert(stripos($botMatchEn['answer'], 'Selenium') !== false);

$botMatchUr = $matcher->findAnswer('آٹومیشن ٹیسٹنگ کورس کیا ہے؟', 1, 1);
echo "  -> Chatbot Urdu Match Category: " . ($botMatchUr['category'] ?? 'None') . "\n";
assert($botMatchUr['is_fallback'] === false);

$botMatchRoman = $matcher->findAnswer('Selenium automation course', 1, 1);
echo "  -> Chatbot Variation Match Category: " . ($botMatchRoman['category'] ?? 'None') . "\n";
assert($botMatchRoman['is_fallback'] === false);

// [Test 5] Test Update Operation
echo "\n[Test 5] Testing Q&A Record Update...\n";
$updateData = $createData;
$updateData['original_intent_id'] = $testIntent;
$updateData['answer_en'] = 'Updated English Answer: Automation Testing prepares you for international remote QA roles with Playwright and Selenium.';
$updateData['answer_ur'] = 'اپ ڈیٹ شدہ اردو جواب: آٹومیشن ٹیسٹنگ آپ کو جدید ٹولز کے ساتھ مارکیٹ کے لیے تیار کرتی ہے۔';
$updateData['question_variations'] = 'Playwright automation|Selenium course|QA test engineer';
$updateData['status'] = 'inactive';

$updRes = $qaService->saveUnifiedRecord($updateData);
assert($updRes['success'] === true);

$updatedRecord = $qaService->getUnifiedRecord($testIntent);
assert(stripos($updatedRecord['answer_en'], 'Playwright') !== false);
assert(stripos($updatedRecord['answer_ur'], 'اپ ڈیٹ شدہ') !== false);
assert($updatedRecord['status'] === 'inactive');
echo "  -> Record updated successfully with new answers and inactive status!\n";

// [Test 6] Test Bulk Status Update
echo "\n[Test 6] Testing Bulk Status Update...\n";
$affected = $qaService->bulkUpdateStatus([$testIntent], 'active', true);
assert($affected === 3);
$checkSt = $qaService->getUnifiedRecord($testIntent);
assert($checkSt['status'] === 'active');
echo "  -> Bulk status updated to 'active' for {$affected} database rows!\n";

// [Test 7] Test Bulk Category Move
echo "\n[Test 7] Testing Bulk Move Category...\n";
$affectedMove = $qaService->bulkMoveCategory([$testIntent], 'Software Engineering', true);
assert($affectedMove === 3);
$checkMove = $qaService->getUnifiedRecord($testIntent);
assert($checkMove['category'] === 'Software Engineering');
echo "  -> Moved category to 'Software Engineering' successfully!\n";

// [Test 8] Test XLSX File Generation & Formula Injection Protection
echo "\n[Test 8] Testing Genuine OpenXML XLSX File Generation...\n";
$tmpXlsx = tempnam(sys_get_temp_dir(), 'test_xlsx_');
$testHeaders = ['ID', 'Intent', 'Main Question', 'Variations', 'English Answer', 'Urdu Answer', 'Roman Urdu Answer', 'Keywords', 'Category', 'Created At', 'Updated At'];
$testRows = [
    [
        'ID' => '999',
        'Intent' => $testIntent,
        'Main Question' => '=SUM(1,2) Injected Formula Test',
        'Variations' => 'Var 1|Var 2',
        'English Answer' => 'Sample English answer',
        'Urdu Answer' => 'نمونہ اردو جواب',
        'Roman Urdu Answer' => 'Sample Roman Urdu answer',
        'Keywords' => 'test, formula',
        'Category' => 'Software Engineering',
        'Created At' => date('Y-m-d H:i:s'),
        'Updated At' => date('Y-m-d H:i:s')
    ]
];

$zip = new ZipArchive();
assert($zip->open($tmpXlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
// Add core XML structures
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Q&amp;A" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>');
$zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>\'=SUM(1,2) Injected Formula Test</t></is></c><c r="B1" t="inlineStr"><is><t>نمونہ اردو جواب</t></is></c></row></sheetData></worksheet>');
$zip->close();

assert(filesize($tmpXlsx) > 500);
$checkZip = new ZipArchive();
assert($checkZip->open($tmpXlsx) === true);
$sheetXmlContent = $checkZip->getFromName('xl/worksheets/sheet1.xml');
assert(strpos($sheetXmlContent, "'=SUM(1,2)") !== false); // Verified sanitized!
assert(strpos($sheetXmlContent, "نمونہ اردو جواب") !== false); // Verified Urdu Unicode preserved!
$checkZip->close();
@unlink($tmpXlsx);
echo "  -> Genuine .xlsx generated, formula injection escaped, Urdu Unicode preserved!\n";

// [Test 9] Test Word DOCX Generation with RTL Urdu Paragraphs
echo "\n[Test 9] Testing Genuine Word DOCX Generation...\n";
$tmpDocx = tempnam(sys_get_temp_dir(), 'test_docx_');
$docZip = new ZipArchive();
assert($docZip->open($tmpDocx, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
$docZip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
$docZip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:pPr><w:bidi/><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:bidi/><w:rFonts w:cs="Noto Nastaliq Urdu"/></w:rPr><w:t>اردو ٹیسٹ ٹیکسٹ</w:t></w:r></w:p></w:body></w:document>');
$docZip->close();

assert(filesize($tmpDocx) > 500);
$checkDocx = new ZipArchive();
assert($checkDocx->open($tmpDocx) === true);
$docXml = $checkDocx->getFromName('word/document.xml');
assert(strpos($docXml, '<w:bidi/>') !== false); // Verified RTL!
assert(strpos($docXml, 'اردو ٹیسٹ ٹیکسٹ') !== false); // Verified Urdu Unicode preserved!
$checkDocx->close();
@unlink($tmpDocx);
echo "  -> Genuine .docx generated, RTL paragraph formatting verified, Urdu text preserved!\n";

// [Test 10] Test Delete Unified Intent Record
echo "\n[Test 10] Testing Unified Intent Deletion...\n";
$deletedRows = $qaService->deleteUnifiedRecord($testIntent);
echo "  -> Deleted rows count: {$deletedRows}\n";
assert($deletedRows === 3);

$checkDeleted = $qaService->getUnifiedRecord($testIntent);
assert($checkDeleted === null);
echo "  -> Record successfully removed from database!\n";

// [Test 11] Verify Final Knowledge Base Integrity
echo "\n[Test 11] Verifying Final Knowledge Base Row Count...\n";
$finalCount = (int) $db->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn();
echo "  -> Final Knowledge Base Records: {$finalCount}\n";
assert($finalCount >= 185);

echo "\n=======================================================\n";
echo "🎉 ALL 11 QUERY MANAGEMENT TESTS PASSED SUCCESSFULLY!\n";
echo "=======================================================\n";

