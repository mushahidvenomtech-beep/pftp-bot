<?php
/**
 * Server-Side Input Validation & Security Sanitization
 */

declare(strict_types=1);

/**
 * Clean and normalize a general input string
 */
function sanitizeString(string $input, int $maxLength = 255): string
{
    $clean = trim($input);
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
    if (mb_strlen($clean) > $maxLength) {
        $clean = mb_substr($clean, 0, $maxLength);
    }
    return $clean;
}

/**
 * Validate visitor full name
 */
function validateFullName(string $name): array
{
    $name = sanitizeString($name, 120);

    if (empty($name)) {
        return ['valid' => false, 'error' => 'Full Name is required.'];
    }

    if (mb_strlen($name) < 2) {
        return ['valid' => false, 'error' => 'Full Name must be at least 2 characters long.'];
    }

    if (mb_strlen($name) > 100) {
        return ['valid' => false, 'error' => 'Full Name must not exceed 100 characters.'];
    }

    // Allow letters, spaces, hyphens, periods, single quotes, and Urdu/Arabic characters
    if (!preg_match('/^[\p{L}\p{M}\s\.\'\-]+$/u', $name)) {
        return ['valid' => false, 'error' => 'Full Name contains invalid characters.'];
    }

    return ['valid' => true, 'value' => $name];
}

/**
 * Validate email address strictly
 */
function validateEmail(string $email): array
{
    $email = strtolower(trim($email));

    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email address is required.'];
    }

    if (mb_strlen($email) > 150) {
        return ['valid' => false, 'error' => 'Email address is too long.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Please provide a valid email address (e.g. name@example.com).'];
    }

    // Additional sanity check for domain dots
    $parts = explode('@', $email);
    if (count($parts) !== 2 || strpos($parts[1], '.') === false) {
        return ['valid' => false, 'error' => 'Invalid email domain format.'];
    }

    return ['valid' => true, 'value' => $email];
}

/**
 * Validate chat message text
 */
function validateMessageText(string $message): array
{
    $message = trim($message);

    if ($message === '') {
        return ['valid' => false, 'error' => 'Message cannot be empty.'];
    }

    if (mb_strlen($message) > 1200) {
        return ['valid' => false, 'error' => 'Message exceeds maximum limit of 1,200 characters.'];
    }

    return ['valid' => true, 'value' => $message];
}

/**
 * Validate session token format (hex/alphanumeric 32-64 chars)
 */
function validateSessionToken(string $token): bool
{
    return (bool) preg_match('/^[a-f0-9]{32,64}$/i', trim($token));
}

/**
 * Client IP address detection helper
 */
function getClientIp(): string
{
    $candidates = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];

    foreach ($candidates as $header) {
        if (!empty($_SERVER[$header])) {
            $list = explode(',', $_SERVER[$header]);
            $ip = trim($list[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Rate Limiter to prevent message spam / flooding
 */
function checkRateLimit(PDO $db, int $conversationId, int $maxPerMinute = 12): bool
{
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM messages
            WHERE conversation_id = :cid
              AND sender_type = 'visitor'
              AND created_at >= (NOW() - INTERVAL 1 MINUTE)
        ");
        $stmt->execute([':cid' => $conversationId]);
        $count = (int) $stmt->fetchColumn();

        return $count < $maxPerMinute;
    } catch (Throwable $e) {
        error_log('[Rate Limit Check Error] ' . $e->getMessage());
        return true; // fail open for user experience if DB glitch occurs
    }
}

