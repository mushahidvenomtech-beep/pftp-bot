<?php
/**
 * Database Configuration & Connection Provider
 *
 * Implements secure PDO connection with UTF-8 support,
 * SSL encryption for cloud databases (TiDB, Supabase, Railway, PlanetScale, Aiven, AWS RDS),
 * connection URL parsing (DATABASE_URL / MYSQL_URL), and fallback to local XAMPP.
 */

declare(strict_types=1);

/**
 * Retrieve environment variable from $_ENV, $_SERVER, or getenv()
 */
if (!function_exists('pftpGetEnv')) {
    function pftpGetEnv(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string) $val;
        }
        return $default;
    }
}

// Check for unified connection string (DATABASE_URL / MYSQL_URL)
$pftpDbUrl = pftpGetEnv('DATABASE_URL') ?: pftpGetEnv('MYSQL_URL');
if ($pftpDbUrl) {
    $pftpParsed = parse_url($pftpDbUrl);
    $pftpHost = $pftpParsed['host'] ?? '127.0.0.1';
    $pftpPort = (string) ($pftpParsed['port'] ?? '3306');
    $pftpName = isset($pftpParsed['path']) ? ltrim($pftpParsed['path'], '/') : 'pftp_chatbot';
    $pftpUser = isset($pftpParsed['user']) ? urldecode($pftpParsed['user']) : 'root';
    $pftpPass = isset($pftpParsed['pass']) ? urldecode($pftpParsed['pass']) : '';
} else {
    $pftpHost = pftpGetEnv('DB_HOST') ?: pftpGetEnv('MYSQLHOST') ?: pftpGetEnv('MYSQL_HOST') ?: '127.0.0.1';
    $pftpPort = pftpGetEnv('DB_PORT') ?: pftpGetEnv('MYSQLPORT') ?: pftpGetEnv('MYSQL_PORT') ?: '3306';
    $pftpName = pftpGetEnv('DB_NAME') ?: pftpGetEnv('MYSQLDATABASE') ?: pftpGetEnv('MYSQL_DATABASE') ?: 'pftp_chatbot';
    $pftpUser = pftpGetEnv('DB_USER') ?: pftpGetEnv('MYSQLUSER') ?: pftpGetEnv('MYSQL_USER') ?: 'root';
    $pftpPass = pftpGetEnv('DB_PASS');
    if ($pftpPass === null) {
        $pftpPass = pftpGetEnv('MYSQLPASSWORD');
    }
    if ($pftpPass === null) {
        $pftpPass = pftpGetEnv('MYSQL_PASSWORD');
    }
    if ($pftpPass === null) {
        $pftpPass = '';
    }
}

defined('DB_HOST') || define('DB_HOST', $pftpHost);
defined('DB_PORT') || define('DB_PORT', $pftpPort);
defined('DB_NAME') || define('DB_NAME', $pftpName);
defined('DB_USER') || define('DB_USER', $pftpUser);
defined('DB_PASS') || define('DB_PASS', $pftpPass);
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance
 *
 * @throws RuntimeException
 * @return PDO
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2
        ];

        // PHP 8.5+ deprecates PDO::MYSQL_ATTR_INIT_COMMAND in favor of Pdo\Mysql::ATTR_INIT_COMMAND
        if (class_exists('Pdo\Mysql', false) && defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
            $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
        } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[@constant('PDO::MYSQL_ATTR_INIT_COMMAND')] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
        }

        // Cloud MySQL SSL Encryption: automatically enable if remote host or explicit flag
        $isRemote = (DB_HOST !== '127.0.0.1' && DB_HOST !== 'localhost');
        $sslFlag = pftpGetEnv('DB_SSL') ?: pftpGetEnv('MYSQL_SSL');
        if ($isRemote || $sslFlag === 'true' || $sslFlag === '1') {
            if (class_exists('Pdo\Mysql', false) && defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[\Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = false;
            } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[@constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = false;
            }
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[Chatbot DB Error] ' . $e->getMessage());
            throw new RuntimeException('Database service temporarily unavailable. Please verify DB credentials in environment variables.');
        }
    }

    return $pdo;
}

/**
 * Helper to fetch a setting value from chatbot_settings table
 */
function getChatbotSetting(string $key, ?string $default = null, bool $forceRefresh = false): ?string
{
    static $settingsCache = null;

    if ($settingsCache === null || $forceRefresh) {
        $settingsCache = [];
        try {
            $db = getDBConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM chatbot_settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('[Chatbot Settings Error] ' . $e->getMessage());
            return $default;
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Safely attempt database connection without throwing exceptions
 */
function getSafeDBConnection(): ?PDO
{
    try {
        return getDBConnection();
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Check whether database is live and reachable
 */
function isDBConnected(): bool
{
    static $status = null;
    if ($status !== null) {
        return $status;
    }
    $pdo = getSafeDBConnection();
    if ($pdo === null) {
        $status = false;
        return false;
    }
    try {
        $pdo->query("SELECT 1");
        $status = true;
    } catch (Throwable $e) {
        $status = false;
    }
    return $status;
}

/**
 * Reset settings in-memory cache
 */
function clearChatbotSettingsCache(): void
{
    getChatbotSetting('', null, true);
}
