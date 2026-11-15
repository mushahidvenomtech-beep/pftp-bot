<?php
/**
 * API: Search Knowledge Base / Autocomplete Questions
 * Endpoint: /chatbot/api/knowledge-search.php
 * Method: GET
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/validation.php';

setupApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed. Only GET requests are accepted.', 405);
}

$query = trim($_GET['q'] ?? '');
$lang  = trim($_GET['lang'] ?? '');
$limit = min(20, max(1, (int) ($_GET['limit'] ?? 10)));

try {
    $db = getDBConnection();

    if ($query === '') {
        // Return popular / all categories
        $stmt = $db->query("
            SELECT category, COUNT(*) as total_items
            FROM knowledge_base
            WHERE status = 'active'
            GROUP BY category
            ORDER BY category ASC
        ");
        jsonSuccess('Categories list', ['categories' => $stmt->fetchAll()]);
    }

    $params = [];
    $sql = "
        SELECT id, category, language, question, answer, keywords, possible_careers
        FROM knowledge_base
        WHERE status = 'active'
          AND (question LIKE :like1 OR keywords LIKE :like2 OR category LIKE :like3)
    ";
    $params[':like1'] = '%' . $query . '%';
    $params[':like2'] = '%' . $query . '%';
    $params[':like3'] = '%' . $query . '%';

    if (!empty($lang)) {
        $sql .= " AND language = :lang";
        $params[':lang'] = $lang;
    }

    $sql .= " ORDER BY views_count DESC, id ASC LIMIT " . $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    jsonSuccess('Search results', [
        'query'   => $query,
        'results' => $results
    ]);

} catch (Throwable $e) {
    error_log('[Knowledge Search Error] ' . $e->getMessage());
    jsonError('Failed to search knowledge base.', 500);
}

