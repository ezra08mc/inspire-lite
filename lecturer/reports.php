<?php
require_once "../config/db.php";
require_once "_lib.php";
require_once "_layout.php";

lecturer_require_role();

$success = '';
$error = '';

$semester = trim((string)($_GET['semester'] ?? ''));
$academicYear = trim((string)($_GET['academic_year'] ?? ''));
$courseCode = trim((string)($_GET['course'] ?? ''));

$stats = [
    'total_courses' => '0',
    'total_students' => '0',
    'avg_attendance' => '—',
    'avg_grade' => '—',
    'highest_grade' => '—',
    'lowest_grade' => '—',
    'pass_rate' => '0%'
];

try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT kode_mk) AS c FROM jadwal");
    $stats['total_courses'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT nim) AS c FROM enrollments");
    $stats['total_students'] = (string)($stmt->fetch()['c'] ?? 0);
} catch (PDOException $e) {}

try {
    $attendanceWhere = [];
    $attendanceParams = [];

    if ($courseCode !== '') {
        $attendanceWhere[] = "subj.course_code = :course_code";
        $attendanceParams[':course_code'] = $courseCode;
    }

    if ($semester !== '') {
        $attendanceWhere[] = "subj.semester = :semester";
        $attendanceParams[':semester'] = (int)$semester;
    }

    if ($academicYear !== '') {
        $attendanceWhere[] = "jadwal.academic_year = :academic_year";
        $attendanceParams[':academic_year'] = $academicYear;
    }

    $whereSql = '';
    if (!empty($attendanceWhere)) {
        $whereSql = ' WHERE ' . implode(' AND ', $attendanceWhere);
    }

    $stmt = $pdo->prepare("SELECT AVG(att.attendance_rate) AS avg_rate FROM (SELECT subj.course_code, subj.semester, jadwal.academic_year, (SUM(CASE WHEN ar.status='HADIR' THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS attendance_rate FROM attendance_records ar JOIN schedule jadwal ON jadwal.id = ar.schedule_id JOIN subjects subj ON subj.course_code = jadwal.kode_mk GROUP BY subj.course_code, subj.semester, jadwal.academic_year) att" . $whereSql);
    $stmt->execute($attendanceParams);
    $row = $stmt->fetch();
    $avgRate = $row['avg_rate'] ?? null;
    $stats['avg_attendance'] = $avgRate !== null ? number_format((float)$avgRate, 1, '.', '') . '%' : '—';
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT AVG(grade_point) AS avg_gp, MIN(grade_point) AS min_gp, MAX(grade_point) AS max_gp FROM academic_grades");
    $r = $stmt->fetch();
    if ($r) {
        $stats['avg_grade'] = $r['avg_gp'] !== null ? number_format((float)$r['avg_gp'], 2, '.', '') : '—';
        $stats['lowest_grade'] = $r['min_gp'] !== null ? number_format((float)$r['min_gp'], 2, '.', '') : '—';
        $stats['highest_grade'] = $r['max_gp'] !== null ? number_format((float)$r['max_gp'], 2, '.', '') : '—';
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN grade_letter IN ('A','B','C') THEN 1 ELSE 0 END) AS pass FROM academic_grades");
    $r2 = $stmt->fetch();
    $total = (int)($r2['total'] ?? 0);
    $pass = (int)($r2['pass'] ?? 0);
    $stats['pass_rate'] = $total > 0 ? round(($pass / $total) * 100) . '%' : '0%';
} catch (PDOException $e) {}

$coursePerformance = [];
try {
    $sql = "SELECT subj.course_code, subj.course_name, COUNT(DISTINCT e.nim) AS students, subj.semester, subj.sks, subj.course_type, 0 AS attendance_rate, 0 AS avg_grade, 0 AS pass_rate FROM enrollments e JOIN subjects subj ON subj.course_code = e.course_code";
    $filters = [];
    $params = [];

    if ($courseCode !== '') {
        $filters[] = "subj.course_code = :course_code";
        $params[':course_code'] = $courseCode;
    }

    if ($semester !== '') {
        $filters[] = "subj.semester = :semester";
        $params[':semester'] = (int)$semester;
    }

    if (!empty($filters)) {
        $sql .= " WHERE " . implode(' AND ', $filters);
    }

    $sql .= " GROUP BY subj.course_code, subj.course_name, subj.semester, subj.sks, subj.course_type";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coursePerformance = $stmt->fetchAll();
} catch (PDOException $e) {
    $coursePerformance = [];
}

try {
    foreach ($coursePerformance as &$cp) {
        $course = (string)($cp['course_code'] ?? '');
        $sem = isset($cp['semester']) ? (int)$cp['semester'] : null;

        $attendanceSql = "SELECT (SUM(CASE WHEN ar.status='HADIR' THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS attendance_rate FROM attendance_records ar JOIN schedule s ON s.id = ar.schedule_id WHERE s.kode_mk = :course_code";
        $attendanceParams = [':course_code' => $course];
        if ($sem !== null) {
            $attendanceSql .= " AND s.semester = :semester";
            $attendanceParams[':semester'] = $sem;
        }

        $attStmt = $pdo->prepare($attendanceSql);
        $attStmt->execute($attendanceParams);
        $attRow = $attStmt->fetch();
        $rate = $attRow['attendance_rate'] ?? null;
        $cp['attendance_rate'] = $rate !== null ? number_format((float)$rate, 1, '.', '') . '%' : '—';

        $gradeStmt = $pdo->prepare("SELECT AVG(grade_point) AS avg_gp, SUM(CASE WHEN grade_letter IN ('A','B','C') THEN 1 ELSE 0 END) AS pass_cnt, COUNT(*) AS total_cnt FROM academic_grades WHERE course_code = :course_code");
        $gradeStmt->execute([':course_code' => $course]);
        $g = $gradeStmt->fetch();
        $cp['avg_grade'] = $g && $g['avg_gp'] !== null ? number_format((float)$g['avg_gp'], 2, '.', '') : '—';
        $cp['pass_rate'] = ($g && (int)$g['total_cnt'] > 0) ? round(((int)$g['pass_cnt'] / (int)$g['total_cnt']) * 100) . '%' : '0%';
    }
    unset($cp);
} catch (PDOException $e) {}

$teachingSessions = [];
$materialsUploaded = [];
$assignmentsCreated = [];
$announcementsPublished = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM jadwal");
    $teachingSessions = $stmt->fetch();
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM active_tasks WHERE user_id = :user_id");
    $stmt->execute([':user_id' => (int)$_SESSION['user_id']]);
    $materialsUploaded = $stmt->fetch();
} catch (PDOException $e) {
    $materialsUploaded = ['c' => 0];
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM active_tasks WHERE user_id = :user_id");
    $stmt->execute([':user_id' => (int)$_SESSION['user_id']]);
    $assignmentsCreated = $stmt->fetch();
} catch (PDOException $e) {
    $assignmentsCreated = ['c' => 0];
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM announcements");
    $announcementsPublished = $stmt->fetch();
} catch (PDOException $e) {
    $announcementsPublished = ['c' => 0];
}

$recentActivities = [];
try {
    $stmt = $pdo->query("SELECT id, type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC LIMIT 6");
    $recentActivities = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentActivities = [];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Reports & Analytics</h1>
            <p>Monitor academic performance and teaching activities.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Courses</span><span class="metric-value"><?= e($stats['total_courses']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Total Students</span><span class="metric-value"><?= e($stats['total_students']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Avg Attendance</span><span class="metric-value"><?= e($stats['avg_attendance']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Avg Grade</span><span class="metric-value"><?= e($stats['avg_grade']) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Attendance Analytics</h3>
                <span class="action-link" style="cursor:default;">Trend: Stable</span>
            </div>

            <div class="agenda-table-wrapper" style="margin: 0 0 16px;">
                <div class="agenda-table-header" style="grid-template-columns: 1fr 0.7fr 0.8fr 0.9fr;">
                    <span class="col-head">Course</span>
                    <span class="col-head">Students</span>
                    <span class="col-head">Attendance Rate</span>
                    <span class="col-head">Pass Rate</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($coursePerformance)): ?>
                        <div class="empty-fallback-text border-box-pad">No course performance data.</div>
                    <?php else: ?>
                        <?php foreach ($coursePerformance as $cp): ?>
                            <div class="agenda-table-row" style="grid-template-columns: 1fr 0.7fr 0.8fr 0.9fr;">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= e((string)($cp['course_name'] ?? '')) ?></span>
                                    <span class="item-sub-clock"><?= e((string)($cp['course_code'] ?? '')) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e((string)($cp['students'] ?? 0)) ?></span>
                                    <span class="item-sub-clock">Students</span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e((string)($cp['attendance_rate'] ?? '—')) ?></span>
                                    <span class="item-sub-clock">Attendance</span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e((string)($cp['pass_rate'] ?? '0%')) ?></span>
                                    <span class="item-sub-clock">Pass</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-top" style="margin-top: 10px;">
                <h3>Academic Performance Analytics</h3>
            </div>

            <div class="announcements-feed" style="margin-top: -6px; padding: 0 16px 16px;">
                <div class="annc-node">
                    <div class="annc-top-line">
                        <div class="annc-icon-frame blue">
                            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                        </div>
                        <div class="annc-title-area">
                            <h4>Highest / Lowest</h4>
                            <p class="annc-body-text" style="margin:6px 0 0 0;">Max: <?= e($stats['highest_grade']) ?> · Min: <?= e($stats['lowest_grade']) ?></p>
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
                            <p class="annc-body-text" style="margin:6px 0 0 0;"><?= e($stats['pass_rate']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-top" style="margin-top: 10px;">
                <h3>Lecturer Activity Summary</h3>
            </div>

            <div class="tasks-vertical-stack" style="margin: 0; padding: 0 16px 16px; border: none;">
                <div class="task-node">
                    <div class="task-checkbox-frame" style="background-color: rgba(0, 123, 236, 0.08); border-color: rgba(0, 123, 236, 0.25);">
                        <svg viewBox="0 0 24 24" class="check-svg" style="fill: #007BEC;"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    </div>
                    <div class="task-node-details">
                        <span class="task-node-title">Total teaching sessions</span>
                        <span class="task-node-time">Sessions: <?= e((string)($teachingSessions['c'] ?? 0)) ?></span>
                    </div>
                </div>
                <div class="task-node">
                    <div class="task-checkbox-frame" style="background-color: rgba(255, 59, 48, 0.08); border-color: rgba(255, 59, 48, 0.25);">
                        <svg viewBox="0 0 24 24" class="check-svg" style="fill: #FF3B30;"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2z"/></svg>
                    </div>
                    <div class="task-node-details">
                        <span class="task-node-title">Materials uploaded</span>
                        <span class="task-node-time">Uploads: <?= e((string)($materialsUploaded['c'] ?? 0)) ?></span>
                    </div>
                </div>
                <div class="task-node">
                    <div class="task-checkbox-frame" style="background-color: rgba(124, 58, 237, 0.08); border-color: rgba(124, 58, 237, 0.25);">
                        <svg viewBox="0 0 24 24" class="check-svg" style="fill: #7c3aed;"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2z"/></svg>
                    </div>
                    <div class="task-node-details">
                        <span class="task-node-title">Assignments created</span>
                        <span class="task-node-time">Created: <?= e((string)($assignmentsCreated['c'] ?? 0)) ?></span>
                    </div>
                </div>
                <div class="task-node">
                    <div class="task-checkbox-frame" style="background-color: rgba(0, 123, 236, 0.08); border-color: rgba(0, 123, 236, 0.25);">
                        <svg viewBox="0 0 24 24" class="check-svg" style="fill: #007BEC;"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                    </div>
                    <div class="task-node-details">
                        <span class="task-node-title">Announcements published</span>
                        <span class="task-node-time">Published: <?= e((string)($announcementsPublished['c'] ?? 0)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Report Filters</h3>
                <a href="dashboard.php" class="action-link">Kembali</a>
            </div>

            <div style="padding: 0 16px 16px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Semester</span>
                            <select name="semester" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">All</option>
                                <?php for ($i=1; $i<=6; $i++): ?>
                                    <option value="<?= e((string)$i) ?>" <?= $semester === (string)$i ? 'selected' : '' ?>>Semester <?= e((string)$i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Academic Year</span>
                            <input name="academic_year" value="<?= e($academicYear) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" type="text" />
                        </label>
                    </div>

                    <label style="display:block;">
                        <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Course</span>
                        <select name="course" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All</option>
                            <?php foreach ($coursePerformance as $cp): ?>
                                <?php $code = (string)($cp['course_code'] ?? ''); $name = (string)($cp['course_name'] ?? ''); if ($code === '') continue; ?>
                                <option value="<?= e($code) ?>" <?= $courseCode === $code ? 'selected' : '' ?>><?= e($code) ?> - <?= e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Apply</button>
                    <a href="reports.php" class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none;">Reset</a>
                </form>
            </div>

            <div class="card-top" style="margin: 0 16px 10px;">
                <h3>Export Options</h3>
            </div>

            <div class="tasks-vertical-stack" style="border: none; padding: 0 16px 16px;">
                <div class="task-node">
                    <div class="task-node-details">
                        <span class="task-node-title">Export PDF</span>
                        <span class="task-node-time">Not connected in this build</span>
                    </div>
                    <div style="margin-left:auto;">
                        <button type="button" class="trigger-btn btn-blue" style="border:none; padding:8px 12px;" onclick="window.print()">Print</button>
                    </div>
                </div>
                <div class="task-node">
                    <div class="task-node-details">
                        <span class="task-node-title">Export Excel</span>
                        <span class="task-node-time">Not connected in this build</span>
                    </div>
                    <div style="margin-left:auto;">
                        <button type="button" class="trigger-btn btn-red" style="border:none; padding:8px 12px;" onclick="alert('Export Excel not connected in this build.')">Export</button>
                    </div>
                </div>
            </div>

            <div class="card-top" style="margin: 0 16px 10px;">
                <h3>Recent Academic Activities</h3>
            </div>

            <div class="announcements-feed" style="padding: 0 16px 16px;">
                <?php if (empty($recentActivities)): ?>
                    <div class="empty-fallback-text border-box-pad">No recent activities.</div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $ra): ?>
                        <div class="annc-node">
                            <div class="annc-top-line">
                                <div class="annc-icon-frame <?= e($ra['badge_class'] ?? 'blue') ?>">
                                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                </div>
                                <div class="annc-title-area">
                                    <div class="annc-meta">
                                        <span class="annc-date"><?= e((string)($ra['date_text'] ?? '')) ?></span>
                                        <span class="label-badge red"><?= e((string)($ra['type'] ?? '')) ?></span>
                                    </div>
                                    <h4><?= e((string)($ra['title'] ?? '')) ?></h4>
                                </div>
                            </div>
                            <p class="annc-body-text"><?= e(mb_strimwidth((string)($ra['content'] ?? ''), 0, 120, '…')) ?></p>
                            <span class="annc-dept"><?= e((string)($ra['author'] ?? '')) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>


