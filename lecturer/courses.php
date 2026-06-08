<?php
require_once "../config/db.php";
require_once "_layout.php";

$userId = (int)($_SESSION['user_id'] ?? 0);

$search = trim((string)($_GET['q'] ?? ''));
$semester = trim((string)($_GET['semester'] ?? ''));
$courseCode = trim((string)($_GET['course_code'] ?? ''));
$class = trim((string)($_GET['kelas'] ?? ''));
$academicYear = trim((string)($_GET['academic_year'] ?? ''));

$currentYear = (int)date('Y');
if ($academicYear === '') {
    $academicYear = $currentYear . '/' . ($currentYear + 1);
}

$courseOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT course_code, course_name, sks, semester FROM subjects ORDER BY course_name ASC");
    $courseOptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $courseOptions = [];
}

$classOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kelas FROM jadwal ORDER BY kelas ASC");
    $classOptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $classOptions = [];
}

$where = [];
$params = [];

$sql = "SELECT s.course_code, s.course_name, s.sks, s.semester";
$sql .= " FROM subjects s";

if ($search !== '') {
    $where[] = "(s.course_code LIKE :q OR s.course_name LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($semester !== '') {
    $where[] = "s.semester = :sem";
    $params[':sem'] = (int)$semester;
}

if ($courseCode !== '') {
    $where[] = "s.course_code = :code";
    $params[':code'] = $courseCode;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY s.semester ASC, s.course_name ASC";

$courses = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$statsByCourse = [];
$studentsCountByCourse = [];
try {
    $stmt = $pdo->query("SELECT course_code, COUNT(*) AS c FROM enrollments GROUP BY course_code");
    foreach ($stmt->fetchAll() as $row) {
        $studentsCountByCourse[(string)$row['course_code']] = (int)$row['c'];
    }
} catch (PDOException $e) {
}

foreach ($courses as $c) {
    $cc = (string)($c['course_code'] ?? '');
    $statsByCourse[$cc] = [
        'students' => (string)($studentsCountByCourse[$cc] ?? 0),
        'sessions' => '—'
    ];
}

$attendanceTableExists = false;
try {
    $pdo->query("SELECT 1 FROM attendance_records LIMIT 1");
    $attendanceTableExists = true;
} catch (Throwable $e) {
    $attendanceTableExists = false;
}

if ($attendanceTableExists) {
    foreach ($courses as $c) {
        $cc = (string)($c['course_code'] ?? '');
        $present = 0;
        $excused = 0;
        $sick = 0;
        $absent = 0;
        try {
            $stmt = $pdo->prepare("SELECT ar.status, COUNT(*) AS c FROM attendance_records ar JOIN jadwal j ON j.id = ar.jadwal_id WHERE j.kode_mk = :kode GROUP BY ar.status");
            $stmt->execute([':kode' => $cc]);
            foreach ($stmt->fetchAll() as $r) {
                $st = (string)($r['status'] ?? '');
                $cnt = (int)($r['c'] ?? 0);
                if ($st === 'Present') $present = $cnt;
                elseif ($st === 'Excused') $excused = $cnt;
                elseif ($st === 'Sick') $sick = $cnt;
                elseif ($st === 'Absent') $absent = $cnt;
            }
            $total = $present + $excused + $sick + $absent;
            $perc = $total > 0 ? round((($present + $excused) / $total) * 100) . '%' : '0%';
            $statsByCourse[$cc]['attendance_percentage'] = (string)$perc;
        } catch (PDOException $e) {
            $statsByCourse[$cc]['attendance_percentage'] = '—';
        }
    }
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Courses</h1>
            <p>Manajemen mata kuliah, kelas, dan ringkasan statistik pengajaran.</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 1060px; margin: 0 auto;">
        <div class="card-top">
            <h3>Course List</h3>
            <a href="dashboard.php" class="action-link">Back</a>
        </div>

        <div class="agenda-table-wrapper" style="margin-top: 10px;">
            <div class="agenda-rows-stack" style="border-bottom: 1px solid var(--border);">
                <form method="get" style="display:grid; grid-template-columns: 1fr 140px 220px 140px 150px; gap:0;">
                    <div class="agenda-table-header" style="grid-template-columns: 1fr; border-bottom: none;">
                        <span class="col-head">SEARCH</span>
                    </div>
                    <div class="agenda-table-header" style="grid-template-columns: 1fr; border-bottom: none;">
                        <span class="col-head">SEMESTER</span>
                    </div>
                    <div class="agenda-table-header" style="grid-template-columns: 1fr; border-bottom: none;">
                        <span class="col-head">COURSE</span>
                    </div>
                    <div class="agenda-table-header" style="grid-template-columns: 1fr; border-bottom: none;">
                        <span class="col-head">KELAS</span>
                    </div>
                    <div class="agenda-table-header" style="grid-template-columns: 1fr; border-bottom: none;">
                        <span class="col-head">TAHUN AJAR</span>
                    </div>

                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kursus" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:600;" />
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <select name="semester" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:700;">
                            <option value="">All</option>
                            <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
                            <option value="3" <?= $semester === '3' ? 'selected' : '' ?>>Semester 3</option>
                            <option value="4" <?= $semester === '4' ? 'selected' : '' ?>>Semester 4</option>
                            <option value="5" <?= $semester === '5' ? 'selected' : '' ?>>Semester 5</option>
                            <option value="6" <?= $semester === '6' ? 'selected' : '' ?>>Semester 6</option>
                        </select>
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <select name="course_code" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:700;">
                            <option value="">All Courses</option>
                            <?php foreach ($courseOptions as $co): ?>
                                <option value="<?= htmlspecialchars((string)$co['course_code']) ?>" <?= $courseCode === (string)$co['course_code'] ? 'selected' : '' ?>><?= htmlspecialchars($co['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <select name="kelas" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:700;">
                            <option value="">All Classes</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars((string)$cl['kelas']) ?>" <?= $class === (string)$cl['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:none; border-left:none; border-top:none;">
                        <input type="text" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:600;" />
                    </div>

                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:none; border-left:none; border-top:none; grid-column: 1 / -1; display:flex; align-items:center; justify-content:flex-end; padding:10px 12px;">
                        <button type="submit" class="trigger-btn btn-blue" style="border:none; padding:10px 14px;">Apply</button>
                    </div>
                </form>
            </div>

            <div class="agenda-table-header" style="grid-template-columns: 1fr 1fr 90px 110px 110px 120px;">
                <span class="col-head">COURSE</span>
                <span class="col-head">NAME</span>
                <span class="col-head">SKS</span>
                <span class="col-head">SEMESTER</span>
                <span class="col-head">CLASS</span>
                <span class="col-head">STUDENTS</span>
            </div>

            <div class="agenda-rows-stack">
                <?php if (empty($courses)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada mata kuliah.</div>
                <?php else: ?>
                    <?php foreach ($courses as $c): ?>
                        <?php
                            $cc = (string)($c['course_code'] ?? '');
                            $cn = (string)($c['course_name'] ?? '');
                            $sks = (string)($c['sks'] ?? '');
                            $sem = (string)($c['semester'] ?? '');

                            $firstClass = '';
                            $studentCount = (string)($statsByCourse[$cc]['students'] ?? '0');

                            try {
                                if ($class !== '') {
                                    $stmt = $pdo->prepare("SELECT DISTINCT kelas FROM jadwal WHERE kode_mk = :kode AND kelas = :kelas ORDER BY kelas ASC LIMIT 1");
                                    $stmt->execute([':kode' => $cc, ':kelas' => $class]);
                                } else {
                                    $stmt = $pdo->prepare("SELECT DISTINCT kelas FROM jadwal WHERE kode_mk = :kode ORDER BY kelas ASC LIMIT 1");
                                    $stmt->execute([':kode' => $cc]);
                                }
                                $clRow = $stmt->fetch();
                                if ($clRow && isset($clRow['kelas'])) $firstClass = (string)$clRow['kelas'];
                            } catch (PDOException $e) {
                                $firstClass = '';
                            }
                        ?>
                        <div class="agenda-table-row" style="grid-template-columns: 1fr 1fr 90px 110px 110px 120px;">
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline"><?= htmlspecialchars($cc) ?></span>
                                <span class="item-sub-clock">TA <?= htmlspecialchars($academicYear) ?></span>
                            </div>
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline"><?= htmlspecialchars($cn) ?></span>
                                <span class="item-sub-clock">Attendance: <?= htmlspecialchars((string)($statsByCourse[$cc]['attendance_percentage'] ?? '—')) ?></span>
                            </div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($sks) ?></div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($sem) ?></div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($firstClass !== '' ? $firstClass : '—') ?></div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($studentCount) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 0 16px 16px; display:flex; justify-content:flex-end;">
            <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none;">Kembali</a>
        </div>
    </div>
</main>

</div>
</body>
</html>


