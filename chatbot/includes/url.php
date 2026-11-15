<?php
/**
 * Universal URL & Base Path Resolver
 * Automatically adapts between:
 * 1. Subdirectory setups (e.g. http://localhost/pftp-bot/)
 * 2. Root domain setups (e.g. https://pftp-bot.vercel.app/ or https://pftp.com.pk/)
 */

declare(strict_types=1);

/**
 * Returns the detected URL base path prefix (e.g. '/pftp-bot' or '')
 */
function getAppBasePath(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    if (strpos($script, '/pftp-bot') === 0 || strpos($uri, '/pftp-bot') === 0) {
        $basePath = '/pftp-bot';
    } else {
        $basePath = '';
    }

    return $basePath;
}

/**
 * Build an absolute application URL relative to the active deployment environment
 */
function url(string $path = ''): string
{
    $base = getAppBasePath();
    $cleanPath = '/' . ltrim($path, '/');
    if ($cleanPath === '/') {
        return $base !== '' ? $base . '/' : '/';
    }
    return $base . $cleanPath;
}

/**
 * Build an asset URL relative to the active deployment environment
 */
function asset(string $path): string
{
    return url($path);
}

