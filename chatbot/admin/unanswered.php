<?php
/**
 * Administrator: Unanswered Visitor Questions
 */

declare(strict_types=1);

$activePage = 'unanswered';
require_once __DIR__ . '/sidebar.php';

$db = getDBConnection();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'ignore' && $id > 0) {
        $stmt = $db->prepare("UPDATE unanswered_queries SET status = 'ignored' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $msg = "Query #{$id} marked as ignored.";
    } elseif ($action === 'resolve' && $id > 0) {
        $stmt = $db->prepare("UPDATE unanswered_queries SET status = 'resolved' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $msg = "Query #{$id} marked as resolved.";
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $db->prepare("DELETE FROM unanswered_queries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $msg = "Query #{$id} deleted.";
    }
}

$statusFilter = $_GET['status'] ?? 'pending';

$where = [];
$params = [];
if ($statusFilter !== 'all') {
    $where[] = "u.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT
        u.*,
        v.full_name,
        v.email
    FROM unanswered_queries u
    LEFT JOIN visitors v ON u.visitor_id = v.id
    {$whereSql}
    ORDER BY u.id DESC
");
$stmt->execute($params);
$queries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unanswered Questions - PFTP Chatbot Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
</head>
<body>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Unanswered Visitor Questions</h1>
      <span class="admin-badge warning"><?= count($queries) ?> Records</span>
    </div>

    <div class="admin-content">

      <?php if (!empty($msg)): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- Filter Bar -->
      <div class="admin-card">
        <div class="admin-card-body" style="padding: 16px 24px;">
          <form method="GET" action="unanswered.php" class="admin-filter-bar">
            <label class="admin-form-label" style="margin: 0;">Status Filter:</label>
            <select name="status" class="admin-form-control" style="max-width: 200px;" onchange="this.form.submit()">
              <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
              <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved / Added</option>
              <option value="ignored" <?= $statusFilter === 'ignored' ? 'selected' : '' ?>>Ignored / Irrelevant</option>
              <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Queries</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Queries Table -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h2 class="admin-card-title">Fallback Log Queries</h2>
        </div>
        <div class="admin-table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Visitor</th>
                <th>Question Asked</th>
                <th>Date Logged</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($queries)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">
                    No unanswered queries under this filter. Great job!
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($queries as $q): ?>
                  <tr>
                    <td>#<?= $q['id'] ?></td>
                    <td>
                      <?php if ($q['full_name']): ?>
                        <strong><?= htmlspecialchars($q['full_name']) ?></strong><br>
                        <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($q['email']) ?></span>
                      <?php else: ?>
                        <span style="color: #94a3b8;">Anonymous</span>
                      <?php endif; ?>
                    </td>
                    <td style="max-width: 380px;">
                      <div style="font-size: 14px; font-weight: 600; color: #0f172a;">
                        "<?= htmlspecialchars($q['query_text']) ?>"
                      </div>
                      <?php if (!empty($q['notes'])): ?>
                        <div style="font-size: 11px; color: #0f766e; margin-top: 4px;">
                          Note: <?= htmlspecialchars($q['notes']) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y h:i A', strtotime($q['created_at'])) ?></td>
                    <td>
                      <span class="admin-badge <?= $q['status'] === 'pending' ? 'warning' : ($q['status'] === 'resolved' ? 'success' : 'gray') ?>">
                        <?= ucfirst($q['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="display: flex; gap: 6px; align-items: center;">
                        <a href="knowledge-base.php?prefill=<?= urlencode($q['query_text']) ?>&unanswered_id=<?= $q['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm" title="Add directly to Knowledge Base">
                          + Add to KB
                        </a>

                        <?php if ($q['status'] === 'pending'): ?>
                          <form method="POST" action="unanswered.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="action" value="ignore">
                            <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm" title="Ignore query">Ignore</button>
                          </form>
                        <?php endif; ?>

                        <form method="POST" action="unanswered.php" onsubmit="return confirm('Delete this log?');" style="display:inline;">
                          <input type="hidden" name="id" value="<?= $q['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">✕</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

</body>
</html>

