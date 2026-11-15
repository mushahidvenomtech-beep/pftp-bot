<?php
/**
 * PFTP Admin Portal: Administrator Login
 * Route: /pftp-bot/login
 */

declare(strict_types=1);

require_once __DIR__ . '/chatbot/config/database.php';
require_once __DIR__ . '/chatbot/includes/auth.php';

startAdminSession();

// If already logged in, redirect straight to dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . url('/dashboard'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $db = getDBConnection();
        $result = adminLogin($db, $usernameOrEmail, $password);
        if ($result['success']) {
            header('Location: ' . url('/dashboard'));
            exit;
        } else {
            $error = $result['error'];
        }
    } catch (Throwable $e) {
        $error = 'Authentication service is temporarily unavailable. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — PFTP AI Chatbot &amp; Admissions</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
  <style>
    :root {
      --pftp-green: #07613F;
      --pftp-green-dark: #05482e;
      --pftp-amber: #F59E0B;
      --pftp-gold: #FBCB44;
    }

    body {
      background: #0b1329;
      background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.15) 0%, transparent 40%),
                  radial-gradient(circle at bottom left, rgba(7, 97, 63, 0.25) 0%, transparent 50%),
                  #0b1329;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      min-height: 100dvh;
      padding: 24px;
      font-family: 'Asap Condensed', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .login-container {
      max-width: 440px;
      width: 100%;
    }

    .login-card {
      background: #ffffff;
      border-radius: 18px;
      padding: 40px 36px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
    }

    .login-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .login-logo-wrap {
      width: 76px;
      height: 76px;
      margin: 0 auto 16px auto;
      border-radius: 16px;
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      padding: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .login-logo-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .login-title {
      font-family: 'Oswald', sans-serif;
      font-size: 24px;
      font-weight: 700;
      color: #0f172a;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .login-subtitle {
      font-size: 13.5px;
      color: #64748b;
      margin-top: 6px;
    }

    .login-badge-pill {
      display: inline-block;
      margin-top: 8px;
      padding: 3px 10px;
      background: rgba(7, 97, 63, 0.08);
      color: var(--pftp-green);
      font-size: 11.5px;
      font-weight: 700;
      border-radius: 20px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .admin-form-group {
      margin-bottom: 20px;
    }

    .admin-form-label {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13.5px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }

    .admin-form-control {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      font-size: 14.5px;
      color: #0f172a;
      transition: all 0.2s ease;
      background: #f8fafc;
    }

    .admin-form-control:focus {
      background: #ffffff;
      border-color: var(--pftp-green);
      box-shadow: 0 0 0 3px rgba(7, 97, 63, 0.15);
      outline: none;
    }

    .password-wrapper {
      position: relative;
    }

    .password-toggle-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #94a3b8;
      font-size: 13px;
      padding: 4px;
    }

    .password-toggle-btn:hover {
      color: #475569;
    }

    .btn-login-submit {
      width: 100%;
      padding: 12px;
      background: var(--pftp-green);
      color: #ffffff;
      font-family: 'Oswald', sans-serif;
      font-size: 16px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
      margin-top: 24px;
      box-shadow: 0 4px 6px -1px rgba(7, 97, 63, 0.25);
    }

    .btn-login-submit:hover {
      background: var(--pftp-green-dark);
      transform: translateY(-1px);
      box-shadow: 0 8px 12px -2px rgba(7, 97, 63, 0.3);
    }

    .credentials-card {
      margin-top: 24px;
      padding: 14px 16px;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: 10px;
      font-size: 12.5px;
      color: #475569;
    }

    .credentials-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 6px;
      font-family: monospace;
      font-size: 12px;
    }

    .btn-autofill {
      margin-top: 10px;
      width: 100%;
      padding: 6px;
      font-size: 11.5px;
      font-weight: 600;
      color: var(--pftp-green);
      background: rgba(7, 97, 63, 0.08);
      border: 1px solid rgba(7, 97, 63, 0.2);
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.15s;
    }

    .btn-autofill:hover {
      background: rgba(7, 97, 63, 0.15);
    }

    .login-footer-links {
      text-align: center;
      margin-top: 20px;
      font-size: 13px;
    }

    .login-footer-links a {
      color: #94a3b8;
      text-decoration: none;
      transition: color 0.15s;
    }

    .login-footer-links a:hover {
      color: #ffffff;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="login-logo-wrap">
          <img src="<?= asset('/chatbot/assets/images/pftp-logo.png') ?>" alt="PFTP Logo" class="login-logo-img">
        </div>
        <h1 class="login-title">PFTP Admin Portal</h1>
        <div class="login-subtitle">Student Counselling &amp; Admission Chatbot</div>
        <div class="login-badge-pill">Administrator Access</div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="admin-alert admin-alert-danger" style="margin-bottom: 20px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('/login') ?>" id="login-form">
        <div class="admin-form-group">
          <label class="admin-form-label" for="username">Username or Email</label>
          <input
            type="text"
            id="username"
            name="username"
            class="admin-form-control"
            required
            autocomplete="username"
            placeholder="admin or admin@pftp.com.pk"
            autofocus
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          >
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label" for="password">Password</label>
          <div class="password-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              class="admin-form-control"
              required
              autocomplete="current-password"
              placeholder="••••••••••••"
            >
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility();" title="Toggle password visibility">
              👁️
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login-submit">
          <span>Sign In to Dashboard</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
      </form>

      <div class="credentials-card">
        <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Default Access Credentials:</div>
        <div class="credentials-row">
          <span>Username:</span>
          <strong>admin</strong>
        </div>
        <div class="credentials-row">
          <span>Password:</span>
          <strong>AdminPassword123!</strong>
        </div>
        <button type="button" class="btn-autofill" onclick="autofillCredentials();">
          ⚡ 1-Click Fill Credentials
        </button>
      </div>
    </div>

    <div class="login-footer-links">
      <a href="<?= url('/') ?>">
        &larr; Back to Student Chatbot Interface
      </a>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
      const pwd = document.getElementById('password');
      if (pwd.type === 'password') {
        pwd.type = 'text';
      } else {
        pwd.type = 'password';
      }
    }

    function autofillCredentials() {
      document.getElementById('username').value = 'admin';
      document.getElementById('password').value = 'AdminPassword123!';
      const pwd = document.getElementById('password');
      pwd.focus();
    }
  </script>

</body>
</html>

