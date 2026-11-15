<?php
/**
 * Conversation Session & Visitor Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validation.php';

/**
 * Generate a cryptographically secure 64-character session token
 */
function generateSessionToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Retrieve active conversation by session token
 */
function getConversationByToken(PDO $db, string $token): ?array
{
    if (!validateSessionToken($token)) {
        return null;
    }

    $stmt = $db->prepare("
        SELECT
            c.id AS conversation_id,
            c.visitor_id,
            c.session_token,
            c.status AS conversation_status,
            c.created_at AS conversation_created_at,
            v.full_name,
            v.email,
            v.phone
        FROM conversations c
        INNER JOIN visitors v ON c.visitor_id = v.id
        WHERE c.session_token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Find existing visitor by email or create new visitor record
 */
function findOrCreateVisitor(PDO $db, string $fullName, string $email, ?string $phone = null): int
{
    $ip = getClientIp();
    $userAgent = sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? '', 250);

    // Check if visitor already exists
    $stmt = $db->prepare("SELECT id FROM visitors WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        // Update name/phone/agent if provided
        $updateStmt = $db->prepare("
            UPDATE visitors
            SET full_name = :full_name,
                phone = COALESCE(:phone, phone),
                ip_address = :ip,
                user_agent = :ua
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':full_name' => $fullName,
            ':phone'     => $phone,
            ':ip'        => $ip,
            ':ua'        => $userAgent,
            ':id'        => $existingId
        ]);
        return (int) $existingId;
    }

    // Insert new visitor
    $insertStmt = $db->prepare("
        INSERT INTO visitors (full_name, email, phone, ip_address, user_agent)
        VALUES (:full_name, :email, :phone, :ip, :ua)
    ");
    $insertStmt->execute([
        ':full_name' => $fullName,
        ':email'     => $email,
        ':phone'     => $phone,
        ':ip'        => $ip,
        ':ua'        => $userAgent
    ]);

    return (int) $db->lastInsertId();
}

/**
 * Start or resume conversation for a visitor
 */
function startConversation(PDO $db, int $visitorId): array
{
    // Check if visitor already has an active conversation created within the last 48 hours
    $stmt = $db->prepare("
        SELECT id, session_token, status, created_at
        FROM conversations
        WHERE visitor_id = :vid AND status = 'active'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':vid' => $visitorId]);
    $existing = $stmt->fetch();

    if ($existing) {
        return [
            'conversation_id' => (int) $existing['id'],
            'session_token'   => $existing['session_token'],
            'is_new'          => false
        ];
    }

    // Create new conversation
    $token = generateSessionToken();
    $insert = $db->prepare("
        INSERT INTO conversations (visitor_id, session_token, status)
        VALUES (:vid, :token, 'active')
    ");
    $insert->execute([
        ':vid'   => $visitorId,
        ':token' => $token
    ]);

    $convId = (int) $db->lastInsertId();

    return [
        'conversation_id' => $convId,
        'session_token'   => $token,
        'is_new'          => true
    ];
}

