<?php
/**
 * Shared Admin Sidebar Template
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdminAuth();

$currentAdmin = getCurrentAdmin();
$activePage = $activePage ?? 'dashboard';

// Fetch pending unanswered count
$pendingUnansweredCount = 0;
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM unanswered_queries WHERE status = 'pending'");
    $pendingUnansweredCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) {}
?>
<aside class="admin-sidebar">
  <div class="admin-brand">
    <div class="admin-brand-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
      </svg>
    </div>
    <span>PFTP Chatbot</span>
  </div>

  <nav class="admin-nav">
    <a href="<?= url('/dashboard') ?>" class="admin-nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Dashboard</span>
    </a>

    <a href="<?= url('/chatbot/admin/conversations.php') ?>" class="admin-nav-item <?= $activePage === 'conversations' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      <span>Conversations</span>
    </a>

    <a href="<?= url('/chatbot/admin/knowledge-base.php') ?>" class="admin-nav-item <?= $activePage === 'knowledge-base' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      <span>Q&amp;A Management</span>
    </a>

    <a href="<?= url('/chatbot/admin/unanswered.php') ?>" class="admin-nav-item <?= $activePage === 'unanswered' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <span>Unanswered</span>
      <?php if ($pendingUnansweredCount > 0): ?>
        <span class="admin-nav-badge"><?= $pendingUnansweredCount ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= url('/chatbot/admin/settings.php') ?>" class="admin-nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
      <span>Settings</span>
    </a>

    <div style="margin-top: 20px; padding: 0 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
      Live Preview
    </div>
    <a href="<?= url('/') ?>" target="_blank" class="admin-nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
      <span>Website Demo</span>
    </a>
  </nav>

  <div class="admin-user-footer">
    <div class="admin-user-info">
      <div class="admin-user-name"><?= htmlspecialchars($currentAdmin['username'] ?? 'Admin') ?></div>
      <div class="admin-user-role"><?= htmlspecialchars($currentAdmin['role'] ?? 'admin') ?></div>
    </div>
    <a href="<?= url('/logout') ?>" class="admin-btn-logout" title="Sign Out">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
    </a>
  </div>
</aside>

