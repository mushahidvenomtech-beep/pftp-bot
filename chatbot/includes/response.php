<?php
/**
 * Standardized JSON Response & CORS Handler
 */

declare(strict_types=1);

/**
 * Configure standard API response headers including CORS
 */
function setupApiHeaders(): void
{
    // Allow embed from any domain/port (vital for universal widget embed)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Session-Token');
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');

    // Handle pre-flight OPTIONS request
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Emit JSON response and terminate script
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Helper for success response
 */
function jsonSuccess(string $message, array $data = [], int $statusCode = 200): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], $statusCode);
}

/**
 * Helper for error response
 */
function jsonError(string $message, int $statusCode = 400, array $extra = []): void
{
    $payload = array_merge([
        'success' => false,
        'error'   => $message
    ], $extra);

    jsonResponse($payload, $statusCode);
}

/**
 * Parse incoming JSON or POST request body safely
 */
function getRequestPayload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

