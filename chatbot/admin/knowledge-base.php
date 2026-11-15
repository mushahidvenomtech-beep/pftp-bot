<?php
/**
 * PFTP Admin Dashboard: Complete Query & Q&A Knowledge Base Management System
 * Full CRUD, Grouped Intent View & Individual Records View, Copy, Clear, Search, Filters,
 * Bulk Operations, Genuine OpenXML Excel (.xlsx) & Word (.docx) Export, Import, and SQL Backup.
 */

declare(strict_types=1);

$activePage = 'knowledge-base';
require_once __DIR__ . '/sidebar.php';
require_once __DIR__ . '/qa-service.php';

$db = getDBConnection();
$qaService = new QAService($db);

$msg = '';
$error = '';
$actionSuccess = '';

// Handle Direct File Exports before any HTML output
if (isset($_GET['export'])) {
    $exportFormat = strtolower(trim($_GET['export']));
    $scope = $_GET['scope'] ?? 'filtered';
    $search = trim($_GET['search'] ?? '');
    $categoryFilter = trim($_GET['category'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');
    $langFilter = trim($_GET['lang'] ?? '');

    if ($exportFormat === 'sql') {
        $qaService->exportSQLBackup();
        exit;
    }

    // Determine records to export
    if ($scope === 'all') {
        $exportData = $qaService->getUnifiedList([], 1, 0)['records'];
    } elseif ($scope === 'selected' && !empty($_GET['selected_keys'])) {
        $keys = array_filter(array_map('trim', explode(',', $_GET['selected_keys'])));
        $exportData = [];
        foreach ($keys as $k) {
            $rec = $qaService->getUnifiedRecord($k);
            if ($rec) $exportData[] = $rec;
        }
    } else {
        // Filtered scope
        $exportData = $qaService->getUnifiedList([
            'search'   => $search,
            'category' => $categoryFilter,
            'status'   => $statusFilter,
            'lang'     => $langFilter
        ], 1, 0)['records'];
    }

    $timestamp = date('Y-m-d-His');
    if ($exportFormat === 'xlsx') {
        $qaService->exportXLSX($exportData, "pftp-qa-dataset-{$timestamp}.xlsx");
        exit;
    } elseif ($exportFormat === 'docx') {
        $qaService->exportDOCX($exportData, "pftp-qa-knowledge-base-{$timestamp}.docx");
        exit;
    }
}

// Handle Form Submissions (Create, Update, Delete, Bulk Actions, Import)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Unified Save (Create / Update Intent)
    if ($action === 'save_unified') {
        $intentId       = trim($_POST['intent_id'] ?? '');
        $originalIntent = trim($_POST['original_intent_id'] ?? '');
        $category       = trim($_POST['category'] ?? 'General');
        $mainQuestion   = trim($_POST['main_question'] ?? '');
        $questionUr     = trim($_POST['question_ur'] ?? '');
        $questionRoman  = trim($_POST['question_roman_ur'] ?? '');
        $variations     = trim($_POST['question_variations'] ?? '');
        $answerEn       = trim($_POST['answer_en'] ?? '');
        $answerUr       = trim($_POST['answer_ur'] ?? '');
        $answerRomanUr  = trim($_POST['answer_roman_ur'] ?? '');
        $keywords       = trim($_POST['keywords'] ?? '');
        $careers        = trim($_POST['possible_careers'] ?? '');
        $status         = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $unansweredId   = (int) ($_POST['unanswered_id'] ?? 0);

        // Normalize variations (can be pipe or newline separated)
        if (strpos($variations, "\n") !== false) {
            $lines = array_filter(array_map('trim', explode("\n", $variations)));
            $variations = implode('|', $lines);
        }

        $res = $qaService->saveUnifiedRecord([
            'intent_id'           => $intentId,
            'original_intent_id'  => $originalIntent,
            'category'            => $category,
            'main_question'       => $mainQuestion,
            'question_ur'         => $questionUr,
            'question_roman_ur'   => $questionRoman,
            'question_variations' => $variations,
            'answer_en'           => $answerEn,
            'answer_ur'           => $answerUr,
            'answer_roman_ur'     => $answerRomanUr,
            'keywords'            => $keywords,
            'possible_careers'    => $careers,
            'status'              => $status
        ]);

        if ($res['success']) {
            $msg = "Q&A record for intent '{$res['intent_id']}' saved successfully!";
            $actionSuccess = 'saved';

            if ($unansweredId > 0) {
                $uStmt = $db->prepare("UPDATE unanswered_queries SET status = 'resolved', notes = :notes WHERE id = :uid");
                $uStmt->execute([':notes' => "Resolved via Intent '{$res['intent_id']}'", ':uid' => $unansweredId]);
            }
        } else {
            $error = $res['error'] ?? 'Failed to save Q&A record.';
        }
    }

    // 2. Single Record Save (Row-level CRUD)
    elseif ($action === 'save_single_row') {
        $id         = (int) ($_POST['id'] ?? 0);
        $category   = trim($_POST['category'] ?? 'General');
        $intentId   = trim($_POST['intent_id'] ?? '');
        $language   = trim($_POST['language'] ?? 'en');
        $question   = trim($_POST['question'] ?? '');
        $variations = trim($_POST['question_variations'] ?? '');
        $answer     = trim($_POST['answer'] ?? '');
        $keywords   = trim($_POST['keywords'] ?? '');
        $careers    = trim($_POST['possible_careers'] ?? '');
        $status     = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if (empty($question) || empty($answer)) {
            $error = 'Question and Answer fields cannot be empty.';
        } else {
            if ($id > 0) {
                $stmt = $db->prepare("
                    UPDATE knowledge_base
                    SET category = :cat, intent_id = :intent, language = :lang, question = :q,
                        question_variations = :var, answer = :ans, keywords = :kw, possible_careers = :car,
                        status = :status, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':cat'    => $category,
                    ':intent' => !empty($intentId) ? $intentId : null,
                    ':lang'   => $language,
                    ':q'      => $question,
                    ':var'    => $variations,
                    ':ans'    => $answer,
                    ':kw'     => $keywords,
                    ':car'    => $careers,
                    ':status' => $status,
                    ':id'     => $id
                ]);
                $msg = "Database entry #{$id} updated successfully!";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO knowledge_base
                    (category, intent_id, language, question, question_variations, answer, keywords, possible_careers, status)
                    VALUES (:cat, :intent, :lang, :q, :var, :ans, :kw, :car, :status)
                ");
                $stmt->execute([
                    ':cat'    => $category,
                    ':intent' => !empty($intentId) ? $intentId : null,
                    ':lang'   => $language,
                    ':q'      => $question,
                    ':var'    => $variations,
                    ':ans'    => $answer,
                    ':kw'     => $keywords,
                    ':car'    => $careers,
                    ':status' => $status
                ]);
                $msg = "New Database entry #{$db->lastInsertId()} created successfully!";
            }
        }
    }

    // 3. Delete Unified Intent
    elseif ($action === 'delete_unified') {
        $intentId = trim($_POST['intent_id'] ?? '');
        if (!empty($intentId)) {
            $deleted = $qaService->deleteUnifiedRecord($intentId);
            $msg = "Intent '{$intentId}' and all its {$deleted} language entries were permanently deleted.";
            $actionSuccess = 'deleted';
        }
    }

    // 4. Delete Single DB Row
    elseif ($action === 'delete_single') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM knowledge_base WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $msg = "Knowledge Base record #{$id} was deleted.";
            $actionSuccess = 'deleted';
        }
    }

    // 5. Bulk Operations
    elseif ($action === 'bulk_action') {
        $bulkOp   = $_POST['bulk_op'] ?? '';
        $keysRaw  = trim($_POST['bulk_keys'] ?? '');
        $isIntent = ($_POST['view_mode'] ?? 'unified') === 'unified';
        $keys     = array_filter(array_map('trim', explode(',', $keysRaw)));

        if (!empty($keys)) {
            if ($bulkOp === 'delete') {
                $cnt = $qaService->bulkDelete($keys, $isIntent);
                $msg = "Successfully deleted {$cnt} records.";
                $actionSuccess = 'deleted';
            } elseif ($bulkOp === 'status_active') {
                $cnt = $qaService->bulkUpdateStatus($keys, 'active', $isIntent);
                $msg = "Set status to Active for {$cnt} records.";
            } elseif ($bulkOp === 'status_inactive') {
                $cnt = $qaService->bulkUpdateStatus($keys, 'inactive', $isIntent);
                $msg = "Set status to Inactive for {$cnt} records.";
            } elseif ($bulkOp === 'move_category') {
                $newCat = trim($_POST['bulk_new_category'] ?? '');
                if (!empty($newCat)) {
                    $cnt = $qaService->bulkMoveCategory($keys, $newCat, $isIntent);
                    $msg = "Moved {$cnt} records to category '{$newCat}'.";
                }
            }
        }
    }

    // 6. Import Q&A Dataset
    elseif ($action === 'import_dataset' && isset($_FILES['import_file'])) {
        $file = $_FILES['import_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $parsed = $qaService->parseImportFile($file['tmp_name'], $file['name']);
            if ($parsed['success']) {
                $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';
                $recordsToImport = array_merge($parsed['valid_new'], $overwrite ? $parsed['valid_update'] : []);
                $count = $qaService->executeImport($recordsToImport, $overwrite);
                $msg = "Successfully imported {$count} Q&A records!";
                if (!empty($parsed['invalid'])) {
                    $error = "Notice: " . count($parsed['invalid']) . " rows were skipped due to missing required fields.";
                }
            } else {
                $error = $parsed['error'] ?? 'Import failed.';
            }
        } else {
            $error = 'File upload error code: ' . $file['error'];
        }
    }

    // 7. Clear All Data (High Security Safeguard)
    elseif ($action === 'clear_all_data') {
        $confirmText = trim($_POST['confirm_clear_text'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';

        // Verify admin password
        $admin = getCurrentAdmin();
        $adminStmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id");
        $adminStmt->execute([':id' => $admin['id']]);
        $storedHash = $adminStmt->fetchColumn();

        if ($confirmText === 'CONFIRM CLEAR' && password_verify($adminPassword, (string)$storedHash)) {
            $db->exec("TRUNCATE TABLE knowledge_base");
            $msg = "All Knowledge Base data has been cleared.";
        } else {
            $error = "Authorization failed! Invalid confirmation text or administrator password. Data was NOT cleared.";
        }
    }
}

// Parameters
$viewMode       = trim($_GET['view'] ?? 'unified'); // 'unified' or 'rows'
$search         = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$langFilter     = trim($_GET['lang'] ?? '');
$sortOrder      = trim($_GET['sort'] ?? 'newest');
$perPage        = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25;
$page           = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$editIntent     = trim($_GET['edit_intent'] ?? '');
$editRowId      = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$viewIntent     = trim($_GET['view_intent'] ?? '');
$prefill        = trim($_GET['prefill'] ?? '');
$unansweredId   = isset($_GET['unanswered_id']) ? (int) $_GET['unanswered_id'] : 0;

// Fetch Categories for dropdown & datalist
$categories = $db->query("SELECT DISTINCT category FROM knowledge_base ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch Total Statistics
$stats = [
    'total_db_rows' => (int) $db->query("SELECT COUNT(*) FROM knowledge_base")->fetchColumn(),
    'active_rows'   => (int) $db->query("SELECT COUNT(*) FROM knowledge_base WHERE status = 'active'")->fetchColumn(),
    'intents_count' => (int) $db->query("SELECT COUNT(DISTINCT intent_id) FROM knowledge_base WHERE intent_id IS NOT NULL AND intent_id != ''")->fetchColumn(),
    'views_total'   => (int) $db->query("SELECT SUM(views_count) FROM knowledge_base")->fetchColumn()
];

// Load Edit Item if requested
$editUnified = null;
$editSingleRow = null;

if (!empty($editIntent)) {
    $editUnified = $qaService->getUnifiedRecord($editIntent);
}
if ($editRowId > 0) {
    $rStmt = $db->prepare("SELECT * FROM knowledge_base WHERE id = :id");
    $rStmt->execute([':id' => $editRowId]);
    $editSingleRow = $rStmt->fetch(PDO::FETCH_ASSOC);
}

// Load View Item for Detail Modal if requested
$quickViewRecord = null;
if (!empty($viewIntent)) {
    $quickViewRecord = $qaService->getUnifiedRecord($viewIntent);
}

// Query records based on view mode
if ($viewMode === 'rows') {
    // Row-by-Row Database View
    $where = [];
    $params = [];
    if ($categoryFilter !== '') {
        $where[] = "category = :cat";
        $params[':cat'] = $categoryFilter;
    }
    if ($statusFilter !== '') {
        $where[] = "status = :st";
        $params[':st'] = $statusFilter;
    }
    if ($langFilter !== '') {
        $where[] = "language = :lang";
        $params[':lang'] = $langFilter;
    }
    if ($search !== '') {
        $where[] = "(question LIKE :s1 OR answer LIKE :s2 OR keywords LIKE :s3 OR question_variations LIKE :s4 OR intent_id LIKE :s5)";
        $params[':s1'] = "%{$search}%";
        $params[':s2'] = "%{$search}%";
        $params[':s3'] = "%{$search}%";
        $params[':s4'] = "%{$search}%";
        $params[':s5'] = "%{$search}%";
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $orderSql = match($sortOrder) {
        'oldest' => 'ORDER BY id ASC',
        'category' => 'ORDER BY category ASC, id ASC',
        'views' => 'ORDER BY views_count DESC, id ASC',
        default => 'ORDER BY id DESC'
    };

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM knowledge_base {$whereSql}");
    $cntStmt->execute($params);
    $totalRecords = (int) $cntStmt->fetchColumn();
    $totalPages = $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 1;
    $offset = $perPage > 0 ? ($page - 1) * $perPage : 0;
    $limitSql = $perPage > 0 ? "LIMIT {$offset}, {$perPage}" : "";

    $listStmt = $db->prepare("SELECT * FROM knowledge_base {$whereSql} {$orderSql} {$limitSql}");
    $listStmt->execute($params);
    $rowsList = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    $displayItems = $rowsList;
} else {
    // Unified Intent View (Default)
    $listResult = $qaService->getUnifiedList([
        'search'   => $search,
        'category' => $categoryFilter,
        'status'   => $statusFilter,
        'lang'     => $langFilter,
        'sort'     => $sortOrder
    ], $page, $perPage);

    $totalRecords = $listResult['total'];
    $totalPages = $listResult['total_pages'];
    $displayItems = $listResult['records'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Q&A Management - PFTP Chatbot Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Asap+Condensed:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Oswald:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/chatbot/admin/assets/css/admin.css') ?>">
</head>
<body>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="admin-toast-container"></div>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 class="admin-page-title" style="margin-bottom: 2px;">Query &amp; Q&amp;A Management</h1>
        <div style="font-size: 12.5px; color: #64748b;">
          Central Knowledge Base &bull; <?= number_format($stats['intents_count']) ?> Unique Intents &bull; <?= number_format($stats['total_db_rows']) ?> Trilingual Records
        </div>
      </div>

      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <!-- Add Q&A Button -->
        <button type="button" class="admin-btn admin-btn-primary" onclick="openCreateModal();">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add New Q&amp;A
        </button>

        <!-- Import Button -->
        <button type="button" class="admin-btn admin-btn-secondary" onclick="openModal('modal-import');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Import
        </button>

        <!-- Export Dropdown -->
        <div class="admin-dropdown" id="export-dropdown">
          <button type="button" class="admin-btn admin-btn-secondary" onclick="toggleDropdown('export-dropdown')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export &#9662;
          </button>
          <div class="admin-dropdown-menu">
            <a href="knowledge-base.php?export=xlsx&scope=all" class="admin-dropdown-item">
              <span style="color: #10b981; font-weight: 700;">.XLSX</span> Export All Records
            </a>
            <a href="knowledge-base.php?export=xlsx&scope=filtered&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>" class="admin-dropdown-item">
              <span style="color: #10b981; font-weight: 700;">.XLSX</span> Export Filtered Results
            </a>
            <div class="admin-dropdown-divider"></div>
            <a href="knowledge-base.php?export=docx&scope=all" class="admin-dropdown-item">
              <span style="color: #2563eb; font-weight: 700;">.DOCX</span> Export All (Word)
            </a>
            <a href="knowledge-base.php?export=docx&scope=filtered&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>" class="admin-dropdown-item">
              <span style="color: #2563eb; font-weight: 700;">.DOCX</span> Export Filtered (Word)
            </a>
            <div class="admin-dropdown-divider"></div>
            <a href="knowledge-base.php?export=sql" class="admin-dropdown-item">
              <span style="color: #f59e0b; font-weight: 700;">.SQL</span> Download Database Backup
            </a>
          </div>
        </div>

      </div>
    </div>

    <div class="admin-content">

      <?php if (!empty($msg)): ?>
        <div class="admin-alert admin-alert-success">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <div><?= htmlspecialchars($msg) ?></div>
        </div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="admin-alert admin-alert-danger">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <!-- Filter / Search Control Card -->
      <div class="admin-card">
        <div class="admin-card-body" style="padding: 16px 24px;">
          <form method="GET" action="knowledge-base.php" id="filter-form" class="admin-filter-bar">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

            <!-- Search input with clear button -->
            <div class="admin-search-wrapper">
              <input
                type="text"
                id="search-input"
                name="search"
                class="admin-form-control"
                placeholder="Search question, answer, keywords, intent..."
                value="<?= htmlspecialchars($search) ?>"
                oninput="toggleClearSearch(this.value);"
              >
              <button
                type="button"
                id="search-clear-btn"
                class="admin-search-clear <?= !empty($search) ? 'visible' : '' ?>"
                onclick="clearSearchInput();"
                title="Clear Search"
              >&times;</button>
            </div>

            <!-- Category Filter -->
            <select name="category" class="admin-form-control" style="max-width: 200px;">
              <option value="">All Categories (<?= count($categories) ?>)</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- Language Filter -->
            <select name="lang" class="admin-form-control" style="max-width: 140px;">
              <option value="">All Languages</option>
              <option value="en" <?= $langFilter === 'en' ? 'selected' : '' ?>>English</option>
              <option value="ur" <?= $langFilter === 'ur' ? 'selected' : '' ?>>Urdu (اردو)</option>
              <option value="roman_ur" <?= $langFilter === 'roman_ur' ? 'selected' : '' ?>>Roman Urdu</option>
            </select>

            <!-- Status Filter -->
            <select name="status" class="admin-form-control" style="max-width: 130px;">
              <option value="">All Status</option>
              <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <!-- Sorting -->
            <select name="sort" class="admin-form-control" style="max-width: 140px;">
              <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
              <option value="category" <?= $sortOrder === 'category' ? 'selected' : '' ?>>Category A-Z</option>
              <option value="views" <?= $sortOrder === 'views' ? 'selected' : '' ?>>Most Views</option>
            </select>

            <!-- Records Per Page -->
            <select name="per_page" class="admin-form-control" style="max-width: 110px;" onchange="this.form.submit();">
              <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / page</option>
              <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / page</option>
              <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / page</option>
              <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 / page</option>
              <option value="0" <?= $perPage === 0 ? 'selected' : '' ?>>View All</option>
            </select>

            <button type="submit" class="admin-btn admin-btn-primary">Apply</button>

            <?php if (!empty($search) || !empty($categoryFilter) || !empty($statusFilter) || !empty($langFilter) || $sortOrder !== 'newest'): ?>
              <a href="knowledge-base.php?view=<?= urlencode($viewMode) ?>" class="admin-btn admin-btn-secondary" title="Reset all active filters">
                Clear Filters
              </a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Dataset Table Card -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <h2 class="admin-card-title">
              <?= $viewMode === 'unified' ? 'Unified Intent Q&amp;A Records' : 'Individual Database Records' ?>
              <span style="font-size: 13px; font-weight: 500; color: #64748b; margin-left: 6px;">
                (Showing <?= count($displayItems) ?> of <?= $totalRecords ?> total)
              </span>
            </h2>

            <!-- View Mode Switcher -->
            <div class="admin-view-toggle">
              <a href="knowledge-base.php?view=unified<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="admin-view-btn <?= $viewMode === 'unified' ? 'active' : '' ?>">
                Grouped Intent View (<?= $stats['intents_count'] ?>)
              </a>
              <a href="knowledge-base.php?view=rows<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="admin-view-btn <?= $viewMode === 'rows' ? 'active' : '' ?>">
                All DB Rows (<?= $stats['total_db_rows'] ?>)
              </a>
            </div>
          </div>

          <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyTableText();" title="Copy visible rows as formatted text">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
              Copy Table
            </button>
          </div>
        </div>

        <div class="admin-table-responsive">
          <table class="admin-table" id="qa-data-table">
            <thead>
              <tr>
                <th style="width: 38px; text-align: center;">
                  <input type="checkbox" id="select-all-box" onchange="toggleSelectAll(this.checked);" title="Select All">
                </th>
                <th style="width: 70px;">ID / Intent</th>
                <th>Category</th>
                <th>Main Question &amp; Variations</th>
                <th>Trilingual Answers Preview</th>
                <th style="width: 80px;">Status</th>
                <th style="width: 60px;">Views</th>
                <th style="width: 160px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($displayItems)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; color: #94a3b8; padding: 48px 24px;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                    <div style="font-size: 15px; font-weight: 600; color: #475569;">No Q&amp;A records found matching your filters.</div>
                    <div style="font-size: 13px; margin-top: 4px;">Try searching with different keywords or reset your filters.</div>
                    <a href="knowledge-base.php?view=<?= urlencode($viewMode) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="margin-top: 14px;">
                      Reset All Filters
                    </a>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($displayItems as $item): ?>
                  <?php
                    $isUnified = $viewMode === 'unified';
                    $rowKey = $isUnified ? ($item['intent_id'] ?? '') : (string)($item['id'] ?? '');
                    $primaryId = $isUnified ? ($item['primary_id'] ?? 0) : (int)($item['id'] ?? 0);
                    $intentSlug = $item['intent_id'] ?? 'general';
                    $mainQuestion = $isUnified ? ($item['main_question'] ?? '') : ($item['question'] ?? '');
                    $variations = $item['question_variations'] ?? '';
                    $varCount = !empty($variations) ? count(explode('|', $variations)) : 0;
                    $status = $item['status'] ?? 'active';
                    $views = (int)($item['views_count'] ?? 0);
                  ?>
                  <tr data-row-key="<?= htmlspecialchars($rowKey) ?>" id="row-<?= htmlspecialchars($rowKey) ?>">
                    <!-- Checkbox -->
                    <td style="text-align: center;">
                      <input type="checkbox" class="row-checkbox" value="<?= htmlspecialchars($rowKey) ?>" onchange="updateBulkDockState();">
                    </td>

                    <!-- ID & Intent -->
                    <td>
                      <div style="font-weight: 700; color: #0f172a;">#<?= $primaryId ?></div>
                      <span class="admin-badge gray" style="font-size: 10px; font-family: monospace; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($intentSlug) ?>">
                        <?= htmlspecialchars($intentSlug) ?>
                      </span>
                    </td>

                    <!-- Category -->
                    <td>
                      <span class="admin-badge info" style="font-size: 11px;">
                        <?= htmlspecialchars($item['category']) ?>
                      </span>
                      <?php if (!$isUnified): ?>
                        <div style="margin-top: 4px;">
                          <span class="admin-badge <?= $item['language'] === 'ur' ? 'warning' : ($item['language'] === 'roman_ur' ? 'purple' : 'gray') ?>" style="font-size: 10px;">
                            <?= strtoupper($item['language']) ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Question & Variations -->
                    <td style="max-width: 320px;">
                      <div style="font-weight: 600; line-height: 1.35; color: #1e293b;">
                        <?= htmlspecialchars($mainQuestion) ?>
                      </div>
                      <?php if ($varCount > 0): ?>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                          <span class="admin-badge gray" style="font-size: 10px; padding: 1px 6px;">
                            <?= $varCount ?> variation<?= $varCount > 1 ? 's' : '' ?>
                          </span>
                          <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 220px;" title="<?= htmlspecialchars($variations) ?>">
                            <?= htmlspecialchars(mb_substr($variations, 0, 45)) ?>...
                          </span>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($item['keywords'])): ?>
                        <div style="font-size: 11px; color: #07613F; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 280px;" title="<?= htmlspecialchars($item['keywords']) ?>">
                          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                          <?= htmlspecialchars(mb_substr($item['keywords'], 0, 50)) ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Trilingual Answers Preview -->
                    <td style="max-width: 360px;">
                      <?php if ($isUnified): ?>
                        <!-- English Preview -->
                        <?php if (!empty($item['answer_en'])): ?>
                          <div style="font-size: 12px; color: #334155; line-height: 1.35; margin-bottom: 4px;">
                            <strong style="color: #0284c7; font-size: 11px;">EN:</strong> <?= htmlspecialchars(mb_substr($item['answer_en'], 0, 90)) ?>...
                          </div>
                        <?php endif; ?>
                        <!-- Urdu Preview -->
                        <?php if (!empty($item['answer_ur'])): ?>
                          <div class="urdu-text" style="font-size: 12px; color: #78350f; line-height: 1.5; margin-bottom: 4px;">
                            <strong style="font-family: var(--font-family); color: #b45309; font-size: 10px;">UR:</strong> <?= htmlspecialchars(mb_substr($item['answer_ur'], 0, 75)) ?>...
                          </div>
                        <?php endif; ?>
                        <!-- Roman Urdu Preview -->
                        <?php if (!empty($item['answer_roman_ur'])): ?>
                          <div style="font-size: 11.5px; color: #475569; line-height: 1.35;">
                            <strong style="color: #7e22ce; font-size: 10px;">ROMAN:</strong> <?= htmlspecialchars(mb_substr($item['answer_roman_ur'], 0, 80)) ?>...
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <div style="font-size: 12.5px; line-height: 1.45; <?= $item['language'] === 'ur' ? 'font-family: \'Noto Nastaliq Urdu\', serif; direction: rtl;' : '' ?>">
                          <?= htmlspecialchars(mb_substr($item['answer'], 0, 160)) ?>...
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td>
                      <span class="admin-badge <?= $status === 'active' ? 'success' : 'gray' ?>">
                        <?= ucfirst($status) ?>
                      </span>
                    </td>

                    <!-- Views -->
                    <td>
                      <span style="font-size: 12.5px; color: #64748b; font-weight: 600;">
                        <?= number_format($views) ?>
                      </span>
                    </td>

                    <!-- Actions -->
                    <td style="text-align: right; white-space: nowrap;">
                      <div style="display: inline-flex; gap: 4px; align-items: center;">
                        <!-- Quick View Button -->
                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="openQuickView('<?= htmlspecialchars($intentSlug) ?>');" title="Inspect Full Record">
                          View
                        </button>

                        <!-- Edit Button -->
                        <?php if ($isUnified): ?>
                          <a href="knowledge-base.php?view=unified&edit_intent=<?= urlencode($intentSlug) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            Edit
                          </a>
                        <?php else: ?>
                          <a href="knowledge-base.php?view=rows&edit_id=<?= $item['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                            Edit
                          </a>
                        <?php endif; ?>

                        <!-- Copy Dropdown -->
                        <div class="admin-dropdown" id="copy-dd-<?= htmlspecialchars($rowKey) ?>">
                          <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="toggleDropdown('copy-dd-<?= htmlspecialchars($rowKey) ?>')" title="Copy Options">
                            Copy &#9662;
                          </button>
                          <div class="admin-dropdown-menu">
                            <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($mainQuestion)) ?>', 'Main Question copied!')">
                              Copy Main Question
                            </button>
                            <?php if (!empty($variations)): ?>
                              <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($variations)) ?>', 'Question Variations copied!')">
                                Copy Variations
                              </button>
                            <?php endif; ?>
                            <?php if ($isUnified): ?>
                              <?php if (!empty($item['answer_en'])): ?>
                                <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($item['answer_en'])) ?>', 'English Answer copied!')">
                                  Copy English Answer
                                </button>
                              <?php endif; ?>
                              <?php if (!empty($item['answer_ur'])): ?>
                                <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($item['answer_ur'])) ?>', 'Urdu Answer copied!')">
                                  Copy Urdu Answer
                                </button>
                              <?php endif; ?>
                              <?php if (!empty($item['answer_roman_ur'])): ?>
                                <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($item['answer_roman_ur'])) ?>', 'Roman Urdu Answer copied!')">
                                  Copy Roman Urdu Answer
                                </button>
                              <?php endif; ?>
                            <?php else: ?>
                              <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($item['answer'])) ?>', 'Answer copied!')">
                                Copy Answer
                              </button>
                            <?php endif; ?>
                            <?php if (!empty($item['keywords'])): ?>
                              <button type="button" class="admin-dropdown-item" onclick="copyText('<?= htmlspecialchars(addslashes($item['keywords'])) ?>', 'Keywords copied!')">
                                Copy Keywords
                              </button>
                            <?php endif; ?>
                            <div class="admin-dropdown-divider"></div>
                            <button type="button" class="admin-dropdown-item" style="font-weight: 600; color: #07613F;" onclick="copyFullRecord('<?= htmlspecialchars($rowKey) ?>')">
                              Copy Complete Q&amp;A
                            </button>
                          </div>
                        </div>

                        <!-- Delete Button -->
                        <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="confirmDeleteRecord('<?= htmlspecialchars($rowKey) ?>', '<?= htmlspecialchars(addslashes($mainQuestion)) ?>', <?= $isUnified ? 'true' : 'false' ?>);" title="Delete Record">
                          Del
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination & Footer Information -->
        <div class="admin-card-header" style="background: #f8fafc; border-top: 1px solid var(--admin-border); padding: 12px 24px;">
          <div style="font-size: 13px; color: #64748b;">
            Showing <?= count($displayItems) ?> of <?= $totalRecords ?> records (Page <?= $page ?> of <?= max(1, $totalPages) ?>)
          </div>

          <?php if ($totalPages > 1): ?>
            <div style="display: flex; gap: 4px; align-items: center;">
              <?php if ($page > 1): ?>
                <a href="?view=<?= urlencode($viewMode) ?>&page=1&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>&sort=<?= urlencode($sortOrder) ?>&per_page=<?= $perPage ?>" class="admin-btn admin-btn-secondary admin-btn-sm">&laquo; First</a>
                <a href="?view=<?= urlencode($viewMode) ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>&sort=<?= urlencode($sortOrder) ?>&per_page=<?= $perPage ?>" class="admin-btn admin-btn-secondary admin-btn-sm">&lsaquo; Prev</a>
              <?php endif; ?>

              <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
              ?>
                <a href="?view=<?= urlencode($viewMode) ?>&page=<?= $p ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>&sort=<?= urlencode($sortOrder) ?>&per_page=<?= $perPage ?>" class="admin-btn <?= $p === $page ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <a href="?view=<?= urlencode($viewMode) ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>&sort=<?= urlencode($sortOrder) ?>&per_page=<?= $perPage ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Next &rsaquo;</a>
                <a href="?view=<?= urlencode($viewMode) ?>&page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>&sort=<?= urlencode($sortOrder) ?>&per_page=<?= $perPage ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Last &raquo;</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- Destructive Safety Zone (Clear All Data) -->
      <div style="margin-top: 40px; padding: 20px 24px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
        <div>
          <div style="font-weight: 700; color: #9f1239; font-size: 14.5px;">Advanced Administrative Recovery &amp; Reset Zone</div>
          <div style="font-size: 12.5px; color: #be123c;">
            Download complete SQL backups anytime. Clear All Data is protected by strict administrator confirmation and password verification.
          </div>
        </div>
        <div style="display: flex; gap: 10px;">
          <a href="knowledge-base.php?export=sql" class="admin-btn admin-btn-secondary admin-btn-sm">
            Download SQL Backup (.sql)
          </a>
          <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="openModal('modal-clear-all');">
            Clear All Data...
          </button>
        </div>
      </div>

    </div>
  </main>

  <!-- =================================================================== -->
  <!-- FLOATING BULK ACTION DOCK -->
  <!-- =================================================================== -->
  <div id="bulk-dock" class="admin-bulk-dock">
    <div class="admin-bulk-badge" id="bulk-selected-count">0 selected</div>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copySelectedRecords();" style="background: #1e293b; color: #ffffff; border-color: #334155;">
      Copy Selected
    </button>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="exportSelected('xlsx');" style="background: #1e293b; color: #ffffff; border-color: #334155;">
      Export .XLSX
    </button>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="exportSelected('docx');" style="background: #1e293b; color: #ffffff; border-color: #334155;">
      Export .DOCX
    </button>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="openBulkMoveModal();" style="background: #1e293b; color: #ffffff; border-color: #334155;">
      Move Category
    </button>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="executeBulkStatus('active');" style="background: #1e293b; color: #22c55e; border-color: #334155;">
      Set Active
    </button>

    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="executeBulkStatus('inactive');" style="background: #1e293b; color: #cbd5e1; border-color: #334155;">
      Set Inactive
    </button>

    <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="confirmBulkDelete();">
      Delete Selected
    </button>

    <button type="button" class="admin-btn-logout" onclick="clearBulkSelection();" title="Deselect All" style="color: #94a3b8; font-size: 16px; margin-left: 6px;">
      &times;
    </button>
  </div>

  <!-- Form for executing bulk actions -->
  <form id="bulk-action-form" method="POST" action="knowledge-base.php" style="display:none;">
    <input type="hidden" name="action" value="bulk_action">
    <input type="hidden" name="view_mode" value="<?= htmlspecialchars($viewMode) ?>">
    <input type="hidden" name="bulk_op" id="bulk-op-input" value="">
    <input type="hidden" name="bulk_keys" id="bulk-keys-input" value="">
    <input type="hidden" name="bulk_new_category" id="bulk-category-input" value="">
  </form>

  <!-- =================================================================== -->
  <!-- MODAL: UNIFIED Q&A CREATE / EDIT -->
  <!-- =================================================================== -->
  <div id="modal-unified-form" class="admin-modal-backdrop <?= ($editUnified || !empty($prefill)) ? 'active' : '' ?>">
    <div class="admin-modal-content admin-modal-lg">
      <div class="admin-modal-header">
        <h3 class="admin-modal-title">
          <?= $editUnified ? "Edit Q&amp;A Record (Intent: " . htmlspecialchars($editUnified['intent_id']) . ")" : "Add New Unified Q&amp;A Entry" ?>
        </h3>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-unified-form'); window.location.href='knowledge-base.php?view=<?= urlencode($viewMode) ?>';">&times;</button>
      </div>

      <form method="POST" action="knowledge-base.php" id="unified-qa-form">
        <input type="hidden" name="action" value="save_unified">
        <?php if ($editUnified): ?>
          <input type="hidden" name="original_intent_id" value="<?= htmlspecialchars($editUnified['intent_id']) ?>">
        <?php endif; ?>
        <?php if ($unansweredId > 0): ?>
          <input type="hidden" name="unanswered_id" value="<?= $unansweredId ?>">
        <?php endif; ?>

        <div class="admin-modal-body">
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
            <div class="admin-form-group">
              <label class="admin-form-label">Intent Identifier (Slug)</label>
              <input
                type="text"
                name="intent_id"
                id="form-intent-id"
                class="admin-form-control"
                placeholder="e.g. digital_marketing_course"
                value="<?= htmlspecialchars($editUnified['intent_id'] ?? '') ?>"
                required
              >
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Category</label>
              <input
                type="text"
                name="category"
                class="admin-form-control"
                list="cat-datalist"
                required
                placeholder="Select or enter category..."
                value="<?= htmlspecialchars($editUnified['category'] ?? 'Digital Marketing') ?>"
              >
              <datalist id="cat-datalist">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>

            <div class="admin-form-group">
              <label class="admin-form-label">Status</label>
              <select name="status" class="admin-form-control">
                <option value="active" <?= ($editUnified['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible to Bot)</option>
                <option value="inactive" <?= ($editUnified['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
              </select>
            </div>
          </div>

          <!-- Main Question -->
          <div class="admin-form-group">
            <label class="admin-form-label">Main Question (Primary Inquiry)</label>
            <input
              type="text"
              name="main_question"
              id="form-main-q"
              class="admin-form-control"
              required
              placeholder="e.g. Why should I take the Digital Marketing course?"
              value="<?= htmlspecialchars($editUnified['main_question'] ?? $prefill) ?>"
              onblur="autoGenerateIntentSlug();"
            >
          </div>

          <!-- Question Variations -->
          <div class="admin-form-group">
            <label class="admin-form-label">
              Alternative Questions / Variations
              <span style="font-weight: 400; color: #64748b; font-size: 11.5px;">(One question per line, or pipe <code>|</code> separated)</span>
            </label>
            <textarea
              name="question_variations"
              class="admin-form-control"
              style="min-height: 80px;"
              placeholder="What do you teach in digital marketing?&#10;Is digital marketing good for beginners?&#10;Digital marketing course syllabus"
            ><?= htmlspecialchars(str_replace('|', "\n", $editUnified['question_variations'] ?? '')) ?></textarea>
          </div>

          <!-- Trilingual Answer Tabs -->
          <div style="margin-top: 18px; margin-bottom: 6px;">
            <div class="admin-tab-nav">
              <button type="button" class="admin-tab-btn active" onclick="switchFormTab('tab-ans-en', this);">
                🇬🇧 English Answer
              </button>
              <button type="button" class="admin-tab-btn" onclick="switchFormTab('tab-ans-ur', this);">
                🇵🇰 Urdu Answer (اردو)
              </button>
              <button type="button" class="admin-tab-btn" onclick="switchFormTab('tab-ans-roman', this);">
                💬 Roman Urdu Answer
              </button>
            </div>

            <!-- Tab: English -->
            <div id="tab-ans-en" class="admin-tab-pane active">
              <div class="admin-form-group">
                <label class="admin-form-label">English Answer (Supports bold **text** and bullet lines)</label>
                <textarea
                  name="answer_en"
                  class="admin-form-control"
                  style="min-height: 130px;"
                  placeholder="Provide comprehensive English response..."
                ><?= htmlspecialchars($editUnified['answer_en'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Tab: Urdu -->
            <div id="tab-ans-ur" class="admin-tab-pane">
              <div class="admin-form-group">
                <label class="admin-form-label">Urdu Question (اختیاری سوال)</label>
                <input
                  type="text"
                  name="question_ur"
                  class="admin-form-control urdu-text"
                  dir="rtl"
                  placeholder="مثلاً: ڈیجیٹل مارکیٹنگ کا کورس کیوں کرنا چاہیے؟"
                  value="<?= htmlspecialchars($editUnified['question_ur'] ?? '') ?>"
                >
              </div>
              <div class="admin-form-group">
                <label class="admin-form-label">Urdu Answer (مکمل اردو جواب)</label>
                <textarea
                  name="answer_ur"
                  class="admin-form-control urdu-text"
                  dir="rtl"
                  style="min-height: 130px;"
                  placeholder="اردو زبان میں تفصیلی اور مستند جواب درج کریں..."
                ><?= htmlspecialchars($editUnified['answer_ur'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Tab: Roman Urdu -->
            <div id="tab-ans-roman" class="admin-tab-pane">
              <div class="admin-form-group">
                <label class="admin-form-label">Roman Urdu Question (Optional)</label>
                <input
                  type="text"
                  name="question_roman_ur"
                  class="admin-form-control"
                  placeholder="e.g. Aap ko digital marketing course kyun karna chahiye?"
                  value="<?= htmlspecialchars($editUnified['question_roman_ur'] ?? '') ?>"
                >
              </div>
              <div class="admin-form-group">
                <label class="admin-form-label">Roman Urdu Answer (Roman Script)</label>
                <textarea
                  name="answer_roman_ur"
                  class="admin-form-control"
                  style="min-height: 130px;"
                  placeholder="Roman Urdu mein aasan aur wazeh jawab enter karein..."
                ><?= htmlspecialchars($editUnified['answer_roman_ur'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <!-- Keywords -->
            <div class="admin-form-group">
              <label class="admin-form-label">Keywords / Tags (Comma Separated)</label>
              <input
                type="text"
                name="keywords"
                class="admin-form-control"
                placeholder="digital marketing, smm, meta ads, google ads"
                value="<?= htmlspecialchars($editUnified['keywords'] ?? '') ?>"
              >
            </div>

            <!-- Possible Careers -->
            <div class="admin-form-group">
              <label class="admin-form-label">Career Directions (Optional, Semicolon <code>;</code> Separated)</label>
              <input
                type="text"
                name="possible_careers"
                class="admin-form-control"
                placeholder="Social Media Manager; Meta Ads Specialist; Freelancer"
                value="<?= htmlspecialchars($editUnified['possible_careers'] ?? '') ?>"
              >
            </div>
          </div>
        </div>

        <div class="admin-modal-footer">
          <button type="button" class="admin-btn admin-btn-secondary" onclick="resetForm('unified-qa-form');" title="Clear unsaved form inputs">
            Clear Form
          </button>
          <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-unified-form');">
            Cancel
          </button>
          <button type="submit" class="admin-btn admin-btn-primary">
            <?= $editUnified ? 'Save Changes' : 'Create Q&amp;A Record' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- MODAL: QUICK VIEW / DETAIL INSPECTION -->
  <!-- =================================================================== -->
  <div id="modal-quick-view" class="admin-modal-backdrop <?= $quickViewRecord ? 'active' : '' ?>">
    <div class="admin-modal-content admin-modal-lg">
      <div class="admin-modal-header">
        <div>
          <h3 class="admin-modal-title" id="view-title">
            Q&amp;A Inspection &bull; Intent: <span id="view-intent-badge" style="color: #07613F;"><?= htmlspecialchars($quickViewRecord['intent_id'] ?? '') ?></span>
          </h3>
          <div style="font-size: 12px; color: #64748b; margin-top: 2px;" id="view-meta-info">
            Category: <strong><?= htmlspecialchars($quickViewRecord['category'] ?? '') ?></strong> &bull;
            Status: <span class="admin-badge <?= ($quickViewRecord['status'] ?? '') === 'active' ? 'success' : 'gray' ?>"><?= ucfirst($quickViewRecord['status'] ?? 'active') ?></span> &bull;
            Created: <?= htmlspecialchars($quickViewRecord['created_at'] ?? 'N/A') ?>
          </div>
        </div>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-quick-view');">&times;</button>
      </div>

      <div class="admin-modal-body" id="view-modal-body">
        <?php if ($quickViewRecord): ?>
          <!-- Question Box -->
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
              <div>
                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b;">Primary Question</div>
                <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                  <?= htmlspecialchars($quickViewRecord['main_question']) ?>
                </div>
              </div>
              <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['main_question'])) ?>', 'Question copied!');">
                Copy
              </button>
            </div>

            <?php if (!empty($quickViewRecord['question_variations'])): ?>
              <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                  <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Alternative Phrasings / Variations</span>
                  <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['question_variations'])) ?>', 'Variations copied!');">
                    Copy Variations
                  </button>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                  <?php foreach (explode('|', $quickViewRecord['question_variations']) as $v): ?>
                    <?php if (trim($v) !== ''): ?>
                      <span class="admin-badge gray" style="font-size: 11px; font-weight: 500; padding: 3px 8px;">
                        &bull; <?= htmlspecialchars(trim($v)) ?>
                      </span>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Answers Tab Navigation -->
          <div class="admin-tab-nav">
            <button type="button" class="admin-tab-btn active" onclick="switchViewTab('view-ans-en', this);">
              English Answer
            </button>
            <button type="button" class="admin-tab-btn" onclick="switchViewTab('view-ans-ur', this);">
              Urdu Answer (اردو)
            </button>
            <button type="button" class="admin-tab-btn" onclick="switchViewTab('view-ans-roman', this);">
              Roman Urdu Answer
            </button>
          </div>

          <!-- Tab Content: English -->
          <div id="view-ans-en" class="admin-tab-pane active" style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <span style="font-size: 11px; font-weight: 700; color: #0284c7; text-transform: uppercase;">English Response Text</span>
              <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['answer_en'])) ?>', 'English Answer copied!');">
                Copy English
              </button>
            </div>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 18px; font-size: 13.5px; line-height: 1.6; color: #1e293b; white-space: pre-wrap;">
              <?= !empty($quickViewRecord['answer_en']) ? htmlspecialchars($quickViewRecord['answer_en']) : '<span style="color:#94a3b8; font-style:italic;">No English answer registered for this intent.</span>' ?>
            </div>
          </div>

          <!-- Tab Content: Urdu -->
          <div id="view-ans-ur" class="admin-tab-pane" style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <span style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase;">اردو جواب (Nastaliq Typography)</span>
              <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['answer_ur'])) ?>', 'Urdu Answer copied!');">
                Copy Urdu
              </button>
            </div>
            <div class="urdu-text" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px 20px; font-size: 15px; line-height: 2; color: #0f172a; white-space: pre-wrap;">
              <?= !empty($quickViewRecord['answer_ur']) ? htmlspecialchars($quickViewRecord['answer_ur']) : '<span style="color:#94a3b8; font-style:italic;">اس انٹینٹ کے لیے کوئی اردو جواب دستیاب نہیں ہے۔</span>' ?>
            </div>
          </div>

          <!-- Tab Content: Roman Urdu -->
          <div id="view-ans-roman" class="admin-tab-pane" style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
              <span style="font-size: 11px; font-weight: 700; color: #7e22ce; text-transform: uppercase;">Roman Urdu Response Text</span>
              <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['answer_roman_ur'])) ?>', 'Roman Urdu Answer copied!');">
                Copy Roman Urdu
              </button>
            </div>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 18px; font-size: 13.5px; line-height: 1.6; color: #1e293b; white-space: pre-wrap;">
              <?= !empty($quickViewRecord['answer_roman_ur']) ? htmlspecialchars($quickViewRecord['answer_roman_ur']) : '<span style="color:#94a3b8; font-style:italic;">No Roman Urdu answer registered for this intent.</span>' ?>
            </div>
          </div>

          <!-- Keywords & Meta Tags -->
          <div style="padding: 10px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="font-size: 12.5px; color: #475569;">
              <strong>Keywords:</strong> <?= htmlspecialchars($quickViewRecord['keywords'] ?? 'None') ?>
            </div>
            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($quickViewRecord['keywords'] ?? '')) ?>', 'Keywords copied!');">
              Copy Keywords
            </button>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 30px; color: #94a3b8;">Loading record details...</div>
        <?php endif; ?>
      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-quick-view');">Close</button>
        <?php if ($quickViewRecord): ?>
          <a href="knowledge-base.php?view=unified&edit_intent=<?= urlencode($quickViewRecord['intent_id']) ?>" class="admin-btn admin-btn-primary">
            Edit This Record
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- MODAL: DATASET IMPORT (.xlsx / .csv) -->
  <!-- =================================================================== -->
  <div id="modal-import" class="admin-modal-backdrop">
    <div class="admin-modal-content">
      <div class="admin-modal-header">
        <h3 class="admin-modal-title">Import Q&amp;A Knowledge Dataset</h3>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-import');">&times;</button>
      </div>

      <form method="POST" action="knowledge-base.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="import_dataset">

        <div class="admin-modal-body">
          <div style="font-size: 13.5px; color: #475569; margin-bottom: 16px; line-height: 1.5;">
            Upload a structured <strong>.xlsx</strong> (Excel) or <strong>.csv</strong> file to add or update Q&amp;A knowledge base records.
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Select File (.xlsx or .csv)</label>
            <input type="file" name="import_file" class="admin-form-control" accept=".xlsx,.csv" required>
          </div>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-size: 12px; color: #475569;">
            <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Expected Columns in File:</div>
            <code>Intent</code>, <code>Category</code>, <code>Main Question</code>, <code>Variations</code>, <code>English Answer</code>, <code>Urdu Answer</code>, <code>Roman Urdu Answer</code>, <code>Keywords</code>
          </div>

          <div class="admin-form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" name="overwrite_existing" value="1" checked>
              <span><strong>Update existing records</strong> if Intent ID already exists in the database.</span>
            </label>
          </div>
        </div>

        <div class="admin-modal-footer">
          <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-import');">Cancel</button>
          <button type="submit" class="admin-btn admin-btn-primary">Upload &amp; Import</button>
        </div>
      </form>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- MODAL: BULK MOVE CATEGORY -->
  <!-- =================================================================== -->
  <div id="modal-bulk-category" class="admin-modal-backdrop">
    <div class="admin-modal-content" style="max-width: 480px;">
      <div class="admin-modal-header">
        <h3 class="admin-modal-title">Move Selected Records to Category</h3>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-bulk-category');">&times;</button>
      </div>
      <div class="admin-modal-body">
        <div class="admin-form-group">
          <label class="admin-form-label">Target Category</label>
          <input type="text" id="bulk-target-category-input" class="admin-form-control" list="cat-datalist" placeholder="Select or type category name...">
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-bulk-category');">Cancel</button>
        <button type="button" class="admin-btn admin-btn-primary" onclick="submitBulkMove();">Move Category</button>
      </div>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- MODAL: DELETE CONFIRMATION -->
  <!-- =================================================================== -->
  <div id="modal-delete-confirm" class="admin-modal-backdrop">
    <div class="admin-modal-content" style="max-width: 500px;">
      <div class="admin-modal-header" style="border-bottom-color: #fee2e2;">
        <h3 class="admin-modal-title" style="color: #b91c1c;">Confirm Permanent Deletion</h3>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-delete-confirm');">&times;</button>
      </div>
      <form method="POST" action="knowledge-base.php" id="delete-form">
        <input type="hidden" name="action" id="delete-action-input" value="delete_unified">
        <input type="hidden" name="intent_id" id="delete-intent-input" value="">
        <input type="hidden" name="id" id="delete-id-input" value="">

        <div class="admin-modal-body">
          <div style="font-size: 14px; color: #334155; line-height: 1.5; margin-bottom: 12px;">
            Are you sure you want to permanently delete:
            <div style="font-weight: 700; color: #0f172a; margin-top: 6px; padding: 8px 12px; background: #f1f5f9; border-radius: 6px;" id="delete-item-label">
              ...
            </div>
          </div>
          <div style="font-size: 12.5px; color: #be123c;">
            <strong>Warning:</strong> Deleting an intent removes all linked English, Urdu, and Roman Urdu answer records and variations. This action cannot be undone.
          </div>
        </div>
        <div class="admin-modal-footer">
          <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-delete-confirm');">Cancel</button>
          <button type="submit" class="admin-btn admin-btn-danger">Permanently Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- MODAL: CLEAR ALL DATA SAFEGUARD -->
  <!-- =================================================================== -->
  <div id="modal-clear-all" class="admin-modal-backdrop">
    <div class="admin-modal-content" style="max-width: 540px;">
      <div class="admin-modal-header" style="background: #fff1f2; border-bottom: 1px solid #fecdd3;">
        <h3 class="admin-modal-title" style="color: #9f1239;">⚠️ High-Security Action: Clear All Q&amp;A Data</h3>
        <button type="button" class="admin-btn-logout" onclick="closeModal('modal-clear-all');">&times;</button>
      </div>
      <form method="POST" action="knowledge-base.php">
        <input type="hidden" name="action" value="clear_all_data">

        <div class="admin-modal-body">
          <div style="font-size: 13.5px; color: #9f1239; margin-bottom: 16px; line-height: 1.5;">
            You are about to permanently delete <strong>ALL <?= $stats['total_db_rows'] ?> records</strong> in the knowledge base. This operation is non-recoverable without a prior SQL backup.
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Type <code>CONFIRM CLEAR</code> to proceed:</label>
            <input type="text" name="confirm_clear_text" class="admin-form-control" required placeholder="CONFIRM CLEAR">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Enter Administrator Password:</label>
            <input type="password" name="admin_password" class="admin-form-control" required placeholder="Admin password">
          </div>
        </div>

        <div class="admin-modal-footer">
          <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal('modal-clear-all');">Cancel</button>
          <button type="submit" class="admin-btn admin-btn-danger">Execute Complete Clear</button>
        </div>
      </form>
    </div>
  </div>

  <!-- =================================================================== -->
  <!-- CLIENT-SIDE SCRIPTING FOR INTERACTIONS & COPY / BULK / TOASTS -->
  <!-- =================================================================== -->
  <script>
    // Toast Notification System
    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = `admin-toast ${type} active`;
      toast.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span>${message}</span>
      `;
      container.appendChild(toast);
      setTimeout(() => {
        toast.classList.remove('active');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    // Native Clipboard Copy
    function copyText(text, successMsg = 'Copied to clipboard!') {
      if (!text) {
        showToast('Nothing to copy.', 'error');
        return;
      }
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
          showToast(successMsg, 'success');
        }).catch(() => fallbackCopy(text, successMsg));
      } else {
        fallbackCopy(text, successMsg);
      }
    }

    function fallbackCopy(text, successMsg) {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        showToast(successMsg, 'success');
      } catch (err) {
        showToast('Failed to copy.', 'error');
      }
      document.body.removeChild(ta);
    }

    // Copy Full Record Summary
    function copyFullRecord(rowKey) {
      const row = document.getElementById(`row-${rowKey}`);
      if (!row) return;

      const cells = row.querySelectorAll('td');
      const qText = cells[3] ? cells[3].querySelector('div').innerText.trim() : '';
      const ansText = cells[4] ? cells[4].innerText.trim() : '';

      const fullBlock = `PFTP Q&A Record: [${rowKey}]\nQuestion: ${qText}\n\n${ansText}\n`;
      copyText(fullBlock, `Full Q&A [${rowKey}] copied!`);
    }

    // Copy Table Data
    function copyTableText() {
      const table = document.getElementById('qa-data-table');
      if (!table) return;

      let out = "PFTP Q&A Table Export:\n\n";
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(r => {
        const text = r.innerText.replace(/\s+/g, ' ').trim();
        if (text) out += text + "\n---\n";
      });

      copyText(out, 'Visible table contents copied to clipboard!');
    }

    // Modal Control Helpers
    function openModal(id) {
      const el = document.getElementById(id);
      if (el) el.classList.add('active');
    }

    function closeModal(id) {
      const el = document.getElementById(id);
      if (el) el.classList.remove('active');
    }

    function openCreateModal() {
      // Reset form fields
      const f = document.getElementById('unified-qa-form');
      if (f) f.reset();
      const origInput = f.querySelector('input[name="original_intent_id"]');
      if (origInput) origInput.value = '';
      openModal('modal-unified-form');
    }

    function resetForm(formId) {
      const f = document.getElementById(formId);
      if (f && confirm('Are you sure you want to clear this form?')) {
        f.reset();
        showToast('Form cleared.', 'info');
      }
    }

    // Auto-generate intent slug from question
    function autoGenerateIntentSlug() {
      const qInput = document.getElementById('form-main-q');
      const slugInput = document.getElementById('form-intent-id');
      if (qInput && slugInput && (!slugInput.value || slugInput.value.trim() === '')) {
        let clean = qInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        slugInput.value = clean.substring(0, 40);
      }
    }

    // Dropdown Toggling
    function toggleDropdown(id) {
      const dd = document.getElementById(id);
      if (!dd) return;
      const isOpen = dd.classList.contains('active');
      document.querySelectorAll('.admin-dropdown').forEach(d => d.classList.remove('active'));
      if (!isOpen) dd.classList.add('active');
    }

    // Close dropdowns on click outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.admin-dropdown')) {
        document.querySelectorAll('.admin-dropdown').forEach(d => d.classList.remove('active'));
      }
    });

    // Form Tab Switching (English / Urdu / Roman Urdu)
    function switchFormTab(paneId, btn) {
      const parent = btn.closest('.admin-modal-body');
      parent.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      parent.querySelectorAll('.admin-tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const targetPane = document.getElementById(paneId);
      if (targetPane) targetPane.classList.add('active');
    }

    function switchViewTab(paneId, btn) {
      const parent = document.getElementById('view-modal-body');
      parent.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      parent.querySelectorAll('.admin-tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const targetPane = document.getElementById(paneId);
      if (targetPane) targetPane.classList.add('active');
    }

    // Clear Search Input
    function toggleClearSearch(val) {
      const btn = document.getElementById('search-clear-btn');
      if (btn) btn.classList.toggle('visible', val.trim().length > 0);
    }

    function clearSearchInput() {
      const input = document.getElementById('search-input');
      if (input) {
        input.value = '';
        toggleClearSearch('');
        document.getElementById('filter-form').submit();
      }
    }

    // Quick View AJAX / Fetch
    function openQuickView(intentSlug) {
      window.location.href = `knowledge-base.php?view=<?= urlencode($viewMode) ?>&view_intent=${encodeURIComponent(intentSlug)}&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&lang=<?= urlencode($langFilter) ?>`;
    }

    // Single Record Delete Confirmation
    function confirmDeleteRecord(key, label, isUnified) {
      document.getElementById('delete-action-input').value = isUnified ? 'delete_unified' : 'delete_single';
      if (isUnified) {
        document.getElementById('delete-intent-input').value = key;
        document.getElementById('delete-id-input').value = '';
      } else {
        document.getElementById('delete-id-input').value = key;
        document.getElementById('delete-intent-input').value = '';
      }
      document.getElementById('delete-item-label').innerText = `${label} (${key})`;
      openModal('modal-delete-confirm');
    }

    // Bulk Checkbox & Action Dock Handling
    function toggleSelectAll(checked) {
      document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = checked;
      });
      updateBulkDockState();
    }

    function updateBulkDockState() {
      const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
      const count = checkedBoxes.length;
      const dock = document.getElementById('bulk-dock');
      const countBadge = document.getElementById('bulk-selected-count');

      if (count > 0) {
        dock.classList.add('active');
        countBadge.innerText = `${count} record${count > 1 ? 's' : ''} selected`;
      } else {
        dock.classList.remove('active');
      }

      // Update master checkbox
      const allBoxes = document.querySelectorAll('.row-checkbox');
      const master = document.getElementById('select-all-box');
      if (master && allBoxes.length > 0) {
        master.checked = count === allBoxes.length;
      }
    }

    function getSelectedKeys() {
      const keys = [];
      document.querySelectorAll('.row-checkbox:checked').forEach(cb => keys.push(cb.value));
      return keys;
    }

    function clearBulkSelection() {
      document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
      const master = document.getElementById('select-all-box');
      if (master) master.checked = false;
      updateBulkDockState();
      showToast('Selection cleared.', 'info');
    }

    function copySelectedRecords() {
      const keys = getSelectedKeys();
      if (keys.length === 0) return;

      let combined = `PFTP Q&A Export of ${keys.length} Selected Records:\n\n`;
      keys.forEach(k => {
        const row = document.getElementById(`row-${k}`);
        if (row) {
          const cells = row.querySelectorAll('td');
          const qText = cells[3] ? cells[3].innerText.trim() : '';
          const aText = cells[4] ? cells[4].innerText.trim() : '';
          combined += `[${k}]\nQuestion: ${qText}\nAnswer:\n${aText}\n\n====================\n\n`;
        }
      });

      copyText(combined, `Copied ${keys.length} selected records to clipboard!`);
    }

    function exportSelected(format) {
      const keys = getSelectedKeys();
      if (keys.length === 0) return;
      window.location.href = `knowledge-base.php?export=${format}&scope=selected&selected_keys=${encodeURIComponent(keys.join(','))}`;
    }

    function executeBulkStatus(statusVal) {
      const keys = getSelectedKeys();
      if (keys.length === 0) return;
      document.getElementById('bulk-op-input').value = statusVal === 'active' ? 'status_active' : 'status_inactive';
      document.getElementById('bulk-keys-input').value = keys.join(',');
      document.getElementById('bulk-action-form').submit();
    }

    function openBulkMoveModal() {
      const keys = getSelectedKeys();
      if (keys.length === 0) return;
      openModal('modal-bulk-category');
    }

    function submitBulkMove() {
      const keys = getSelectedKeys();
      const targetCat = document.getElementById('bulk-target-category-input').value.trim();
      if (!targetCat) {
        alert('Please specify a category name.');
        return;
      }
      document.getElementById('bulk-op-input').value = 'move_category';
      document.getElementById('bulk-keys-input').value = keys.join(',');
      document.getElementById('bulk-category-input').value = targetCat;
      document.getElementById('bulk-action-form').submit();
    }

    function confirmBulkDelete() {
      const keys = getSelectedKeys();
      if (keys.length === 0) return;
      if (confirm(`Are you sure you want to permanently delete these ${keys.length} selected records? This cannot be undone.`)) {
        document.getElementById('bulk-op-input').value = 'delete';
        document.getElementById('bulk-keys-input').value = keys.join(',');
        document.getElementById('bulk-action-form').submit();
      }
    }
  </script>

</body>
</html>
