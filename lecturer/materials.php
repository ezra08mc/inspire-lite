<?php
require_once "../config/db.php";
require_once "_lib.php";
require_once "_layout.php";

lecturer_require_role();

$success = '';
$error = '';

$courseCode = trim((string)($_GET['course_code'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));

$courses = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kode_mk, nama_mata_kuliah FROM jadwal ORDER BY nama_mata_kuliah ASC");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$categoryMap = [
    'lecture_notes' => 'Lecture Notes',
    'slides' => 'Presentation Slides',
    'assignment_guides' => 'Assignment Guides',
    'lab_materials' => 'Lab Materials',
    'reference_documents' => 'Reference Documents',
    'other' => 'Other',
];

function materials_parse_meta(string $name): array {
    $course = '';
    $category = '';
    $parts = explode(' | ', $name);
    foreach ($parts as $p) {
        $p = trim($p);
        if (stripos($p, 'Course:') === 0) $course = trim(substr($p, strlen('Course:')));
        if (stripos($p, 'Cat:') === 0) $category = trim(substr($p, strlen('Cat:')));
    }
    return [$course, $category];
}

function materials_payload_from_fields(string $title, string $kodeMk, string $category, string $description): array {
    $title = trim($title);
    $kodeMk = trim($kodeMk);
    $description = trim($description);

    $fileName = $title;
    $name = $title;
    $metaParts = [];
    if ($kodeMk !== '') $metaParts[] = 'Course:' . $kodeMk;
    if ($category !== '') $metaParts[] = 'Cat:' . $category;
    if ($description !== '') $metaParts[] = 'Desc:' . $description;

    if (!empty($metaParts)) {
        $name .= ' [' . implode(' | ', $metaParts) . ']';
    }

    return [$name, $fileName];
}

$materialRows = [];
try {
    $where = " WHERE user_id = :user_id";
    $params = [':user_id' => (int)($_SESSION['user_id'] ?? 0)];

    if ($courseCode !== '') {
        $where .= " AND name LIKE :course_hint";
        $params[':course_hint'] = '%' . $courseCode . '%';
    }

    if ($search !== '') {
        $where .= " AND (name LIKE :q OR deadline_text LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare("SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks" . $where . " ORDER BY id DESC");
    $stmt->execute($params);
    $materialRows = $stmt->fetchAll();
} catch (PDOException $e) {
    $materialRows = [];
}

$recentMaterials = $materialRows;
if (!empty($recentMaterials)) {
    $recentMaterials = array_slice($recentMaterials, 0, 5);
}

$stats = [
    'total_courses' => '0',
    'total_materials' => (string)count($materialRows),
    'total_downloads' => '0',
    'recent_uploads' => (string)count($recentMaterials),
];

try {
    $stats['total_courses'] = (string)count($courses);
} catch (PDOException $e) {}

try {
    $stats['total_downloads'] = '0';
} catch (PDOException $e) {
    $stats['total_downloads'] = '0';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'upload'));

    $kodeMk = trim((string)($_POST['course_code'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'other'));
    $description = trim((string)($_POST['description'] ?? ''));

    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'create') {
        if ($title === '' || $kodeMk === '') {
            $error = 'Course dan judul wajib diisi.';
        } else {
            try {
                $original = isset($_FILES['attachment']['name']) ? (string)$_FILES['attachment']['name'] : '';
                $dueDate = trim((string)($_POST['due_date'] ?? ''));
                $dueTime = trim((string)($_POST['due_time'] ?? ''));
                $deadlineText = $dueDate;
                if ($dueTime !== '') $deadlineText .= ' ' . $dueTime;
                if ($deadlineText === '') $deadlineText = date('Y-m-d H:i:s');

                $metaCategoryKey = $category;
                if (!isset($categoryMap[$category])) $metaCategoryKey = 'other';

                [$name, $fileName] = materials_payload_from_fields($title, $kodeMk, $metaCategoryKey, $description);

                $stmt = $pdo->prepare("INSERT INTO active_tasks (user_id, name, deadline_text, is_alert, is_completed) VALUES (:user_id, :name, :deadline_text, :is_alert, 0)");
                $stmt->execute([
                    ':user_id' => (int)($_SESSION['user_id'] ?? 0),
                    ':name' => $name,
                    ':deadline_text' => $deadlineText,
                    ':is_alert' => isset($_POST['publish']) ? 1 : 0,
                ]);

                $success = 'Materi berhasil dipublikasikan.';
                header('Location: materials.php?course_code=' . urlencode($kodeMk));
                exit();
            } catch (PDOException $e) {
                $error = 'Gagal mengunggah materi.';
            }
        }
    }

    if ($action === 'delete') {
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM active_tasks WHERE id = :id AND user_id = :user_id");
                $stmt->execute([':id' => $id, ':user_id' => (int)($_SESSION['user_id'] ?? 0)]);
                $success = 'Materi dihapus.';
            } catch (PDOException $e) {
                $error = 'Gagal menghapus materi.';
            }
        }
        header('Location: materials.php?course_code=' . urlencode($courseCode));
        exit();
    }

    if ($action === 'publish') {
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE active_tasks SET is_alert = :is_alert WHERE id = :id AND user_id = :user_id");
                $stmt->execute([':is_alert' => 1, ':id' => $id, ':user_id' => (int)($_SESSION['user_id'] ?? 0)]);
                $success = 'Materi dipublikasikan.';
            } catch (PDOException $e) {
                $error = 'Gagal memublikasikan materi.';
            }
        }
        header('Location: materials.php');
        exit();
    }
}

function render_course_name(array $courses, string $code): string {
    foreach ($courses as $c) {
        if ((string)$c['kode_mk'] === $code) return (string)$c['nama_mata_kuliah'];
    }
    return $code;
}

$materialForTable = $materialRows;
$courseLookup = [];
foreach ($courses as $c) {
    $courseLookup[(string)$c['kode_mk']] = (string)$c['nama_mata_kuliah'];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Course Materials</h1>
            <p>Manage teaching materials and learning resources.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Courses</span><span class="metric-value"><?= htmlspecialchars($stats['total_courses']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Total Materials</span><span class="metric-value"><?= htmlspecialchars($stats['total_materials']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Total Downloads</span><span class="metric-value"><?= htmlspecialchars($stats['total_downloads']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Recent Uploads</span><span class="metric-value"><?= htmlspecialchars($stats['recent_uploads']) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Materials</h3>
                <button type="button" class="trigger-btn btn-blue" style="border:none; width:auto;" onclick="openUploadModal()">Upload</button>
            </div>

            <?php if ($success !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= e($error) ?></div>
            <?php endif; ?>

            <div style="padding: 0 16px 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Course</span>
                            <select name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= e($c['kode_mk']) ?>" <?= $courseCode === (string)$c['kode_mk'] ? 'selected' : '' ?>><?= e($c['nama_mata_kuliah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Search</span>
                            <input name="q" value="<?= e($search) ?>" placeholder="Material title" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" type="text" />
                        </label>
                    </div>
                    <button type="submit" class="trigger-btn btn-blue" style="border:none; width:100%;">Apply</button>
                </form>
            </div>

            <div class="agenda-table-wrapper" style="margin: 0 16px 16px;">
                <div class="agenda-table-header" style="grid-template-columns: 1.1fr 0.8fr 0.7fr 0.7fr 0.8fr 0.5fr 0.7fr;">
                    <span class="col-head">Material Title</span>
                    <span class="col-head">Course</span>
                    <span class="col-head">Category</span>
                    <span class="col-head">File Type</span>
                    <span class="col-head">Upload Date</span>
                    <span class="col-head">Downloads</span>
                    <span class="col-head">Actions</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($materialForTable)): ?>
                        <div class="empty-fallback-text border-box-pad" style="margin: 12px;">No materials found.</div>
                    <?php else: ?>
                        <?php foreach ($materialForTable as $m): ?>
                            <?php
                                $rawName = (string)($m['name'] ?? '');
                                $deadlineText = (string)($m['deadline_text'] ?? '');
                                $courseFromMeta = '';
                                $catFromMeta = '';
                                $titlePart = $rawName;
                                if (strpos($rawName, ' [' ) !== false) {
                                    $titlePart = trim(substr($rawName, 0, strpos($rawName, ' [')));
                                    [$courseFromMeta, $catFromMeta] = materials_parse_meta($rawName);
                                }
                                if ($courseFromMeta === '' && $courseCode !== '') $courseFromMeta = $courseCode;
                                $categoryLabel = isset($categoryMap[$catFromMeta]) ? $categoryMap[$catFromMeta] : (isset($categoryMap['other']) ? $categoryMap['other'] : 'Other');
                                $fileType = 'FILE';
                                $uploadDate = $deadlineText;
                            ?>
                            <div class="agenda-table-row" style="grid-template-columns: 1.1fr 0.8fr 0.7fr 0.7fr 0.8fr 0.5fr 0.7fr;">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= e($titlePart) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($courseLookup[$courseFromMeta] ?? $courseFromMeta) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($categoryLabel) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($fileType) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($uploadDate) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline">—</span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end;">
                                        <button type="button" class="trigger-btn" style="border:none; padding:8px 12px; background-color: #007BEC; color:white;" onclick="alert('View not connected to database in this build.')">View</button>
                                        <button type="button" class="trigger-btn btn-blue" style="border:none; padding:8px 12px;" onclick="alert('Download not connected to database in this build.')">Download</button>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
                                            <button type="submit" class="trigger-btn btn-red" style="border:none; padding:8px 12px;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card" style="flex: 0.95;">
            <div class="card-top">
                <h3>Recent Uploads</h3>
            </div>

            <div class="announcements-feed" style="margin-top: -6px; padding: 0 16px 16px;">
                <?php if (empty($recentMaterials)): ?>
                    <div class="empty-fallback-text border-box-pad">No uploads yet.</div>
                <?php else: ?>
                    <?php foreach ($recentMaterials as $m): ?>
                        <?php
                            $rawName = (string)($m['name'] ?? '');
                            $deadlineText = (string)($m['deadline_text'] ?? '');
                            $courseFromMeta = '';
                            $catFromMeta = '';
                            $titlePart = $rawName;
                            if (strpos($rawName, ' [' ) !== false) {
                                $titlePart = trim(substr($rawName, 0, strpos($rawName, ' [')));
                                [$courseFromMeta, $catFromMeta] = materials_parse_meta($rawName);
                            }
                        ?>
                        <div class="annc-node">
                            <div class="annc-top-line">
                                <div class="annc-icon-frame blue">
                                    <svg viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
                                </div>
                                <div class="annc-title-area">
                                    <div class="annc-meta">
                                        <span class="annc-date"><?= e($deadlineText) ?></span>
                                    </div>
                                    <h4><?= e($titlePart) ?></h4>
                                </div>
                            </div>
                            <span class="annc-dept"><?= e($courseLookup[$courseFromMeta] ?? $courseFromMeta) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card-top" style="margin-top: 6px;">
                <h3>Course Summary</h3>
            </div>

            <div class="tasks-vertical-stack" style="padding: 0 16px 16px; margin-top: -6px;">
                <?php if (empty($courses)): ?>
                    <div class="empty-fallback-text border-box-pad">No courses available.</div>
                <?php else: ?>
                    <?php
                        $summary = [];
                        foreach ($courses as $c) {
                            $code = (string)$c['kode_mk'];
                            $summary[$code] = ['code' => $code, 'name' => (string)$c['nama_mata_kuliah'], 'count' => 0, 'downloads' => 0];
                        }
                        foreach ($materialRows as $m) {
                            $rawName = (string)($m['name'] ?? '');
                            $courseFromMeta = '';
                            if (strpos($rawName, ' [' ) !== false) {
                                [$courseFromMeta, $catFromMeta] = materials_parse_meta($rawName);
                            }
                            if ($courseFromMeta !== '' && isset($summary[$courseFromMeta])) $summary[$courseFromMeta]['count']++;
                        }
                    ?>
                    <?php foreach ($summary as $s): ?>
                        <div class="task-node">
                            <div class="task-checkbox-frame" style="background-color: rgba(0, 123, 236, 0.08); border-color: rgba(0, 123, 236, 0.25);">
                                <svg viewBox="0 0 24 24" class="check-svg" style="fill: #007BEC;"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
                            </div>
                            <div class="task-node-details">
                                <span class="task-node-title"><?= e($s['name']) ?></span>
                                <span class="task-node-time">Materials: <?= e((string)$s['count']) ?> · Downloads: <?= e((string)$s['downloads']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="uploadModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.35); display:none; align-items:center; justify-content:center; z-index: 4000;">
        <div style="width: 100%; max-width: 780px; background:#ffffff; border-radius: var(--radius); border:1px solid var(--border); box-shadow: 0 30px 80px rgba(0,0,0,0.25); overflow:hidden;">
            <div class="navbar" style="height:auto; background: linear-gradient(135deg, #8b0000 0%, var(--primary) 60%, #ff5247 100%); padding: 14px 18px; border-bottom:none;">
                <div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:12px;">
                    <div>
                        <div style="color:#fff; font-weight:800; font-size:1rem;">Upload Material</div>
                        <div style="color: rgba(255,255,255,0.8); font-size:0.78rem; margin-top:2px;">Add learning resources for your courses.</div>
                    </div>
                    <button type="button" class="trigger-btn" style="border:none; background: rgba(255,255,255,0.18); color:#fff;" onclick="closeUploadModal()">Close</button>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" style="padding: 16px;" id="uploadForm">
                <input type="hidden" name="action" value="create" />

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal); margin-bottom:6px;">Course</span>
                        <select name="course_code" required style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Pilih</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= e($c['kode_mk']) ?>"><?= e($c['nama_mata_kuliah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal); margin-bottom:6px;">Material Category</span>
                        <select name="category" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <?php foreach ($categoryMap as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div style="margin-top: 12px;">
                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal); margin-bottom:6px;">Material Title</span>
                        <input required name="title" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                    </label>
                </div>

                <div style="margin-top: 12px;">
                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal); margin-bottom:6px;">Description</span>
                        <input name="description" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                    </label>
                </div>

                <div style="margin-top: 12px;">
                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal); margin-bottom:6px;">File Upload</span>
                        <input type="file" name="attachment" accept="*/*" required style="width:100%; padding:10px; border-radius:10px; border:1px dashed var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;" />
                    </label>
                </div>

                <div style="display:flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; align-items:center;">
                    <label style="display:flex; gap: 10px; align-items:center;">
                        <input type="checkbox" name="publish" value="1" checked />
                        <span style="font-size:0.78rem; font-weight:700; color:var(--charcoal);">Publish</span>
                    </label>

                    <div style="margin-left:auto; display:flex; gap: 10px; flex-wrap:wrap;">
                        <button type="submit" class="trigger-btn btn-blue" style="border:none;">Upload</button>
                        <button type="button" class="trigger-btn btn-red" style="border:none;" onclick="closeUploadModal()">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            var m = document.getElementById('uploadModal');
            if (m) {
                m.style.display = 'flex';
            }
        }
        function closeUploadModal() {
            var m = document.getElementById('uploadModal');
            if (m) {
                m.style.display = 'none';
            }
        }
        (function() {
            var modal = document.getElementById('uploadModal');
            if (!modal) return;
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeUploadModal();
            });
        })();
    </script>
</main>

</div>
</body>
</html>


