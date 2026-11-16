<?php
/**
 * PFTP Admin Portal: Primary Dashboard
 * Route: /pftp-bot/dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/chatbot/config/database.php';
require_once __DIR__ . '/chatbot/includes/auth.php';
require_once __DIR__ . '/chatbot/includes/static_provider.php';

// Enforce admin authentication
requireAdminAuth();

$currentAdmin = getCurrentAdmin();
$activePage = 'dashboard';

$db = getSafeDBConnection();
$isDbMode = ($db !== null && isDBConnected());

if ($isDbMode) {
    try {
        // Fetch live statistics
        $totalVisitors = (int) $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
        $totalConversations = (int) $db->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
        $activeConversations = (int) $db->query("SELECT COUNT(*) FROM conversations WHERE status = 'active'")->fetchColumn();
        $totalKB = (int) $db->query("SELECT COUNT(*) FROM knowledge_base WHERE status = 'active'")->fetchColumn();
        $unansweredCount = (int) $db->query("SELECT COUNT(*) FROM unanswered_queries WHERE status = 'pending'")->fetchColumn();

        // Fetch message count and today's activity
        $totalMessages = 0;
        try {
            $totalMessages = (int) $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        } catch (Throwable $e) {}

        $todayConversations = 0;
        try {
            $todayConversations = (int) $db->query("SELECT COUNT(*) FROM conversations WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        } catch (Throwable $e) {}

        // Fetch recent conversations
        $stmt = $db->query("
            SELECT
                c.id,
                c.session_token,
                c.status,
                c.created_at,
                c.updated_at,
                v.full_name,
                v.email,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as msg_count,
                (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_msg
            FROM conversations c
            INNER JOIN visitors v ON c.visitor_id = v.id
            ORDER BY c.updated_at DESC
            LIMIT 8
        ");
        $recentConversations = $stmt->fetchAll();

        // Fetch recent unanswered questions
        $unansweredStmt = $db->query("
            SELECT u.id, u.query_text, u.created_at, v.full_name, v.email
            FROM unanswered_queries u
            LEFT JOIN visitors v ON u.visitor_id = v.id
            WHERE u.status = 'pending'
            ORDER BY u.id DESC
            LIMIT 5
        ");
        $recentUnanswered = $unansweredStmt->fetchAll();
    } catch (Throwable $e) {
        $isDbMode = false;
    }
}

if (!$isDbMode) {
    $stats = getStaticDashboardStats();
    $totalVisitors = $stats['totalVisitors'];
    $totalConversations = $stats['totalConversations'];
    $activeConversations = $stats['activeConversations'];
    $totalKB = $stats['totalKB'];
    $unansweredCount = $stats['unansweredCount'];
    $totalMessages = $stats['totalMessages'];
    $todayConversations = $stats['todayConversations'];
    $recentConversations = getStaticConversationsList();
    $recentUnanswered = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — PFTP Admission Counselling Chatbot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
  <style>
    .admin-welcome-banner {
      background: linear-gradient(135deg, #07613F 0%, #05482e 60%, #0f172a 100%);
      color: #ffffff;
      padding: 24px 28px;
      border-radius: var(--admin-radius);
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      box-shadow: 0 10px 25px -5px rgba(7, 97, 63, 0.3);
      position: relative;
      overflow: hidden;
    }
    .admin-welcome-banner::after {
      content: "";
      position: absolute;
      top: -30px;
      right: -30px;
      width: 140px;
      height: 140px;
      border-radius: 50%;
      background: rgba(245, 158, 11, 0.15);
      pointer-events: none;
    }
    .admin-welcome-title {
      font-family: var(--font-family-heading);
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .admin-welcome-subtitle {
      font-size: 14px;
      color: #e2e8f0;
      opacity: 0.9;
    }
    .admin-quick-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .admin-quick-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .admin-quick-btn-gold {
      background: #F59E0B;
      color: #0b1329;
    }
    .admin-quick-btn-gold:hover {
      background: #FBCB44;
      transform: translateY(-1px);
    }
    .admin-quick-btn-outline {
      background: rgba(255, 255, 255, 0.12);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .admin-quick-btn-outline:hover {
      background: rgba(255, 255, 255, 0.22);
      transform: translateY(-1px);
    }
    .admin-sys-health {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-top: 24px;
    }
    .admin-sys-health-item {
      background: #ffffff;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius);
      padding: 16px 20px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .admin-sys-health-label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--admin-text-muted);
      font-weight: 600;
    }
    .admin-sys-health-value {
      font-size: 15px;
      font-weight: 700;
      color: var(--admin-text-main);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .status-dot-pulse {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #10b981;
      display: inline-block;
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
    }
  </style>
</head>
<body>

  <?php require __DIR__ . '/chatbot/admin/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 class="admin-page-title">PFTP Admissions AI Dashboard</h1>
        <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
          Pakistan Freelancing Training Program &bull; Administrative Operations
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 14px;">
        <span style="font-size: 13px; color: #64748b;">
          Logged in as: <strong style="color: #07613F;"><?= htmlspecialchars($currentAdmin['username'] ?? 'admin') ?></strong> (<?= htmlspecialchars($currentAdmin['role'] ?? 'admin') ?>)
        </span>
        <a href="<?= url('/') ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" title="Open student chatbot preview">
          Live Website
        </a>
        <a href="<?= url('/logout') ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="color: #ef4444; border-color: #fca5a5;">
          Sign Out
        </a>
      </div>
    </div>

    <div class="admin-content">

      <!-- Welcome Banner -->
      <div class="admin-welcome-banner">
        <div>
          <div class="admin-welcome-title">
            <span>Welcome back, <?= htmlspecialchars($currentAdmin['username'] ?? 'Administrator') ?>!</span>
          </div>
          <div class="admin-welcome-subtitle">
            PFTP Admissions Chatbot is actively counselling prospective students in English, Urdu Script, and Roman Urdu.
          </div>
        </div>
        <div class="admin-quick-actions">
          <a href="<?= url('/chatbot/admin/knowledge-base.php?action=add') ?>" class="admin-quick-btn admin-quick-btn-gold">
            + New Q&amp;A Entry
          </a>
          <a href="<?= url('/chatbot/admin/unanswered.php') ?>" class="admin-quick-btn admin-quick-btn-outline">
            Review Unanswered (<?= $unansweredCount ?>)
          </a>
          <a href="<?= url('/chatbot/admin/conversations.php') ?>" class="admin-quick-btn admin-quick-btn-outline">
            View Live Chats
          </a>
        </div>
      </div>

      <!-- Metrics Row -->
      <div class="admin-stats-grid">
        <div class="admin-stat-card">
          <div class="admin-stat-icon teal">👥</div>
          <div class="admin-stat-details">
            <span class="admin-stat-val"><?= number_format($totalVisitors) ?></span>
            <span class="admin-stat-label">Registered Student Leads</span>
          </div>
        </div>

        <div class="admin-stat-card">
          <div class="admin-stat-icon blue">💬</div>
          <div class="admin-stat-details">
            <span class="admin-stat-val"><?= number_format($totalConversations) ?></span>
            <span class="admin-stat-label">Total Conversations (<?= $activeConversations ?> active)</span>
          </div>
        </div>

        <div class="admin-stat-card">
          <div class="admin-stat-icon purple">📚</div>
          <div class="admin-stat-details">
            <span class="admin-stat-val"><?= number_format($totalKB) ?></span>
            <span class="admin-stat-label">Knowledge Base Q&amp;A Entries</span>
          </div>
        </div>

        <div class="admin-stat-card">
          <div class="admin-stat-icon <?= $unansweredCount > 0 ? 'amber' : 'green' ?>">❓</div>
          <div class="admin-stat-details">
            <span class="admin-stat-val"><?= number_format($unansweredCount) ?></span>
            <span class="admin-stat-label">Pending Unanswered Questions</span>
          </div>
        </div>
      </div>

      <!-- Recent Unanswered Questions Alert Card (if any pending) -->
      <?php if (!empty($recentUnanswered)): ?>
        <div class="admin-card" style="border-left: 4px solid #F59E0B;">
          <div class="admin-card-header">
            <div>
              <h2 class="admin-card-title" style="color: #92400e;">
                Student Questions Requiring Training (<?= $unansweredCount ?> Pending)
              </h2>
              <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
                Prospective students asked these questions during admission counselling. Click <strong>+ Add to KB</strong> to teach the bot the answers.
              </div>
            </div>
            <a href="<?= url('/chatbot/admin/unanswered.php') ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
              Manage All Unanswered
            </a>
          </div>
          <div class="admin-table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Visitor</th>
                  <th>Student Question</th>
                  <th>Received Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentUnanswered as $u): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($u['full_name'] ?: 'Prospective Student') ?></strong>
                      <?php if (!empty($u['email'])): ?>
                        <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($u['email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td style="max-width: 480px;">
                      <span style="font-weight: 600; color: #1e293b;">"<?= htmlspecialchars($u['query_text']) ?>"</span>
                    </td>
                    <td><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?></td>
                    <td>
                      <a href="<?= url('/chatbot/admin/knowledge-base.php?action=add&prefill=' . urlencode($u['query_text']) . '&unanswered_id=' . $u['id']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                        + Add to KB
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <!-- Recent Conversations Card -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2 class="admin-card-title">Recent Visitor Conversations</h2>
            <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
              Live transcripts of prospective students interacting with the AI Admission Consultant
            </div>
          </div>
          <a href="<?= url('/chatbot/admin/conversations.php') ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
            View All Conversations
          </a>
        </div>
        <div class="admin-table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Visitor</th>
                <th>Email</th>
                <th>Total Messages</th>
                <th>Last Activity</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentConversations)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px;">
                    No conversations recorded yet. Start chatting via the student portal to see live records!
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentConversations as $conv): ?>
                  <tr>
                    <td>#<?= $conv['id'] ?></td>
                    <td><strong><?= htmlspecialchars($conv['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($conv['email']) ?></td>
                    <td>
                      <span class="admin-badge info"><?= $conv['msg_count'] ?> messages</span>
                    </td>
                    <td><?= date('M d, Y h:i A', strtotime($conv['updated_at'])) ?></td>
                    <td>
                      <span class="admin-badge <?= $conv['status'] === 'active' ? 'success' : 'gray' ?>">
                        <?= ucfirst($conv['status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= url('/chatbot/admin/conversations.php?id=' . $conv['id']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                        View Chat Transcript
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <!-- System Health & Architecture Summary -->
      <div class="admin-sys-health">
        <div class="admin-sys-health-item">
          <span class="admin-sys-health-label">Admissions AI Engine</span>
          <span class="admin-sys-health-value">
            <span class="status-dot-pulse"></span>
            100% On-Premise PHP
          </span>
        </div>

        <div class="admin-sys-health-item">
          <span class="admin-sys-health-label">Supported Languages</span>
          <span class="admin-sys-health-value">
            Trilingual (EN / Roman / UR)
          </span>
        </div>

        <div class="admin-sys-health-item">
          <span class="admin-sys-health-label">Database Status</span>
          <span class="admin-sys-health-value">
            MySQL UTF8MB4 Connected
          </span>
        </div>

        <div class="admin-sys-health-item">
          <span class="admin-sys-health-label">Total Messages Logged</span>
          <span class="admin-sys-health-value">
            <?= number_format($totalMessages) ?>
          </span>
        </div>
      </div>

    </div>
  </main>

</body>
</html>

