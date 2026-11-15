<?php
/**
 * Administrator: Chatbot Settings & Availability Management
 */

declare(strict_types=1);

$activePage = 'settings';
require_once __DIR__ . '/sidebar.php';

$db = getDBConnection();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsToUpdate = [
        'bot_name'              => trim($_POST['bot_name'] ?? 'PFTP Course Advisor'),
        'bot_title'             => trim($_POST['bot_title'] ?? 'Student Counselling & Admissions'),
        'bot_status'            => in_array($_POST['bot_status'] ?? '', ['online', 'offline'], true) ? $_POST['bot_status'] : 'online',
        'theme_color'           => trim($_POST['theme_color'] ?? '#0f766e'),
        'welcome_message'       => trim($_POST['welcome_message'] ?? ''),
        'fallback_response'     => trim($_POST['fallback_response'] ?? ''),
        'rate_limit_per_minute' => (string) max(3, min(60, (int) ($_POST['rate_limit_per_minute'] ?? 12))),
        'support_phone'         => trim($_POST['support_phone'] ?? ''),
        'support_email'         => trim($_POST['support_email'] ?? ''),
        'office_hours'          => trim($_POST['office_hours'] ?? ''),
        'disclaimer_text'       => trim($_POST['disclaimer_text'] ?? '')
    ];

    try {
        $stmt = $db->prepare("
            INSERT INTO chatbot_settings (setting_key, setting_value)
            VALUES (:key, :val)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");

        foreach ($settingsToUpdate as $key => $val) {
            $stmt->execute([':key' => $key, ':val' => $val]);
        }

        $msg = 'Chatbot settings updated successfully!';
    } catch (Throwable $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Fetch all current settings
$stmt = $db->query("SELECT setting_key, setting_value FROM chatbot_settings");
$currentSettings = [];
while ($row = $stmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - PFTP Chatbot Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
</head>
<body>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Chatbot Configuration & Availability</h1>
    </div>

    <div class="admin-content">

      <?php if (!empty($msg)): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= url('/chatbot/admin/settings.php') ?>">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title">General Availability & Identity</h2>
          </div>
          <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="admin-form-group">
                <label class="admin-form-label">Chatbot Availability Status</label>
                <select name="bot_status" class="admin-form-control">
                  <option value="online" <?= ($currentSettings['bot_status'] ?? '') === 'online' ? 'selected' : '' ?>>🟢 Online (Accepting queries)</option>
                  <option value="offline" <?= ($currentSettings['bot_status'] ?? '') === 'offline' ? 'selected' : '' ?>>🔴 Offline (Displaying offline notice)</option>
                </select>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Controls the live status indicator on the widget.</div>
              </div>

              <div class="admin-form-group">
                <label class="admin-form-label">Theme Color (Hex)</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <input type="color" name="theme_color_picker" value="<?= htmlspecialchars($currentSettings['theme_color'] ?? '#0f766e') ?>" onchange="document.getElementById('theme_color_text').value = this.value" style="width: 44px; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                  <input type="text" id="theme_color_text" name="theme_color" class="admin-form-control" value="<?= htmlspecialchars($currentSettings['theme_color'] ?? '#0f766e') ?>">
                </div>
              </div>

              <div class="admin-form-group">
                <label class="admin-form-label">Bot Display Name</label>
                <input type="text" name="bot_name" class="admin-form-control" required value="<?= htmlspecialchars($currentSettings['bot_name'] ?? 'PFTP Course Advisor') ?>">
              </div>

              <div class="admin-form-group">
                <label class="admin-form-label">Bot Subtitle / Tagline</label>
                <input type="text" name="bot_title" class="admin-form-control" required value="<?= htmlspecialchars($currentSettings['bot_title'] ?? 'Student Counselling & Admissions') ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title">Automated Responses & Behaviour</h2>
          </div>
          <div class="admin-card-body">
            <div class="admin-form-group">
              <label class="admin-form-label">Initial Welcome Message (Sent after visitor registration)</label>
              <textarea name="welcome_message" class="admin-form-control" style="min-height: 80px;" required><?= htmlspecialchars($currentSettings['welcome_message'] ?? '') ?></textarea>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Default Fallback Response (When no suitable answer is found)</label>
              <textarea name="fallback_response" class="admin-form-control" style="min-height: 80px;" required><?= htmlspecialchars($currentSettings['fallback_response'] ?? '') ?></textarea>
              <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Never generates fabricated answers; smoothly guides user to human counselling.</div>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Rate Limiter (Max messages per minute per session)</label>
              <input type="number" name="rate_limit_per_minute" min="3" max="60" class="admin-form-control" style="max-width: 180px;" value="<?= htmlspecialchars($currentSettings['rate_limit_per_minute'] ?? '12') ?>">
            </div>
          </div>
        </div>

        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title">Institute Contact Info & Disclaimers</h2>
          </div>
          <div class="admin-card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div class="admin-form-group">
                <label class="admin-form-label">Official Support / WhatsApp Phone</label>
                <input type="text" name="support_phone" class="admin-form-control" value="<?= htmlspecialchars($currentSettings['support_phone'] ?? '+92 300 1234567') ?>">
              </div>

              <div class="admin-form-group">
                <label class="admin-form-label">Official Support Email</label>
                <input type="email" name="support_email" class="admin-form-control" value="<?= htmlspecialchars($currentSettings['support_email'] ?? 'counselling@pftp.com.pk') ?>">
              </div>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Office & Counselling Hours</label>
              <input type="text" name="office_hours" class="admin-form-control" value="<?= htmlspecialchars($currentSettings['office_hours'] ?? 'Monday to Saturday, 9:00 AM - 6:00 PM (PKT)') ?>">
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Student Counselling Policy Disclaimer</label>
              <textarea name="disclaimer_text" class="admin-form-control" style="min-height: 60px;"><?= htmlspecialchars($currentSettings['disclaimer_text'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 10px 24px;">
              Save All Settings
            </button>
          </div>
        </div>
      </form>

    </div>
  </main>

</body>
</html>

