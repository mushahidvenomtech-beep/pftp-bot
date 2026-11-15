<?php
/**
 * Administrator: Conversations & Visitor History
 */

declare(strict_types=1);

$activePage = 'conversations';
require_once __DIR__ . '/sidebar.php';

$db = getDBConnection();

$msg = '';
$error = '';

// Handle status change / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $convId = (int) ($_POST['conversation_id'] ?? 0);

    if ($action === 'close' && $convId > 0) {
        $stmt = $db->prepare("UPDATE conversations SET status = 'closed' WHERE id = :id");
        $stmt->execute([':id' => $convId]);
        $msg = "Conversation #{$convId} marked as closed.";
    } elseif ($action === 'reopen' && $convId > 0) {
        $stmt = $db->prepare("UPDATE conversations SET status = 'active' WHERE id = :id");
        $stmt->execute([':id' => $convId]);
        $msg = "Conversation #{$convId} reopened.";
    } elseif ($action === 'delete' && $convId > 0) {
        $stmt = $db->prepare("DELETE FROM conversations WHERE id = :id");
        $stmt->execute([':id' => $convId]);
        $msg = "Conversation #{$convId} deleted permanently.";
    }
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$selectedConvId = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Build query
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(v.full_name LIKE :s1 OR v.email LIKE :s2 OR c.session_token LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

if ($statusFilter === 'active' || $statusFilter === 'closed') {
    $where[] = "c.status = :status";
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT
        c.id,
        c.session_token,
        c.status,
        c.created_at,
        c.updated_at,
        v.full_name,
        v.email,
        v.phone,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as msg_count
    FROM conversations c
    INNER JOIN visitors v ON c.visitor_id = v.id
    {$whereSql}
    ORDER BY c.updated_at DESC
");
$stmt->execute($params);
$conversations = $stmt->fetchAll();

// If a specific conversation is selected, load its messages
$selectedConversation = null;
$selectedMessages = [];
if ($selectedConvId) {
    $cStmt = $db->prepare("
        SELECT c.*, v.full_name, v.email, v.phone, v.ip_address, v.user_agent
        FROM conversations c
        INNER JOIN visitors v ON c.visitor_id = v.id
        WHERE c.id = :id
    ");
    $cStmt->execute([':id' => $selectedConvId]);
    $selectedConversation = $cStmt->fetch();

    if ($selectedConversation) {
        $mStmt = $db->prepare("
            SELECT m.*, kb.category as matched_category
            FROM messages m
            LEFT JOIN knowledge_base kb ON m.matched_kb_id = kb.id
            WHERE m.conversation_id = :id
            ORDER BY m.id ASC
        ");
        $mStmt->execute([':id' => $selectedConvId]);
        $selectedMessages = $mStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conversations - PFTP Chatbot Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
</head>
<body>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1 class="admin-page-title">Visitor Conversations</h1>
      <span class="admin-badge info"><?= count($conversations) ?> conversations found</span>
    </div>

    <div class="admin-content">

      <?php if (!empty($msg)): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="admin-alert admin-alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Filter / Search Bar -->
      <div class="admin-card">
        <div class="admin-card-body" style="padding: 16px 24px;">
          <form method="GET" action="conversations.php" class="admin-filter-bar">
            <input
              type="text"
              name="search"
              class="admin-form-control"
              placeholder="Search by visitor name, email..."
              value="<?= htmlspecialchars($search) ?>"
              style="max-width: 300px;"
            >

            <select name="status" class="admin-form-control" style="max-width: 160px;">
              <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
              <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
              <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed Only</option>
            </select>

            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
            <?php if (!empty($search) || $statusFilter !== 'all'): ?>
              <a href="conversations.php" class="admin-btn admin-btn-secondary">Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: <?= $selectedConversation ? '1fr 1fr' : '1fr' ?>; gap: 24px;">

        <!-- Conversations List -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2 class="admin-card-title">All Conversations</h2>
          </div>
          <div class="admin-table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Visitor</th>
                  <th>Email</th>
                  <th>Messages</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($conversations)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 24px;">
                      No conversations matching your criteria.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($conversations as $c): ?>
                    <tr style="<?= ($selectedConvId === (int)$c['id']) ? 'background: #f0fdfa;' : '' ?>">
                      <td>#<?= $c['id'] ?></td>
                      <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                      <td><?= htmlspecialchars($c['email']) ?></td>
                      <td><span class="admin-badge gray"><?= $c['msg_count'] ?> msgs</span></td>
                      <td>
                        <span class="admin-badge <?= $c['status'] === 'active' ? 'success' : 'gray' ?>">
                          <?= ucfirst($c['status']) ?>
                        </span>
                      </td>
                      <td><?= date('M d, H:i', strtotime($c['updated_at'])) ?></td>
                      <td>
                        <a href="conversations.php?id=<?= $c['id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                          Inspect
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Selected Conversation Transcript Panel -->
        <?php if ($selectedConversation): ?>
          <div class="admin-card">
            <div class="admin-card-header">
              <div>
                <h2 class="admin-card-title">Transcript #<?= $selectedConversation['id'] ?></h2>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                  Visitor: <strong><?= htmlspecialchars($selectedConversation['full_name']) ?></strong> (<?= htmlspecialchars($selectedConversation['email']) ?>)
                </div>
              </div>
              <div style="display: flex; gap: 6px;">
                <form method="POST" action="conversations.php" style="display:inline;">
                  <input type="hidden" name="conversation_id" value="<?= $selectedConversation['id'] ?>">
                  <?php if ($selectedConversation['status'] === 'active'): ?>
                    <input type="hidden" name="action" value="close">
                    <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">Close Chat</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="reopen">
                    <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">Re-open</button>
                  <?php endif; ?>
                </form>

                <form method="POST" action="conversations.php" onsubmit="return confirm('Permanently delete this conversation and its messages?');" style="display:inline;">
                  <input type="hidden" name="conversation_id" value="<?= $selectedConversation['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Delete</button>
                </form>
              </div>
            </div>

            <div class="admin-card-body">
              <div class="admin-chat-preview">
                <?php if (empty($selectedMessages)): ?>
                  <div style="text-align: center; color: #94a3b8; padding: 20px;">No messages sent in this session.</div>
                <?php else: ?>
                  <?php foreach ($selectedMessages as $m): ?>
                    <div class="admin-chat-row <?= $m['sender_type'] === 'visitor' ? 'admin-chat-user' : 'admin-chat-bot' ?>">
                      <div style="font-size: 11px; opacity: 0.8; margin-bottom: 2px;">
                        <?= $m['sender_type'] === 'visitor' ? htmlspecialchars($selectedConversation['full_name']) : '🤖 PFTP Advisor' ?> • <?= date('h:i A', strtotime($m['created_at'])) ?>
                        <?php if ($m['is_fallback']): ?>
                          <span style="color: #dc2626; font-weight: bold;">(Fallback)</span>
                        <?php elseif ($m['matched_category']): ?>
                          <span style="color: #0f766e; font-weight: bold;">[<?= htmlspecialchars($m['matched_category']) ?>]</span>
                        <?php endif; ?>
                      </div>
                      <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>

    </div>
  </main>

</body>
</html>

