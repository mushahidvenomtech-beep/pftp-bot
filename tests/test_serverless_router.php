<?php
/**
 * Test Serverless Front Controller Router (api/index.php)
 * Simulates Vercel Serverless Function invocations
 */

declare(strict_types=1);

$tests = [
    '/'          => 'PFTP AI Chatbot',
    '/login'     => 'PFTP Admin Portal',
    '/api/health'=> '"status": "healthy"',
    '/install'   => 'PFTP Cloud Database Setup'
];

$passed = 0;
$failed = 0;

echo "=== TESTING SERVERLESS ROUTER (api/index.php) ===\n";

foreach ($tests as $route => $expectedSnippet) {
    // Write small invocation script to avoid shell quote issues
    $tempRunner = __DIR__ . '/_temp_runner.php';
    $runnerCode = "<?php
\$_SERVER['REQUEST_METHOD'] = 'GET';
\$_SERVER['REQUEST_URI'] = '{$route}';
\$_SERVER['SCRIPT_NAME'] = '/api/index.php';
ob_start();
require dirname(__DIR__) . '/api/index.php';
\$res = ob_get_clean();
echo strpos(\$res, " . var_export($expectedSnippet, true) . ") !== false ? 'OK' : 'FAIL: ' . substr(\$res, 0, 100);
";
    file_put_contents($tempRunner, $runnerCode);

    $phpBinary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
    $cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($tempRunner);
    $output = trim(shell_exec($cmd) ?: '');
    @unlink($tempRunner);

    if ($output === 'OK') {
        $passed++;
        echo "  [PASS] Route '{$route}' matched expected output\n";
    } else {
        $failed++;
        echo "  [FAIL] Route '{$route}': {$output}\n";
    }
}

echo "=================================================\n";
echo "Router Tests: {$passed} Passed, {$failed} Failed\n";
echo "=================================================\n";

if ($failed > 0) exit(1);
