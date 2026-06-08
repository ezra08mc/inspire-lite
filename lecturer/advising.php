<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int)($_SESSION['user_id'] ?? 0);

$success = '';
$error = '';

$search = trim((string)($_GET['q'] ?? ''));
$program = trim((string)($_GET['program'] ?? ''));
$academicYear = trim((string)($_GET['academic_year'] ?? ''));
$academicStatus = trim((string)($_GET['academic_status'] ?? ''));
$advisingStatus = trim((string)($_GET['advising_status'] ?? ''));

$selectedNim = trim((string)($_GET['nim'] ?? ''));

function advising_safe_str($v): string {
    return (string)$v;
}

function gpa_bucket(?float $gpa): string {
    if ($gpa === null) return '—';
    if ($gpa < 2.5) return 'Low GPA';
    if ($gpa < 3.0) return 'Fair GPA';
    return 'Good GPA';
}

function academic_status_badge(?string $s): string {
    $s = (string)($s ?? '');
    if ($s === 'ON_TRACK') return 'label-badge red';
    if ($s === 'AT_RISK') return 'label-badge red';
    return '';
}

$advisedSessions = [];
$advisees = [];
$profile = null;

$programs = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT study_program FROM students ORDER BY study_program ASC");
    $programs = $stmt->fetchAll();
} catch (PDOException $e) {
    $programs = [];
}

$academicYears = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT academic_year FROM students ORDER BY academic_year DESC");
    $academicYears = $stmt->fetchAll();
} catch (PDOException $e) {
    $academicYears = [];
}

$defaultAcademicYear = '';
if (!empty($academicYears)) {
    $defaultAcademicYear = (string)($academicYears[0]['academic_year'] ?? '');
}

if ($academicYear === '') {
    $academicYear = $defaultAcademicYear;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'create'));

    $nim = trim((string)($_POST['nim'] ?? ''));
    $tanggal = trim((string)($_POST['tanggal'] ?? ''));
    $topik = trim((string)($_POST['topik'] ?? ''));
    $catatan = trim((string)($_POST['catatan'] ?? ''));
    $rekomendasi = trim((string)($_POST['rekomendasi'] ?? ''));
    $followup = trim((string)($_POST['followup'] ?? ''));

    $id = (int)($_POST['id'] ?? 0);

    if ($nim === '' || $tanggal === '') {
        $error = 'NIM dan tanggal wajib diisi.';
    } else {
        try {
            if ($action === 'create') {
                $msg = trim($topik !== '' ? $topik : $catatan);
                $stmt = $pdo->prepare("INSERT INTO student_notifications (title, content, category, sender, created_at) VALUES (:title, :content, :category, :sender, NOW())");
                $stmt->execute([
                    ':title' => 'Advising Session',
                    ':content' => 'NIM:' . $nim . ' | ' . $msg . ' | ' . ($rekomendasi !== '' ? 'Rekomendasi:' . $rekomendasi : '') . ' | ' . ($followup !== '' ? 'Follow-up:' . $followup : ''),
                    ':category' => 'BIMBINGAN',
                    ':sender' => 'Dosen ' . $lecturerId,
                ]);
                $success = 'Sesi advising tersimpan.';
                header('Location: advising.php');
                exit();
            }

            if ($action === 'delete') {
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM student_notifications WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $success = 'Rekaman advising dihapus.';
                }
                header('Location: advising.php?nim=' . urlencode($nim));
                exit();
            }

            if ($action === 'edit') {
                if ($id > 0) {
                    $msg = trim($topik !== '' ? $topik : $catatan);
                    $stmt = $pdo->prepare("UPDATE student_notifications SET title = :title, content = :content, category = :category, sender = :sender, created_at = :created_at WHERE id = :id");
                    $stmt->execute([
                        ':title' => 'Advising Session',
                        ':content' => 'NIM:' . $nim . ' | ' . $msg . ' | ' . ($rekomendasi !== '' ? 'Rekomendasi:' . $rekomendasi : '') . ' | ' . ($followup !== '' ? 'Follow-up:' . $followup : ''),
                        ':category' => 'BIMBINGAN',
                        ':sender' => 'Dosen ' . $lecturerId,
                        ':created_at' => $tanggal,
                        ':id' => $id,
                    ]);
                    $success = 'Sesi advising diperbarui.';
                }
                header('Location: advising.php?nim=' . urlencode($nim));
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan sesi advising.';
        }
    }
}

try {
    $sql = "SELECT nim, first_name, last_name, study_program, academic_year, gpa, academic_status FROM students";

    $filters = [];
    $params = [];

    if ($search !== '') {
        $filters[] = "(nim LIKE :q OR first_name LIKE :q OR last_name LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }

    if ($program !== '') {
        $filters[] = "study_program = :program";
        $params[':program'] = $program;
    }

    if ($academicYear !== '') {
        $filters[] = "academic_year = :academic_year";
        $params[':academic_year'] = $academicYear;
    }

    if ($academicStatus !== '') {
        $filters[] = "academic_status = :academic_status";
        $params[':academic_status'] = $academicStatus;
    }

    if ($advisingStatus !== '') {
        if ($advisingStatus === 'REQUIRES_ATTENTION') {
            $filters[] = "(gpa IS NOT NULL AND gpa < 2.7)";
        } elseif ($advisingStatus === 'ON_TRACK') {
            $filters[] = "(gpa IS NOT NULL AND gpa >= 2.7)";
        }
    }

    if (!empty($filters)) {
        $sql .= " WHERE " . implode(' AND ', $filters);
    }

    $sql .= " ORDER BY academic_year DESC, last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $advisees = $stmt->fetchAll();
} catch (PDOException $e) {
    $advisees = [];
}

$profile = null;
if ($selectedNim !== '') {
    try {
        $stmt = $pdo->prepare("SELECT nim, first_name, last_name, study_program, academic_year, gpa, academic_status FROM students WHERE nim = :nim LIMIT 1");
        $stmt->execute([':nim' => $selectedNim]);
        $profile = $stmt->fetch();
    } catch (PDOException $e) {
        $profile = null;
    }
}

$adviseesStats = [
    'total' => '0',
    'active' => '0',
    'final_project' => '0',
    'graduation_candidates' => '0',
    'attention' => '0'
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM students");
    $adviseesStats['total'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM students WHERE academic_status = 'ON_TRACK'");
    $adviseesStats['active'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM students WHERE academic_status = 'AT_RISK' AND (gpa IS NULL OR gpa < 2.7)");
    $adviseesStats['attention'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM students WHERE academic_year >= (YEAR(CURDATE()) - 1)");
    $adviseesStats['final_project'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

$adviseesStats['graduation_candidates'] = $adviseesStats['final_project'];

try {
    if ($selectedNim !== '') {
        $stmt = $pdo->prepare("SELECT id, title, content, category, sender, created_at FROM student_notifications WHERE category = 'BIMBINGAN' ORDER BY id DESC LIMIT 20");
        $stmt->execute();
        $allSessions = $stmt->fetchAll();

        $sessions = [];
        foreach ($allSessions as $s) {
            $content = (string)($s['content'] ?? '');
            if (strpos($content, 'NIM:' . $selectedNim) === 0) {
                $sessions[] = $s;
            } else {
                $sessions[] = $s;
            }
        }
        $advisedSessions = $sessions;
    } else {
        $stmt = $pdo->prepare("SELECT id, title, content, category, sender, created_at FROM student_notifications WHERE category = 'BIMBINGAN' ORDER BY id DESC LIMIT 15");
        $stmt->execute();
        $advisedSessions = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $advisedSessions = [];
}

try {
    $lowGpaStudents = [];
    $stmt = $pdo->query("SELECT nim, first_name, last_name, gpa FROM students WHERE gpa IS NOT NULL AND gpa < 2.7 ORDER BY gpa ASC LIMIT 5");
    $lowGpaStudents = $stmt->fetchAll();
} catch (PDOException $e) {
    $lowGpaStudents = [];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Academic Advising</h1>
            <p>Monitoring kemajuan akademik dan sesi bimbingan dosen.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Advisees</span><span class="metric-value"><?= htmlspecialchars($adviseesStats['total']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Active Advisees</span><span class="metric-value"><?= htmlspecialchars($adviseesStats['active']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Final Project</span><span class="metric-value"><?= htmlspecialchars($adviseesStats['final_project']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Candidates</span><span class="metric-value"><?= htmlspecialchars($adviseesStats['graduation_candidates']) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Advisee List</h3>
                <span class="action-link" style="cursor:default;">Attention: <?= htmlspecialchars($adviseesStats['attention']) ?></span>
            </div>

            <div style="padding: 0 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search student" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Study Program</span>
                            <select name="program" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= htmlspecialchars((string)$p['study_program']) ?>" <?= $program === (string)$p['study_program'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['study_program']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Academic Year</span>
                            <select name="academic_year" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= htmlspecialchars((string)$ay['academic_year']) ?>" <?= $academicYear === (string)$ay['academic_year'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$ay['academic_year']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Academic Status</span>
                            <select name="academic_status" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <option value="ON_TRACK" <?= $academicStatus === 'ON_TRACK' ? 'selected' : '' ?>>On Track</option>
                                <option value="AT_RISK" <?= $academicStatus === 'AT_RISK' ? 'selected' : '' ?>>At Risk</option>
                            </select>
                        </label>

                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Advising Status</span>
                            <select name="advising_status" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <option value="REQUIRES_ATTENTION" <?= $advisingStatus === 'REQUIRES_ATTENTION' ? 'selected' : '' ?>>Requires Attention</option>
                                <option value="ON_TRACK" <?= $advisingStatus === 'ON_TRACK' ? 'selected' : '' ?>>On Track</option>
                            </select>
                        </label>
                    </div>

                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Apply</button>
                    <a href="advising.php" class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none;">Reset</a>
                </form>
            </div>

            <div class="agenda-table-wrapper" style="margin: 14px 16px 16px;">
                <div class="agenda-table-header" style="grid-template-columns: 140px 1fr 130px;">
                    <span class="col-head">STUDENT</span>
                    <span class="col-head">PROGRAM</span>
                    <span class="col-head">STATUS</span>
                </div>

                <div class="agenda-rows-stack">
                    <?php if (empty($advisees)): ?>
                        <div class="empty-fallback-text border-box-pad">No advisees found.</div>
                    <?php else: ?>
                        <?php foreach ($advisees as $st): ?>
                            <?php
                                $gpaVal = $st['gpa'] ?? null;
                                $isAttention = ($gpaVal !== null && (float)$gpaVal < 2.7);
                                $badgeClass = $isAttention ? 'label-badge red' : 'label-badge';
                            ?>
                            <div class="agenda-table-row" style="grid-template-columns: 140px 1fr 130px;">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></span>
                                    <span class="item-sub-clock">NIM <?= htmlspecialchars($st['nim']) ?></span>
                                    <a href="advising.php?nim=<?= urlencode((string)$st['nim']) ?>" class="action-link" style="display:inline-block; margin-top: 6px;">View</a>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars($st['study_program']) ?></span>
                                    <span class="item-sub-clock"><?= htmlspecialchars($st['academic_year']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars((string)($st['academic_status'] ?? '')) ?></span>
                                    <span class="label-badge red" style="display:inline-flex; margin-top: 6px; opacity: <?= $isAttention ? '1' : '0.65' ?>;"><?= $isAttention ? 'Attention' : 'OK' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Advising Sessions</h3>
                <a href="dashboard.php" class="action-link">Back</a>
            </div>

            <div style="padding: 0 16px; margin-top: -6px;">
                <div class="card-top" style="margin-bottom: 10px;">
                    <h3 style="font-size:0.88rem;">Student Profile</h3>
                </div>

                <?php if ($profile === null && $selectedNim === ''): ?>
                    <div class="empty-fallback-text border-box-pad">Select a student to view advising profile.</div>
                <?php elseif ($profile === null): ?>
                    <div class="empty-fallback-text border-box-pad">Student not found.</div>
                <?php else: ?>
                    <div class="announcements-feed" style="margin-top: 10px;">
                        <div class="annc-node">
                            <div class="annc-top-line">
                                <div class="annc-icon-frame blue">
                                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="annc-title-area">
                                    <h4><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></h4>
                                    <p class="annc-body-text" style="margin:6px 0 0 0;">NIM <?= htmlspecialchars($profile['nim']) ?> · <?= htmlspecialchars($profile['study_program']) ?></p>
                                </div>
                            </div>
                            <div class="annc-body-text" style="margin-top: 8px;">
                                <span style="font-weight:700; color: var(--charcoal);">Academic Year:</span> <?= htmlspecialchars($profile['academic_year']) ?>
                                <br />
                                <span style="font-weight:700; color: var(--charcoal);">GPA:</span> <?= htmlspecialchars((string)($profile['gpa'] ?? '—')) ?>
                                <br />
                                <span style="font-weight:700; color: var(--charcoal);">Academic Status:</span> <?= htmlspecialchars((string)($profile['academic_status'] ?? '')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-top" style="margin-top: 12px;">
                        <h3 style="font-size:0.88rem;">Create / Edit Advising Session</h3>
                        <span class="action-link" style="cursor:default;">NIM: <?= htmlspecialchars((string)$profile['nim']) ?></span>
                    </div>

                    <form method="POST" style="padding: 16px;" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create" />
                        <input type="hidden" name="nim" value="<?= htmlspecialchars((string)$profile['nim']) ?>" />

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display:block;">
                                <span>Session Date</span>
                                <input required type="date" name="tanggal" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                            <label style="display:block;">
                                <span>Session Topic</span>
                                <input type="text" name="topik" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                        </div>

                        <div style="margin-top: 12px;">
                            <label style="display:block;">
                                <span>Session Notes</span>
                                <textarea name="catatan" rows="4" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;"></textarea>
                            </label>
                        </div>

                        <div style="margin-top: 12px;">
                            <label style="display:block;">
                                <span>Recommendations</span>
                                <input type="text" name="rekomendasi" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                        </div>

                        <div style="margin-top: 12px;">
                            <label style="display:block;">
                                <span>Follow-Up Actions</span>
                                <input type="text" name="followup" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                        </div>

                        <?php if ($success): ?>
                            <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px 0 0;"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px 0 0;"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <div style="margin-top: 16px; display:flex; gap: 10px; flex-wrap:wrap;">
                            <button type="submit" class="trigger-btn btn-blue" style="border:none;">Save Session</button>
                            <a href="advising.php" class="trigger-btn btn-red" style="text-decoration:none;">Back</a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="card-top" style="margin-top: 14px;">
                    <h3 style="font-size:0.88rem;">History</h3>
                    <a href="advising.php" class="action-link">Refresh</a>
                </div>

                <div class="tasks-vertical-stack" style="margin: 0;">
                    <?php if (empty($advisedSessions)): ?>
                        <div class="empty-fallback-text border-box-pad">No advising records.</div>
                    <?php else: ?>
                        <?php foreach ($advisedSessions as $s): ?>
                            <?php
                                $content = (string)($s['content'] ?? '');
                                $created = (string)($s['created_at'] ?? '');
                                $rawParts = explode('|', $content);
                                $topicPart = '';
                                foreach ($rawParts as $p) {
                                    $p = trim($p);
                                    if (stripos($p, 'NIM:') === 0) continue;
                                    if ($topicPart === '') {
                                        $topicPart = $p;
                                    } else {
                                        break;
                                    }
                                }
                            ?>
                            <div class="task-node">
                                <div class="task-checkbox-frame" style="border-color: var(--border); background:#fff;">
                                    <svg viewBox="0 0 24 24" class="check-svg" style="fill: var(--primary);"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                </div>
                                <div class="task-node-details">
                                    <span class="task-node-title"><?= htmlspecialchars($topicPart !== '' ? $topicPart : ($s['title'] ?? 'Advising')) ?></span>
                                    <span class="task-node-time"><?= htmlspecialchars($created) ?></span>
                                </div>
                                <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>" />
                                        <input type="hidden" name="nim" value="<?= htmlspecialchars($selectedNim !== '' ? $selectedNim : '') ?>" />
                                        <button type="submit" class="trigger-btn btn-red" style="border:none; padding:8px 12px;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>


