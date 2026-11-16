<?php
/**
 * Administrator Authentication & Authorization Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/url.php';

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Ensure session save path is writable on serverless / cloud hosts
        if (function_exists('sys_get_temp_dir')) {
            $tmpDir = sys_get_temp_dir();
            if (is_dir($tmpDir) && is_writable($tmpDir)) {
                @session_save_path($tmpDir);
            }
        }

        if (!headers_sent()) {
            $cookieParams = [
                'lifetime' => 86400 * 2, // 48 hours
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            session_set_cookie_params($cookieParams);
            session_name('PFTP_ADMIN_SESS');
        }
        @session_start();
    }
}

/**
 * Attempt admin login with database verification or static cloud fallback
 */
function adminLogin(?PDO $db, string $usernameOrEmail, string $password): array
{
    $usernameOrEmail = trim($usernameOrEmail);

    if (empty($usernameOrEmail) || empty($password)) {
        return ['success' => false, 'error' => 'Please provide both username and password.'];
    }

    $admin = null;

    if ($db !== null) {
        try {
            $stmt = $db->prepare("
                SELECT id, username, email, password_hash, role
                FROM admins
                WHERE username = :val1 OR email = :val2
                LIMIT 1
            ");
            $stmt->execute([
                ':val1' => $usernameOrEmail,
                ':val2' => $usernameOrEmail
            ]);
            $admin = $stmt->fetch();
        } catch (Throwable $e) {
            $admin = null;
        }
    }

    if ($admin !== null && isset($admin['password_hash'])) {
        if (!password_verify($password, $admin['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid username/email or password.'];
        }
    } else {
        // Static / Cloud Demo fallback credentials
        $validUser = pftpGetEnv('ADMIN_USER') ?: 'admin';
        $validEmail = 'admin@pftp.com.pk';
        $validPass = pftpGetEnv('ADMIN_PASS') ?: 'AdminPassword123!';

        $isUserMatch = (strcasecmp($usernameOrEmail, $validUser) === 0 || strcasecmp($usernameOrEmail, $validEmail) === 0);
        $isPassMatch = ($password === $validPass);

        if (!$isUserMatch || !$isPassMatch) {
            return ['success' => false, 'error' => 'Invalid username/email or password.'];
        }

        $admin = [
            'id'            => 1,
            'username'      => $validUser,
            'email'         => $validEmail,
            'password_hash' => password_hash($validPass, PASSWORD_BCRYPT),
            'role'          => 'super_admin'
        ];
    }

    startAdminSession();
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_regenerate_id(true);
    }

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_logged_in_at'] = time();

    if ($db !== null) {
        try {
            $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => $admin['id']]);
        } catch (Throwable $e) {}
    }

    return ['success' => true, 'admin' => $admin];
}

/**
 * Check if current user is logged in as an administrator
 */
function isAdminLoggedIn(): bool
{
    startAdminSession();
    return !empty($_SESSION['admin_id']);
}

/**
 * Enforce admin login on protected admin pages
 */
function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . url('/login'));
        exit;
    }
}

/**
 * Log out admin
 */
function adminLogout(): void
{
    startAdminSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Get current logged in admin data
 */
function getCurrentAdmin(): ?array
{
    if (!isAdminLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'email'    => $_SESSION['admin_email'] ?? '',
        'role'     => $_SESSION['admin_role'] ?? 'admin'
    ];
}
