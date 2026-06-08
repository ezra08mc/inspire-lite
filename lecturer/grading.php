<?php
require_once "../config/db.php";
require_once "_layout.php";

$success = '';
$error = '';

$courseCode = trim((string)($_GET['course_code'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));
$semester = trim((string)($_GET['semester'] ?? ''));
$academicYear = trim((string)($_GET['academic_year'] ?? ''));

$gradePoint = trim((string)($_POST['grade_point'] ?? ''));
$gradeLetter = trim((string)($_POST['grade_letter'] ?? ''));

$nim = trim((string)($_POST['nim'] ?? ''));
$postedCourseCode = trim((string)($_POST['course_code'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'save'));
    if ($action === 'bulk') {
        $raw = (string)($_POST['bulk_input'] ?? '');
        $lines = preg_split('/\r\n|\n|\r/', $raw);
        $saved = 0;
        $failed = 0;
        $pdo->beginTransaction();
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $parts = preg_split('/\s*[,;|]\s*/', $line);
                if (count($parts) < 3) {
                    $failed++;
                    continue;
                }
                $bNim = trim((string)$parts[0]);
                $bCourse = trim((string)$parts[1]);
                $bPoint = trim((string)$parts[2]);
                $bLetter = trim((string)($parts[3] ?? ''));
                if ($bNim === '' || $bCourse === '' || $bPoint === '') {
                    $failed++;
                    continue;
                }
                if ($bLetter === '') {
                    $bLetter = 'E';
                }
                $check = $pdo->prepare("SELECT id FROM academic_grades WHERE nim = :nim AND course_code = :course_code LIMIT 1");
                $check->execute([':nim' => $bNim, ':course_code' => $bCourse]);
                $existing = $check->fetch();
                if ($existing) {
                    $up = $pdo->prepare("UPDATE academic_grades SET grade_point = :grade_point, grade_letter = :grade_letter WHERE id = :id");
                    $up->execute([':grade_point' => (float)$bPoint, ':grade_letter' => $bLetter, ':id' => (int)$existing['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO academic_grades (nim, course_code, grade_point, grade_letter) VALUES (:nim, :course_code, :grade_point, :grade_letter)");
                    $ins->execute([':nim' => $bNim, ':course_code' => $bCourse, ':grade_point' => (float)$bPoint, ':grade_letter' => $bLetter]);
                }
                $saved++;
            }
            $pdo->commit();
            $success = 'Bulk grading saved. Saved: ' . $saved . '. Failed: ' . $failed . '.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to process bulk grading.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM academic_grades WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = 'Grade deleted.';
            } catch (PDOException $e) {
                $error = 'Failed to delete grade.';
            }
        }
    } else {
        $nim = trim((string)($_POST['nim'] ?? ''));
        $course_code = trim((string)($_POST['course_code'] ?? ''));
        $gp = trim((string)($_POST['grade_point'] ?? ''));
        $gl = trim((string)($_POST['grade_letter'] ?? ''));

        if ($nim === '' || $course_code === '' || $gp === '' || $gl === '') {
            $error = 'Semua field wajib diisi.';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM academic_grades WHERE nim = :nim AND course_code = :course_code LIMIT 1");
                $check->execute([':nim' => $nim, ':course_code' => $course_code]);
                $existing = $check->fetch();
                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE academic_grades SET grade_point = :grade_point, grade_letter = :grade_letter WHERE id = :id");
                    $stmt->execute([':grade_point' => (float)$gp, ':grade_letter' => $gl, ':id' => (int)$existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO academic_grades (nim, course_code, grade_point, grade_letter) VALUES (:nim, :course_code, :grade_point, :grade_letter)");
                    $stmt->execute([':nim' => $nim, ':course_code' => $course_code, ':grade_point' => (float)$gp, ':grade_letter' => $gl]);
                }
                $success = 'Nilai berhasil disimpan.';
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan nilai. Pastikan NIM dan course_code valid.';
            }
        }
    }
}

$stats = [
    'total_courses' => '0',
    'total_students' => '0',
    'graded_assignments' => '0',
    'pending_grades' => '0',
    'average_grade' => '—',
    'highest_grade' => '—',
    'lowest_grade' => '—',
    'pass_rate' => '0%'
];

try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT course_code) AS c FROM academic_grades");
    $stats['total_courses'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT nim) AS c FROM academic_grades");
    $stats['total_students'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM academic_grades");
    $stats['graded_assignments'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT AVG(grade_point) AS avg_gp, MIN(grade_point) AS min_gp, MAX(grade_point) AS max_gp FROM academic_grades");
    $row = $stmt->fetch();
    if ($row) {
        $avg = $row['avg_gp'];
        $min = $row['min_gp'];
        $max = $row['max_gp'];
        $stats['average_grade'] = $avg !== null ? number_format((float)$avg, 2, '.', '') : '—';
        $stats['lowest_grade'] = $min !== null ? number_format((float)$min, 2, '.', '') : '—';
        $stats['highest_grade'] = $max !== null ? number_format((float)$max, 2, '.', '') : '—';
    }
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN grade_letter IN ('A','B','C') THEN 1 ELSE 0 END) AS pass FROM academic_grades");
    $r = $stmt->fetch();
    $total = (int)($r['total'] ?? 0);
    $pass = (int)($r['pass'] ?? 0);
    $stats['pass_rate'] = $total > 0 ? round(($pass / $total) * 100) . '%' : '0%';
} catch (PDOException $e) {}

$filters = [];
$params = [];

$sql = "SELECT g.id, g.nim, g.course_code, s.first_name, s.last_name, subj.course_name, subj.sks, subj.semester, g.grade_point, g.grade_letter FROM academic_grades g JOIN students s ON s.nim = g.nim JOIN subjects subj ON subj.course_code = g.course_code";

if ($courseCode !== '') {
    $filters[] = "g.course_code = :course_code";
    $params[':course_code'] = $courseCode;
}

if ($kelas !== '') {
    $filters[] = "g.course_code IN (SELECT kode_mk FROM jadwal WHERE kelas = :kelas)";
    $params[':kelas'] = $kelas;
}

if ($semester !== '') {
    $filters[] = "subj.semester = :semester";
    $params[':semester'] = (int)$semester;
}

if (!empty($filters)) {
    $sql .= " WHERE " . implode(' AND ', $filters);
}

$sql .= " ORDER BY g.id DESC";

$grades = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll();
} catch (PDOException $e) {
    $grades = [];
}

$courses = [];
$classOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT course_code, course_name FROM subjects ORDER BY course_name ASC");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

try {
    $stmt = $pdo->query("SELECT DISTINCT kelas FROM jadwal ORDER BY kelas ASC");
    $classOptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $classOptions = [];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Grade Management</h1>
            <p>Kelola nilai akademik dari tabel <code>academic_grades</code>.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Courses</span><span class="metric-value"><?= htmlspecialchars($stats['total_courses']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Total Students</span><span class="metric-value"><?= htmlspecialchars($stats['total_students']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Graded</span><span class="metric-value"><?= htmlspecialchars($stats['graded_assignments']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Average</span><span class="metric-value"><?= htmlspecialchars($stats['average_grade']) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Add / Edit Grades</h3>
                <a href="dashboard.php" class="action-link">Back</a>
            </div>

            <?php if ($success): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="padding: 16px;">
                <div style="display:grid; gap: 12px;">
                    <div>
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Student NIM</span>
                        <input required name="nim" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </div>
                    <div>
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Course Code</span>
                        <input required name="course_code" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Nilai Angka (grade_point)</span>
                            <input required name="grade_point" type="number" step="0.01" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                        </div>
                        <div>
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Grade Letter</span>
                            <select required name="grade_letter" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 16px; display:flex; gap: 10px; flex-wrap:wrap;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Save Grade</button>
                    <button type="submit" name="action" value="bulk" class="trigger-btn btn-red" style="border:none;" formaction="grading.php" formmethod="POST" formnovalidate="formnovalidate">Bulk (see below)</button>
                </div>
            </form>

            <div class="card-top" style="margin-top: 10px;">
                <h3>Bulk Grade Input</h3>
            </div>

            <form method="POST" enctype="multipart/form-data" style="padding: 16px;" id="bulkForm">
                <input type="hidden" name="action" value="bulk" />
                <label style="display:block; margin-bottom: 10px;">
                    <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Format per baris: NIM, KODE_MK, GRADE_POINT, [GRADE_LETTER]</span>
                    <textarea name="bulk_input" rows="6" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none; resize:vertical;"></textarea>
                </label>
                <button type="submit" class="trigger-btn btn-blue" style="border:none;">Save Bulk</button>
            </form>

            <div class="card-top" style="margin-top: 6px;">
                <h3>Analytics</h3>
            </div>
            <div class="announcements-feed" style="margin-top: -6px; padding: 0 16px 16px;">
                <div class="annc-node">
                    <div class="annc-top-line">
                        <div class="annc-icon-frame blue">
                            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                        </div>
                        <div class="annc-title-area">
                            <h4>Highest / Lowest</h4>
                            <p class="annc-body-text" style="margin:6px 0 0 0;">Max: <?= htmlspecialchars($stats['highest_grade']) ?> · Min: <?= htmlspecialchars($stats['lowest_grade']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="annc-node">
                    <div class="annc-top-line">
                        <div class="annc-icon-frame red">
                            <svg viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 16h-2v-2h2v2zm0-4h-2V8h2v6z"/></svg>
                        </div>
                        <div class="annc-title-area">
                            <h4>Pass Rate</h4>
                            <p class="annc-body-text" style="margin:6px 0 0 0;"><?= htmlspecialchars($stats['pass_rate']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Grade List</h3>
                <span class="action-link" style="cursor:default;">Total: <?= count($grades) ?></span>
            </div>

            <div style="padding: 0 16px 12px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Course</span>
                            <select name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= htmlspecialchars((string)$c['course_code']) ?>" <?= $courseCode === (string)$c['course_code'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Class</span>
                            <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <?php foreach ($classOptions as $cl): ?>
                                    <option value="<?= htmlspecialchars((string)$cl['kelas']) ?>" <?= $kelas === (string)$cl['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$cl['kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Semester</span>
                            <select name="semester" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
                                <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
                                <option value="3" <?= $semester === '3' ? 'selected' : '' ?>>Semester 3</option>
                                <option value="4" <?= $semester === '4' ? 'selected' : '' ?>>Semester 4</option>
                                <option value="5" <?= $semester === '5' ? 'selected' : '' ?>>Semester 5</option>
                                <option value="6" <?= $semester === '6' ? 'selected' : '' ?>>Semester 6</option>
                            </select>
                        </label>
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Academic Year</span>
                            <input type="text" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                        </label>
                    </div>
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Apply</button>
                    <a href="grading.php" class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none;">Reset</a>
                </form>
            </div>

            <div class="agenda-table-wrapper" style="margin-top: 10px;">
                <div class="agenda-table-header" style="grid-template-columns: 1.1fr 0.8fr 0.8fr 0.9fr;">
                    <span class="col-head">STUDENT</span>
                    <span class="col-head">COURSE</span>
                    <span class="col-head">SCORE</span>
                    <span class="col-head">ACTION</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($grades)): ?>
                        <div class="empty-fallback-text border-box-pad">Belum ada nilai.</div>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <div class="agenda-table-row" style="grid-template-columns: 1.1fr 0.8fr 0.8fr 0.9fr;">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['first_name'].' '.$g['last_name']) ?></span>
                                    <span class="item-sub-clock">NIM <?= htmlspecialchars($g['nim']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['course_code']) ?></span>
                                    <span class="item-sub-clock"><?= htmlspecialchars($g['course_name']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['grade_letter']) ?></span>
                                    <span class="item-sub-clock">Point <?= htmlspecialchars((string)$g['grade_point']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>" />
                                            <button type="submit" class="trigger-btn btn-red" style="border:none; padding:8px 12px;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 0 16px 16px;">
                <div class="card-top" style="margin-top: 18px;">
                    <h3>Export</h3>
                </div>
                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="trigger-btn btn-blue" style="border:none;" onclick="window.location='grading.php?export=course';">Export Course Gradebook</button>
                    <button type="button" class="trigger-btn btn-red" style="border:none;" onclick="window.location='grading.php?export=student';">Export Student Summary</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
if (isset($_GET['export'])) {
    $exportMode = (string)$_GET['export'];
    $rows = [];
    try {
        $stmt = $pdo->query("SELECT g.nim, s.first_name, s.last_name, g.course_code, subj.course_name, g.grade_point, g.grade_letter FROM academic_grades g JOIN students s ON s.nim = g.nim JOIN subjects subj ON subj.course_code = g.course_code ORDER BY g.course_code ASC, s.last_name ASC");
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        $rows = [];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="grading_export_' . $exportMode . '.csv"');
    $out = fopen('php://output', 'w');
    if ($exportMode === 'student') {
        fputcsv($out, ['NIM', 'Nama', 'Course', 'Grade Point', 'Grade Letter']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['nim'], $r['first_name'].' '.$r['last_name'], $r['course_code'].' - '.$r['course_name'], $r['grade_point'], $r['grade_letter']]);
        }
    } else {
        fputcsv($out, ['Course', 'Kode', 'NIM', 'Nama', 'Grade Point', 'Grade Letter']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['course_name'], $r['course_code'], $r['nim'], $r['first_name'].' '.$r['last_name'], $r['grade_point'], $r['grade_letter']]);
        }
    }
    fclose($out);
    exit();
}
?>

</div>
</body>
</html>


