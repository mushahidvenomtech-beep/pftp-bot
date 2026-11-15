<?php
/**
 * PFTP AI Chatbot - Universal Serverless Router & Front Controller
 * Target: Vercel Serverless Functions (@vercel/php), Docker, Cloud & cPanel Runtimes
 */

declare(strict_types=1);

@ini_set('display_errors', '0');

// Configure writable session storage for serverless / read-only filesystems
if (function_exists('sys_get_temp_dir')) {
    $tmpDir = sys_get_temp_dir();
    if (is_dir($tmpDir) && is_writable($tmpDir)) {
        @session_save_path($tmpDir);
    }
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Path Traversal Security Guard
if (strpos($parsedPath, '..') !== false) {
    http_response_code(400);
    exit('Bad Request');
}

// Normalize path: strip /pftp-bot prefix if present
if (strpos($parsedPath, '/pftp-bot') === 0) {
    $parsedPath = substr($parsedPath, strlen('/pftp-bot'));
    if ($parsedPath === '' || $parsedPath === false) {
        $parsedPath = '/';
    }
}

$projectRoot = dirname(__DIR__);

// Block sensitive file patterns from direct web execution/download
if (preg_match('/\.(sql|env|yml|yaml|git|log|ini|sh|bat)$/i', $parsedPath) || preg_match('/(^|\/)\./', $parsedPath)) {
    http_response_code(403);
    exit('Access Denied');
}

// Handle static assets if they reach the PHP runtime
$possibleStaticFile = $projectRoot . $parsedPath;
if ($parsedPath !== '/' && file_exists($possibleStaticFile) && is_file($possibleStaticFile)) {
    $ext = strtolower(pathinfo($possibleStaticFile, PATHINFO_EXTENSION));
    $mimes = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'json'  => 'application/json',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Content-Type: ' . $mimes[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($possibleStaticFile);
        exit;
    }
}

$cleanRoute = rtrim($parsedPath, '/');
if ($cleanRoute === '') {
    $cleanRoute = '/';
}

switch ($cleanRoute) {
    case '/':
    case '/index.php':
        require $projectRoot . '/index.php';
        break;

    case '/login':
    case '/login.php':
        require $projectRoot . '/login.php';
        break;

    case '/dashboard':
    case '/dashboard.php':
        require $projectRoot . '/dashboard.php';
        break;

    case '/logout':
    case '/logout.php':
        require $projectRoot . '/logout.php';
        break;

    case '/install':
    case '/install.php':
        require $projectRoot . '/install.php';
        break;

    case '/health':
    case '/api/health':
    case '/api/health.php':
        require __DIR__ . '/health.php';
        break;

    default:
        $targetFile = $projectRoot . $parsedPath;

        // 1. Direct PHP file match (/path.php)
        if (file_exists($targetFile) && is_file($targetFile) && substr($targetFile, -4) === '.php') {
            require $targetFile;
            break;
        }

        // 2. Clean URL mapping (/path -> /path.php)
        if (file_exists($targetFile . '.php') && is_file($targetFile . '.php')) {
            require $targetFile . '.php';
            break;
        }

        // 3. Directory index mapping (/dir or /dir/ -> /dir/index.php)
        if (is_dir($targetFile)) {
            $dirIndex = rtrim($targetFile, '/') . '/index.php';
            if (file_exists($dirIndex) && is_file($dirIndex)) {
                require $dirIndex;
                break;
            }
        }

        // 404 Fallback
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>404 Not Found - PFTP Bot</title>
          <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; text-align: center; padding: 60px 20px; background: #0b1329; color: #ffffff; }
            h1 { color: #F59E0B; font-size: 28px; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 15px; margin-bottom: 24px; }
            a { color: #10b981; text-decoration: none; font-weight: 600; }
            a:hover { text-decoration: underline; }
          </style>
        </head>
        <body>
          <h1>404 — Page Not Found</h1>
          <p>The requested route <code><?= htmlspecialchars($parsedPath) ?></code> was not found on this deployment.</p>
          <p><a href="/">Return to Home</a> &bull; <a href="/login">Admin Login</a> &bull; <a href="/api/health">System Health</a></p>
        </body>
        </html>
        <?php
        break;
}
