<?php
/**
 * PFTP Admin: Query & Q&A Management Service
 * Provides Data Grouping, CRUD, Excel/Word Export, Import, and SQL Backup
 */

declare(strict_types=1);

class QAService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch unified Q&A records grouped by intent_id
     */
    public function getUnifiedList(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $search   = trim((string)($filters['search'] ?? ''));
        $category = trim((string)($filters['category'] ?? ''));
        $status   = trim((string)($filters['status'] ?? ''));
        $sort     = trim((string)($filters['sort'] ?? 'newest'));

        // First, fetch all records matching basic filters
        $where = [];
        $params = [];

        if ($category !== '') {
            $where[] = "category = :cat";
            $params[':cat'] = $category;
        }

        if ($status === 'active' || $status === 'inactive') {
            $where[] = "status = :st";
            $params[':st'] = $status;
        }

        if ($search !== '') {
            $where[] = "(question LIKE :s1 OR answer LIKE :s2 OR keywords LIKE :s3 OR question_variations LIKE :s4 OR category LIKE :s5 OR intent_id LIKE :s6)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
            $params[':s5'] = "%{$search}%";
            $params[':s6'] = "%{$search}%";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT *
            FROM knowledge_base
            {$whereSql}
            ORDER BY id ASC
        ");
        $stmt->execute($params);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group rows by intent_id (or category slug if intent_id is empty)
        $grouped = [];
        foreach ($allRows as $row) {
            $intentKey = !empty($row['intent_id']) ? $row['intent_id'] : 'intent_' . strtolower(preg_replace('/[^a-z0-9]+/i', '_', $row['category']));

            if (!isset($grouped[$intentKey])) {
                $grouped[$intentKey] = [
                    'intent_id'           => $intentKey,
                    'primary_id'          => (int) $row['id'],
                    'all_ids'             => [(int) $row['id']],
                    'category'            => $row['category'],
                    'status'              => $row['status'],
                    'main_question'       => '',
                    'question_variations' => '',
                    'keywords'            => '',
                    'possible_careers'    => '',
                    'views_count'         => 0,
                    'created_at'          => $row['created_at'],
                    'updated_at'          => $row['updated_at'],
                    'answer_en'           => '',
                    'answer_ur'           => '',
                    'answer_roman_ur'     => '',
                    'question_en'         => '',
                    'question_ur'         => '',
                    'question_roman_ur'   => '',
                    'available_langs'     => []
                ];
            } else {
                $grouped[$intentKey]['all_ids'][] = (int) $row['id'];
                $grouped[$intentKey]['views_count'] += (int) $row['views_count'];
                if ($row['created_at'] < $grouped[$intentKey]['created_at']) {
                    $grouped[$intentKey]['created_at'] = $row['created_at'];
                }
                if ($row['updated_at'] > $grouped[$intentKey]['updated_at']) {
                    $grouped[$intentKey]['updated_at'] = $row['updated_at'];
                }
                if ($row['status'] === 'active') {
                    $grouped[$intentKey]['status'] = 'active';
                }
            }

            $lang = $row['language'];
            $grouped[$intentKey]['available_langs'][] = $lang;

            if ($lang === 'en') {
                $grouped[$intentKey]['answer_en'] = $row['answer'];
                $grouped[$intentKey]['question_en'] = $row['question'];
                if (empty($grouped[$intentKey]['main_question'])) {
                    $grouped[$intentKey]['main_question'] = $row['question'];
                }
                if (!empty($row['question_variations'])) {
                    $grouped[$intentKey]['question_variations'] = $row['question_variations'];
                }
                if (!empty($row['keywords'])) {
                    $grouped[$intentKey]['keywords'] = $row['keywords'];
                }
                if (!empty($row['possible_careers'])) {
                    $grouped[$intentKey]['possible_careers'] = $row['possible_careers'];
                }
            } elseif ($lang === 'ur') {
                $grouped[$intentKey]['answer_ur'] = $row['answer'];
                $grouped[$intentKey]['question_ur'] = $row['question'];
                if (empty($grouped[$intentKey]['main_question'])) {
                    $grouped[$intentKey]['main_question'] = $row['question'];
                }
            } elseif ($lang === 'roman_ur') {
                $grouped[$intentKey]['answer_roman_ur'] = $row['answer'];
                $grouped[$intentKey]['question_roman_ur'] = $row['question'];
                if (empty($grouped[$intentKey]['main_question'])) {
                    $grouped[$intentKey]['main_question'] = $row['question'];
                }
                if (empty($grouped[$intentKey]['question_variations']) && !empty($row['question_variations'])) {
                    $grouped[$intentKey]['question_variations'] = $row['question_variations'];
                }
                if (empty($grouped[$intentKey]['keywords']) && !empty($row['keywords'])) {
                    $grouped[$intentKey]['keywords'] = $row['keywords'];
                }
            }
        }

        // Language filter if requested
        $langFilter = trim((string)($filters['lang'] ?? ''));
        if ($langFilter !== '') {
            $filtered = [];
            foreach ($grouped as $k => $item) {
                if (in_array($langFilter, $item['available_langs'], true)) {
                    $filtered[$k] = $item;
                }
            }
            $grouped = $filtered;
        }

        // Sorting
        $records = array_values($grouped);
        if ($sort === 'oldest') {
            usort($records, fn($a, $b) => $a['primary_id'] <=> $b['primary_id']);
        } elseif ($sort === 'category') {
            usort($records, fn($a, $b) => strcmp($a['category'], $b['category']));
        } elseif ($sort === 'views') {
            usort($records, fn($a, $b) => $b['views_count'] <=> $a['views_count']);
        } else {
            // Newest first (default)
            usort($records, fn($a, $b) => $b['primary_id'] <=> $a['primary_id']);
        }

        $totalRecords = count($records);

        // Pagination
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $pagedRecords = array_slice($records, $offset, $perPage);
        } else {
            $pagedRecords = $records;
        }

        return [
            'records'      => $pagedRecords,
            'all_records'  => $records,
            'total'        => $totalRecords,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 1
        ];
    }

    /**
     * Get a single unified Q&A record by intent_id or primary_id
     */
    public function getUnifiedRecord(string $intentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM knowledge_base
            WHERE intent_id = :intent OR id = :id
            ORDER BY id ASC
        ");
        $stmt->execute([':intent' => $intentId, ':id' => is_numeric($intentId) ? (int)$intentId : 0]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $first = $rows[0];
        $actualIntent = !empty($first['intent_id']) ? $first['intent_id'] : $intentId;

        $record = [
            'intent_id'           => $actualIntent,
            'primary_id'          => (int) $first['id'],
            'all_ids'             => [],
            'category'            => $first['category'],
            'status'              => $first['status'],
            'main_question'       => '',
            'question_variations' => '',
            'keywords'            => '',
            'possible_careers'    => '',
            'views_count'         => 0,
            'created_at'          => $first['created_at'],
            'updated_at'          => $first['updated_at'],
            'answer_en'           => '',
            'answer_ur'           => '',
            'answer_roman_ur'     => '',
            'question_en'         => '',
            'question_ur'         => '',
            'question_roman_ur'   => ''
        ];

        foreach ($rows as $r) {
            $record['all_ids'][] = (int) $r['id'];
            $record['views_count'] += (int) $r['views_count'];
            $lang = $r['language'];

            if ($lang === 'en') {
                $record['answer_en'] = $r['answer'];
                $record['question_en'] = $r['question'];
                $record['main_question'] = $r['question'];
                $record['question_variations'] = $r['question_variations'] ?? '';
                $record['keywords'] = $r['keywords'] ?? '';
                $record['possible_careers'] = $r['possible_careers'] ?? '';
            } elseif ($lang === 'ur') {
                $record['answer_ur'] = $r['answer'];
                $record['question_ur'] = $r['question'];
                if (empty($record['main_question'])) {
                    $record['main_question'] = $r['question'];
                }
            } elseif ($lang === 'roman_ur') {
                $record['answer_roman_ur'] = $r['answer'];
                $record['question_roman_ur'] = $r['question'];
                if (empty($record['main_question'])) {
                    $record['main_question'] = $r['question'];
                }
                if (empty($record['question_variations']) && !empty($r['question_variations'])) {
                    $record['question_variations'] = $r['question_variations'];
                }
                if (empty($record['keywords']) && !empty($r['keywords'])) {
                    $record['keywords'] = $r['keywords'];
                }
            }
        }

        return $record;
    }

    /**
     * Save (Create or Update) a Unified Q&A Record with all three languages
     */
    public function saveUnifiedRecord(array $data): array
    {
        $intentId       = trim((string)($data['intent_id'] ?? ''));
        $category       = trim((string)($data['category'] ?? 'General'));
        $mainQuestion   = trim((string)($data['main_question'] ?? ''));
        $variations     = trim((string)($data['question_variations'] ?? ''));
        $answerEn       = trim((string)($data['answer_en'] ?? ''));
        $answerUr       = trim((string)($data['answer_ur'] ?? ''));
        $answerRomanUr  = trim((string)($data['answer_roman_ur'] ?? ''));
        $keywords       = trim((string)($data['keywords'] ?? ''));
        $careers        = trim((string)($data['possible_careers'] ?? ''));
        $status         = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $originalIntent = trim((string)($data['original_intent_id'] ?? ''));

        if ($mainQuestion === '') {
            return ['success' => false, 'error' => 'Main Question cannot be empty.'];
        }
        if ($answerEn === '' && $answerUr === '' && $answerRomanUr === '') {
            return ['success' => false, 'error' => 'At least one language answer (English, Urdu, or Roman Urdu) is required.'];
        }

        if ($intentId === '') {
            $intentId = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($category . '_' . substr($mainQuestion, 0, 30))));
        }

        $this->db->beginTransaction();

        try {
            // If renaming an existing intent
            $targetIntent = !empty($originalIntent) ? $originalIntent : $intentId;

            // Fetch existing rows for this intent
            $checkStmt = $this->db->prepare("SELECT id, language FROM knowledge_base WHERE intent_id = :intent");
            $checkStmt->execute([':intent' => $targetIntent]);
            $existing = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

            $existingByLang = [];
            foreach ($existing as $ex) {
                $existingByLang[$ex['language']] = (int) $ex['id'];
            }

            // Upsert EN
            if (!empty($answerEn) || isset($existingByLang['en'])) {
                if (isset($existingByLang['en'])) {
                    $uStmt = $this->db->prepare("
                        UPDATE knowledge_base
                        SET category = :cat, intent_id = :intent, question = :q, question_variations = :var,
                            answer = :ans, keywords = :kw, possible_careers = :car, status = :st, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $uStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $mainQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerEn,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status,
                        ':id'     => $existingByLang['en']
                    ]);
                } else {
                    $iStmt = $this->db->prepare("
                        INSERT INTO knowledge_base
                        (category, intent_id, language, question, question_variations, answer, keywords, possible_careers, status)
                        VALUES (:cat, :intent, 'en', :q, :var, :ans, :kw, :car, :st)
                    ");
                    $iStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $mainQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerEn,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status
                    ]);
                }
            }

            // Upsert Urdu
            $urQuestion = !empty($data['question_ur']) ? trim((string)$data['question_ur']) : $mainQuestion;
            if (!empty($answerUr) || isset($existingByLang['ur'])) {
                if (isset($existingByLang['ur'])) {
                    $uStmt = $this->db->prepare("
                        UPDATE knowledge_base
                        SET category = :cat, intent_id = :intent, question = :q, question_variations = :var,
                            answer = :ans, keywords = :kw, possible_careers = :car, status = :st, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $uStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $urQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerUr,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status,
                        ':id'     => $existingByLang['ur']
                    ]);
                } else {
                    $iStmt = $this->db->prepare("
                        INSERT INTO knowledge_base
                        (category, intent_id, language, question, question_variations, answer, keywords, possible_careers, status)
                        VALUES (:cat, :intent, 'ur', :q, :var, :ans, :kw, :car, :st)
                    ");
                    $iStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $urQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerUr,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status
                    ]);
                }
            }

            // Upsert Roman Urdu
            $romanQuestion = !empty($data['question_roman_ur']) ? trim((string)$data['question_roman_ur']) : $mainQuestion;
            if (!empty($answerRomanUr) || isset($existingByLang['roman_ur'])) {
                if (isset($existingByLang['roman_ur'])) {
                    $uStmt = $this->db->prepare("
                        UPDATE knowledge_base
                        SET category = :cat, intent_id = :intent, question = :q, question_variations = :var,
                            answer = :ans, keywords = :kw, possible_careers = :car, status = :st, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $uStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $romanQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerRomanUr,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status,
                        ':id'     => $existingByLang['roman_ur']
                    ]);
                } else {
                    $iStmt = $this->db->prepare("
                        INSERT INTO knowledge_base
                        (category, intent_id, language, question, question_variations, answer, keywords, possible_careers, status)
                        VALUES (:cat, :intent, 'roman_ur', :q, :var, :ans, :kw, :car, :st)
                    ");
                    $iStmt->execute([
                        ':cat'    => $category,
                        ':intent' => $intentId,
                        ':q'      => $romanQuestion,
                        ':var'    => $variations,
                        ':ans'    => $answerRomanUr,
                        ':kw'     => $keywords,
                        ':car'    => $careers,
                        ':st'     => $status
                    ]);
                }
            }

            $this->db->commit();
            return ['success' => true, 'intent_id' => $intentId];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete an entire intent and its associated question variations
     */
    public function deleteUnifiedRecord(string $intentId): int
    {
        $stmt = $this->db->prepare("DELETE FROM knowledge_base WHERE intent_id = :intent");
        $stmt->execute([':intent' => $intentId]);
        return $stmt->rowCount();
    }

    /**
     * Bulk Delete records by Intent IDs or Row IDs
     */
    public function bulkDelete(array $keys, bool $isIntent = true): int
    {
        if (empty($keys)) return 0;

        if ($isIntent) {
            $inClause = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $this->db->prepare("DELETE FROM knowledge_base WHERE intent_id IN ({$inClause})");
            $stmt->execute(array_values($keys));
            return $stmt->rowCount();
        } else {
            $intIds = array_map('intval', $keys);
            $inClause = implode(',', array_fill(0, count($intIds), '?'));
            $stmt = $this->db->prepare("DELETE FROM knowledge_base WHERE id IN ({$inClause})");
            $stmt->execute(array_values($intIds));
            return $stmt->rowCount();
        }
    }

    /**
     * Bulk Status Change
     */
    public function bulkUpdateStatus(array $keys, string $status, bool $isIntent = true): int
    {
        if (empty($keys)) return 0;
        $statusVal = $status === 'inactive' ? 'inactive' : 'active';

        if ($isIntent) {
            $inClause = implode(',', array_fill(0, count($keys), '?'));
            $params = array_merge([$statusVal], array_values($keys));
            $stmt = $this->db->prepare("UPDATE knowledge_base SET status = ? WHERE intent_id IN ({$inClause})");
            $stmt->execute($params);
            return $stmt->rowCount();
        } else {
            $intIds = array_map('intval', $keys);
            $inClause = implode(',', array_fill(0, count($intIds), '?'));
            $params = array_merge([$statusVal], array_values($intIds));
            $stmt = $this->db->prepare("UPDATE knowledge_base SET status = ? WHERE id IN ({$inClause})");
            $stmt->execute($params);
            return $stmt->rowCount();
        }
    }

    /**
     * Bulk Move Category
     */
    public function bulkMoveCategory(array $keys, string $newCategory, bool $isIntent = true): int
    {
        if (empty($keys) || empty($newCategory)) return 0;

        if ($isIntent) {
            $inClause = implode(',', array_fill(0, count($keys), '?'));
            $params = array_merge([$newCategory], array_values($keys));
            $stmt = $this->db->prepare("UPDATE knowledge_base SET category = ? WHERE intent_id IN ({$inClause})");
            $stmt->execute($params);
            return $stmt->rowCount();
        } else {
            $intIds = array_map('intval', $keys);
            $inClause = implode(',', array_fill(0, count($intIds), '?'));
            $params = array_merge([$newCategory], array_values($intIds));
            $stmt = $this->db->prepare("UPDATE knowledge_base SET category = ? WHERE id IN ({$inClause})");
            $stmt->execute($params);
            return $stmt->rowCount();
        }
    }

    /**
     * Generate and stream genuine OpenXML .xlsx file
     */
    public function exportXLSX(array $records, string $filename = 'pftp-qa-dataset.xlsx'): void
    {
        $headers = [
            'ID',
            'Intent',
            'Main Question',
            'Variations',
            'English Answer',
            'Urdu Answer',
            'Roman Urdu Answer',
            'Keywords',
            'Category',
            'Created At',
            'Updated At'
        ];

        $rows = [];
        foreach ($records as $r) {
            $rows[] = [
                'ID'                => (string) ($r['primary_id'] ?? $r['id'] ?? ''),
                'Intent'            => (string) ($r['intent_id'] ?? ''),
                'Main Question'     => (string) ($r['main_question'] ?? $r['question'] ?? ''),
                'Variations'        => (string) ($r['question_variations'] ?? ''),
                'English Answer'    => (string) ($r['answer_en'] ?? ($r['language'] === 'en' ? $r['answer'] : '')),
                'Urdu Answer'       => (string) ($r['answer_ur'] ?? ($r['language'] === 'ur' ? $r['answer'] : '')),
                'Roman Urdu Answer' => (string) ($r['answer_roman_ur'] ?? ($r['language'] === 'roman_ur' ? $r['answer'] : '')),
                'Keywords'          => (string) ($r['keywords'] ?? ''),
                'Category'          => (string) ($r['category'] ?? 'General'),
                'Created At'        => (string) ($r['created_at'] ?? ''),
                'Updated At'        => (string) ($r['updated_at'] ?? '')
            ];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Failed to initialize Excel export archive.";
            exit;
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="PFTP Q&amp;A Knowledge Base" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF07613F"/></patternFill></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
    </cellXfs>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        $esc = function(string $s): string {
            // Formula injection protection
            if (preg_match('/^[=\+\-@]/', $s)) {
                $s = "'" . $s;
            }
            return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        };

        $colLetter = function(int $colIndex): string {
            $letter = '';
            while ($colIndex > 0) {
                $mod = ($colIndex - 1) % 26;
                $letter = chr(65 + $mod) . $letter;
                $colIndex = (int)(($colIndex - $mod) / 26);
            }
            return $letter;
        };

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';

        // Header row
        $sheetXml .= '<row r="1">';
        foreach ($headers as $cIdx => $headerText) {
            $cellRef = $colLetter($cIdx + 1) . '1';
            $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $esc($headerText) . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheetXml .= '<row r="' . $rowNum . '">';
            foreach (array_values($row) as $cIdx => $cellVal) {
                $cellRef = $colLetter($cIdx + 1) . $rowNum;
                $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . $esc((string)$cellVal) . '</t></is></c>';
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        // Stream file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: max-age=0');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    /**
     * Generate and stream genuine OpenXML .docx file
     */
    public function exportDOCX(array $records, string $filename = 'pftp-qa-dataset.docx'): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Failed to initialize Word export archive.";
            exit;
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        $esc = function(string $s): string {
            return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        };

        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>';

        // Document Title
        $docXml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="48"/><w:color w:val="07613F"/></w:rPr><w:t>PFTP Chatbot — Q&amp;A Knowledge Base</w:t></w:r></w:p>';
        $docXml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:color w:val="666666"/></w:rPr><w:t>Pakistan Freelancing Training Program • Exported on ' . date('Y-m-d H:i') . '</w:t></w:r></w:p>';
        $docXml .= '<w:p><w:r><w:t></w:t></w:r></w:p>';

        $totalRecs = count($records);
        foreach ($records as $index => $rec) {
            $intentId   = $rec['intent_id'] ?? 'general';
            $mainQ      = $rec['main_question'] ?? $rec['question'] ?? '';
            $variations = $rec['question_variations'] ?? '';
            $ansEn      = $rec['answer_en'] ?? ($rec['language'] === 'en' ? $rec['answer'] : '');
            $ansUr      = $rec['answer_ur'] ?? ($rec['language'] === 'ur' ? $rec['answer'] : '');
            $ansRomanUr = $rec['answer_roman_ur'] ?? ($rec['language'] === 'roman_ur' ? $rec['answer'] : '');
            $keywords   = $rec['keywords'] ?? 'N/A';
            $category   = $rec['category'] ?? 'General';

            // Intent ID Banner
            $docXml .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="12" w:space="4" w:color="07613F"/></w:pBdr></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="07613F"/></w:rPr><w:t>Intent ID: ' . $esc($intentId) . '</w:t></w:r></w:p>';

            // Main Question
            $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>Main Question: </w:t></w:r><w:r><w:rPr><w:sz w:val="24"/></w:rPr><w:t>' . $esc($mainQ) . '</w:t></w:r></w:p>';

            // Alternative Questions
            if (!empty($variations)) {
                $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>Alternative Questions:</w:t></w:r></w:p>';
                $varsList = explode('|', $variations);
                foreach ($varsList as $v) {
                    if (trim($v) === '') continue;
                    $docXml .= '<w:p><w:pPr><w:ind w:left="360"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t>• ' . $esc(trim($v)) . '</w:t></w:r></w:p>';
                }
            }

            // English Answer
            if (!empty($ansEn)) {
                $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="0284C7"/></w:rPr><w:t>English Answer:</w:t></w:r></w:p>';
                $docXml .= '<w:p><w:pPr><w:ind w:left="240"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t>' . $esc($ansEn) . '</w:t></w:r></w:p>';
            }

            // Urdu Answer (with RTL formatting)
            if (!empty($ansUr)) {
                $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="B45309"/></w:rPr><w:t>Urdu Answer (اردو جواب):</w:t></w:r></w:p>';
                $docXml .= '<w:p><w:pPr><w:bidi/><w:jc w:val="right"/><w:ind w:right="240"/></w:pPr><w:r><w:rPr><w:bidi/><w:rFonts w:cs="Noto Nastaliq Urdu"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t>' . $esc($ansUr) . '</w:t></w:r></w:p>';
            }

            // Roman Urdu Answer
            if (!empty($ansRomanUr)) {
                $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="7E22CE"/></w:rPr><w:t>Roman Urdu Answer:</w:t></w:r></w:p>';
                $docXml .= '<w:p><w:pPr><w:ind w:left="240"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t>' . $esc($ansRomanUr) . '</w:t></w:r></w:p>';
            }

            // Category & Keywords
            $docXml .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="18"/><w:color w:val="666666"/></w:rPr><w:t>Category: ' . $esc($category) . ' | Keywords: ' . $esc($keywords) . '</w:t></w:r></w:p>';

            // Page Break between records
            if ($index < $totalRecs - 1) {
                $docXml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            }
        }

        $docXml .= '</w:body></w:document>';
        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        // Stream file
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: max-age=0');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    /**
     * Generate SQL Database Dump for Knowledge Base
     */
    public function exportSQLBackup(): void
    {
        $stmt = $this->db->query("SELECT * FROM knowledge_base ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "-- ===================================================================\n";
        $sql .= "-- PFTP Website AI Chatbot - Knowledge Base Database Backup\n";
        $sql .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Total Records: " . count($rows) . "\n";
        $sql .= "-- ===================================================================\n\n";
        $sql .= "USE `pftp_chatbot`;\n\n";
        $sql .= "TRUNCATE TABLE `knowledge_base`;\n\n";

        if (!empty($rows)) {
            $sql .= "INSERT INTO `knowledge_base`\n";
            $sql .= "(`id`, `category`, `intent_id`, `language`, `question`, `question_variations`, `answer`, `keywords`, `possible_careers`, `status`, `views_count`, `created_at`, `updated_at`)\nVALUES\n";

            $valuesList = [];
            foreach ($rows as $r) {
                $id        = (int) $r['id'];
                $cat       = $this->db->quote($r['category']);
                $intent    = $r['intent_id'] !== null ? $this->db->quote($r['intent_id']) : 'NULL';
                $lang      = $this->db->quote($r['language']);
                $q         = $this->db->quote($r['question']);
                $vars      = $r['question_variations'] !== null ? $this->db->quote($r['question_variations']) : 'NULL';
                $ans       = $this->db->quote($r['answer']);
                $kw        = $r['keywords'] !== null ? $this->db->quote($r['keywords']) : 'NULL';
                $careers   = $r['possible_careers'] !== null ? $this->db->quote($r['possible_careers']) : 'NULL';
                $st        = $this->db->quote($r['status']);
                $views     = (int) $r['views_count'];
                $created   = $this->db->quote($r['created_at']);
                $updated   = $this->db->quote($r['updated_at']);

                $valuesList[] = "({$id}, {$cat}, {$intent}, {$lang}, {$q}, {$vars}, {$ans}, {$kw}, {$careers}, {$st}, {$views}, {$created}, {$updated})";
            }

            $sql .= implode(",\n", $valuesList) . ";\n";
        }

        $filename = 'pftp-knowledge-base-backup-' . date('Y-m-d-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        header('Cache-Control: max-age=0');
        echo $sql;
        exit;
    }

    /**
     * Parse uploaded XLSX or CSV file for preview/import
     */
    public function parseImportFile(string $filePath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            return $this->parseCSV($filePath);
        } elseif ($ext === 'xlsx') {
            return $this->parseXLSX($filePath);
        } else {
            return ['success' => false, 'error' => 'Unsupported file format. Please upload a genuine .xlsx or .csv file.'];
        }
    }

    private function parseCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Could not read CSV file.'];
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'error' => 'Empty CSV file.'];
        }

        // Normalize header
        $cleanHeader = array_map(fn($h) => strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', $h))), $header);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            $mapped = [];
            foreach ($cleanHeader as $idx => $colName) {
                $mapped[$colName] = $row[$idx] ?? '';
            }
            $rows[] = $mapped;
        }
        fclose($handle);

        return $this->validateImportRows($rows);
    }

    private function parseXLSX(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return ['success' => false, 'error' => 'Failed to open XLSX archive.'];
        }

        // Read sharedStrings.xml
        $sharedStrings = [];
        $ssXmlStr = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXmlStr !== false) {
            $ssXml = simplexml_load_string($ssXmlStr);
            if ($ssXml && isset($ssXml->si)) {
                foreach ($ssXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $tStr = '';
                        foreach ($si->r as $r) {
                            $tStr .= (string) $r->t;
                        }
                        $sharedStrings[] = $tStr;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // Read sheet1.xml
        $sheetXmlStr = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlStr === false) {
            $zip->close();
            return ['success' => false, 'error' => 'Invalid XLSX format: missing worksheet data.'];
        }

        $sheetXml = simplexml_load_string($sheetXmlStr);
        $zip->close();

        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return ['success' => false, 'error' => 'No rows found in Excel sheet.'];
        }

        $rawRows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $cellRef = (string) $c['r'];
                $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                $type = (string) $c['t'];

                $val = '';
                if ($type === 's') {
                    $sIndex = (int) $c->v;
                    $val = $sharedStrings[$sIndex] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }

                // Strip leading formula-escape quote if present
                if (str_starts_with($val, "'")) {
                    $val = substr($val, 1);
                }

                $cells[$colLetter] = $val;
            }
            $rawRows[] = $cells;
        }

        if (count($rawRows) < 2) {
            return ['success' => false, 'error' => 'Excel file must contain at least a header row and one data row.'];
        }

        // Map headers
        $headerRow = $rawRows[0];
        $headerMap = [];
        foreach ($headerRow as $colLetter => $hText) {
            $headerMap[$colLetter] = strtolower(trim($hText));
        }

        $mappedRows = [];
        for ($i = 1; $i < count($rawRows); $i++) {
            $rowObj = [];
            foreach ($headerMap as $colLetter => $colName) {
                $rowObj[$colName] = $rawRows[$i][$colLetter] ?? '';
            }
            if (!empty(array_filter($rowObj))) {
                $mappedRows[] = $rowObj;
            }
        }

        return $this->validateImportRows($mappedRows);
    }

    private function validateImportRows(array $rows): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = [];
        $updates = [];

        // Fetch existing intents from DB
        $existingIntents = $this->db->query("SELECT DISTINCT intent_id FROM knowledge_base WHERE intent_id IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        $existingSet = array_flip($existingIntents);

        foreach ($rows as $index => $r) {
            $rowNum = $index + 2;

            // Resolve columns with flexible naming
            $intent   = $r['intent'] ?? $r['intent_id'] ?? '';
            $cat      = $r['category'] ?? 'General';
            $mainQ    = $r['main question'] ?? $r['question'] ?? '';
            $vars     = $r['variations'] ?? $r['question_variations'] ?? '';
            $ansEn    = $r['english answer'] ?? $r['answer_en'] ?? $r['answer'] ?? '';
            $ansUr    = $r['urdu answer'] ?? $r['answer_ur'] ?? '';
            $ansRoman = $r['roman urdu answer'] ?? $r['answer_roman_ur'] ?? '';
            $kw       = $r['keywords'] ?? '';
            $status   = strtolower(trim($r['status'] ?? 'active'));

            if (empty($mainQ)) {
                $invalid[] = "Row {$rowNum}: Missing Main Question.";
                continue;
            }
            if (empty($ansEn) && empty($ansUr) && empty($ansRoman)) {
                $invalid[] = "Row {$rowNum}: Missing Answers (must provide at least one language).";
                continue;
            }

            if (empty($intent)) {
                $intent = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $cat . '_' . substr($mainQ, 0, 25)));
            }

            $recordObj = [
                'intent_id'           => $intent,
                'category'            => $cat,
                'main_question'       => $mainQ,
                'question_variations' => $vars,
                'answer_en'           => $ansEn,
                'answer_ur'           => $ansUr,
                'answer_roman_ur'     => $ansRoman,
                'keywords'            => $kw,
                'status'              => $status === 'inactive' ? 'inactive' : 'active'
            ];

            if (isset($existingSet[$intent])) {
                $updates[] = $recordObj;
            } else {
                $valid[] = $recordObj;
            }
        }

        return [
            'success'      => true,
            'total_rows'   => count($rows),
            'valid_new'    => $valid,
            'valid_update' => $updates,
            'invalid'      => $invalid
        ];
    }

    /**
     * Commit imported records to database
     */
    public function executeImport(array $records, bool $overwriteExisting = false): int
    {
        $importedCount = 0;
        foreach ($records as $rec) {
            $intent = $rec['intent_id'];
            if (!$overwriteExisting) {
                // Check if already exists
                $check = $this->db->prepare("SELECT COUNT(*) FROM knowledge_base WHERE intent_id = :intent");
                $check->execute([':intent' => $intent]);
                if ((int)$check->fetchColumn() > 0) {
                    continue; // Skip
                }
            }

            $res = $this->saveUnifiedRecord($rec);
            if ($res['success']) {
                $importedCount++;
            }
        }
        return $importedCount;
    }
}

