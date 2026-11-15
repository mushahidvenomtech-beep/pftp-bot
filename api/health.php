<?php
/**
 * PFTP AI Chatbot - Production Health Diagnostic & Environment Checker
 * Route: /api/health or /health
 */

declare(strict_types=1);

require_once __DIR__ . '/../chatbot/config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$status = 'healthy';
$errors = [];
$dbConnected = false;
$tablesFound = [];
$kbCount = 0;
$visitorCount = 0;
$adminCount = 0;

// Test PHP extensions
$hasPdoMysql = extension_loaded('pdo_mysql');
if (!$hasPdoMysql) {
    $status = 'error';
    $errors[] = 'PHP extension pdo_mysql is not installed or enabled.';
}

// Test Database Connection
try {
    $db = getDBConnection();
    $dbConnected = true;

    // Check tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tablesFound = $tables;

    if (in_array('knowledge_base', $tables, true)) {
        $kbCount = (int) $db->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn();
    }
    if (in_array('visitors', $tables, true)) {
        $visitorCount = (int) $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    }
    if (in_array('admins', $tables, true)) {
        $adminCount = (int) $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    }

    if (empty($tables)) {
        $status = 'warning';
        $errors[] = 'Database is connected, but no tables exist yet. Run /install.php to import schema.';
    }
} catch (Throwable $e) {
    $status = 'error';
    $errors[] = 'Database connection failed: ' . $e->getMessage();
}

// Detect Platform
$platform = 'standard';
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    $platform = 'vercel';
} elseif (isset($_ENV['RAILWAY_ENVIRONMENT']) || getenv('RAILWAY_ENVIRONMENT')) {
    $platform = 'railway';
} elseif (isset($_ENV['RENDER']) || getenv('RENDER')) {
    $platform = 'render';
} elseif (file_exists('/.dockerenv')) {
    $platform = 'docker';
} elseif (strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache') !== false) {
    $platform = 'apache';
}

$response = [
    'status'              => $status,
    'platform'            => $platform,
    'php_version'         => PHP_VERSION,
    'pdo_mysql_available' => $hasPdoMysql,
    'database' => [
        'connected' => $dbConnected,
        'host'      => DB_HOST,
        'database'  => DB_NAME,
        'tables_count' => count($tablesFound),
        'kb_records'   => $kbCount,
        'leads_count'  => $visitorCount,
        'admins_count' => $adminCount,
    ],
    'timestamp'           => date('c'),
    'errors'              => $errors
];

if ($status === 'error') {
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

