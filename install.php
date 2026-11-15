<?php
/**
 * PFTP AI Chatbot - 1-Click Database Setup & Cloud Migration Assistant
 * Route: /install or /install.php
 *
 * Automatically provisions tables, default settings, default administrator,
 * and 185 trilingual knowledge base records on any cloud MySQL provider
 * (TiDB, PlanetScale, Supabase, Railway, Render, Aiven, AWS RDS, cPanel, XAMPP).
 */

declare(strict_types=1);

require_once __DIR__ . '/chatbot/config/database.php';
require_once __DIR__ . '/chatbot/includes/url.php';

$error = null;
$success = null;
$dbConnected = false;
$tablesCount = 0;
$kbCount = 0;
$pdo = null;

try {
    $pdo = getDBConnection();
    $dbConnected = true;

    // Check existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tablesCount = count($existingTables);

    if (in_array('knowledge_base', $existingTables, true)) {
        $kbCount = (int) $pdo->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn();
    }
} catch (Throwable $e) {
    $error = 'Database Connection Failed: ' . $e->getMessage();
}

/**
 * Safely execute a multi-statement SQL script statement by statement
 */
function executeSqlScript(PDO $pdo, string $sql): void
{
    // Strip only top-level CREATE DATABASE and USE statements at line start
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+`?[a-zA-Z0-9_]+`?[^;]*;/im', '', $sql);
    $sql = preg_replace('/^\s*USE\s+`?[a-zA-Z0-9_]+`?\s*;/im', '', $sql);

    $lines = explode("\n", $sql);
    $buffer = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Skip pure comment lines when buffer is empty
        if ($buffer === '' && (strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || $trimmed === '')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (preg_match('/;\s*$/', $trimmed)) {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
            $buffer = '';
        }
    }

    $stmt = trim($buffer);
    if ($stmt !== '') {
        $pdo->exec($stmt);
    }
}

// Handle Migration Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbConnected) {
    $action = $_POST['action'] ?? '';

    if ($action === 'install' || $action === 'reset') {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 1. Load schema
            $schemaPath = __DIR__ . '/chatbot/database/schema.sql';
            if (!file_exists($schemaPath)) {
                throw new RuntimeException("schema.sql file not found at {$schemaPath}");
            }
            executeSqlScript($pdo, file_get_contents($schemaPath));

            // 2. Load Knowledge Base
            $kbPath = __DIR__ . '/chatbot/database/knowledge-base.sql';
            if (file_exists($kbPath)) {
                executeSqlScript($pdo, file_get_contents($kbPath));
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // Re-check
            $stmt = $pdo->query("SHOW TABLES");
            $tablesCount = count($stmt->fetchAll(PDO::FETCH_COLUMN));
            $kbCount = (int) $pdo->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn();

            $success = "Database successfully initialized! {$tablesCount} tables verified and {$kbCount} Knowledge Base entries loaded.";
        } catch (Throwable $e) {
            $error = 'Migration Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Setup &amp; Migration — PFTP AI Chatbot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
  <style>
    body {
      background: #0b1329;
      background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.15) 0%, transparent 40%),
                  radial-gradient(circle at bottom left, rgba(7, 97, 63, 0.25) 0%, transparent 50%),
                  #0b1329;
      color: #1e293b;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      font-family: 'Asap Condensed', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .setup-card {
      background: #ffffff;
      border-radius: 18px;
      padding: 36px;
      max-width: 600px;
      width: 100%;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
    }
    .setup-header {
      text-align: center;
      margin-bottom: 24px;
    }
    .setup-title {
      font-family: 'Oswald', sans-serif;
      font-size: 24px;
      font-weight: 700;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .setup-subtitle {
      font-size: 14px;
      color: #64748b;
      margin-top: 4px;
    }
    .info-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 20px;
      font-size: 13.5px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px dashed #e2e8f0;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 12px;
      border-radius: 10px;
      font-family: 'Oswald', sans-serif;
      font-size: 16px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-green {
      background: #07613F;
      color: #ffffff;
    }
    .btn-green:hover {
      background: #05482e;
    }
    .btn-outline {
      background: transparent;
      border: 1.5px solid #cbd5e1;
      color: #475569;
      margin-top: 10px;
    }
    .btn-outline:hover {
      background: #f1f5f9;
    }
  </style>
</head>
<body>

  <div class="setup-card">
    <div class="setup-header">
      <h1 class="setup-title">PFTP Cloud Database Setup</h1>
      <div class="setup-subtitle">Automated MySQL Schema &amp; Knowledge Base Migration</div>
    </div>

    <?php if ($success): ?>
      <div class="admin-alert admin-alert-success" style="margin-bottom: 20px;">
        <div><?= htmlspecialchars($success) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="admin-alert admin-alert-danger" style="margin-bottom: 20px;">
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="info-box">
      <div class="info-row">
        <span>Connection Status:</span>
        <strong>
          <?php if ($dbConnected): ?>
            <span style="color: #10b981;">● Connected</span>
          <?php else: ?>
            <span style="color: #ef4444;">● Disconnected</span>
          <?php endif; ?>
        </strong>
      </div>
      <div class="info-row">
        <span>Database Host:</span>
        <code><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></code>
      </div>
      <div class="info-row">
        <span>Database Name:</span>
        <code><?= htmlspecialchars(DB_NAME) ?></code>
      </div>
      <div class="info-row">
        <span>Existing Tables:</span>
        <strong><?= $tablesCount ?> tables</strong>
      </div>
      <div class="info-row">
        <span>Knowledge Base Records:</span>
        <strong><?= $kbCount ?> entries</strong>
      </div>
    </div>

    <?php if (!$dbConnected): ?>
      <div style="font-size: 13.5px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
        <strong>How to fix:</strong> In your hosting dashboard (Vercel, Railway, Render, etc.), set the following environment variables:
        <ul style="margin: 8px 0 0 20px;">
          <li><code>DATABASE_URL</code> (e.g. <code>mysql://user:pass@host:3306/dbname</code>) OR</li>
          <li><code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code></li>
        </ul>
      </div>
      <a href="<?= url('/install') ?>" class="btn-action btn-green">🔄 Retry Connection</a>
    <?php elseif ($tablesCount > 0 && $kbCount > 0 && !$success): ?>
      <div style="text-align: center; margin-bottom: 20px; color: #10b981; font-weight: 600;">
        ✓ Database is fully installed and operational!
      </div>
      <a href="<?= url('/') ?>" class="btn-action btn-green">🚀 Open Student Chatbot</a>
      <a href="<?= url('/login') ?>" class="btn-action btn-outline">🔐 Go to Admin Login</a>

      <form method="POST" action="<?= url('/install') ?>" style="margin-top: 24px; text-align: center;">
        <input type="hidden" name="action" value="reset">
        <button type="submit" onclick="return confirm('This will re-import all schema tables and reset default KB entries. Proceed?');" style="background: none; border: none; color: #94a3b8; font-size: 12px; cursor: pointer; text-decoration: underline;">
          Re-run Migration / Re-import Schema
        </button>
      </form>
    <?php else: ?>
      <form method="POST" action="<?= url('/install') ?>">
        <input type="hidden" name="action" value="install">
        <button type="submit" class="btn-action btn-green">
          ⚡ Run 1-Click Database Setup Now
        </button>
      </form>
      <?php if ($success): ?>
        <a href="<?= url('/') ?>" class="btn-action btn-outline" style="margin-top: 14px;">🚀 Open Student Chatbot</a>
        <a href="<?= url('/login') ?>" class="btn-action btn-outline">🔐 Go to Admin Login</a>
      <?php endif; ?>
    <?php endif; ?>

  </div>

</body>
</html>

