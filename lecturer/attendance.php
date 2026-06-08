<?php
require_once "../config/db.php";
require_once "_layout.php";

$userId = (int) ($_SESSION["user_id"] ?? 0);

$success = "";
$error = "";

// Get Lecturer NIP (from theirs)
$nip = "";
try {
    $stmt = $pdo->prepare(
        "SELECT nip FROM lecturers WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $userId]);
    $lec = $stmt->fetch();
    if ($lec) {
        $nip = $lec["nip"];
    }
} catch (PDOException $e) {
}

// --- CODE GENERATION ACTIONS (from theirs) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"] ?? "";
    if ($action === "generate") {
        $course_code = $_POST["course_code"] ?? "";
        if ($course_code && $nip) {
            $kode_presensi = strtoupper(
                substr(md5(uniqid((string) mt_rand(), true)), 0, 6),
            );
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO presensi_sessions (nip, course_code, tanggal, kode_presensi, status) VALUES (?, ?, CURDATE(), ?, 'open')",
                );
                $stmt->execute([$nip, $course_code, $kode_presensi]);
                $success =
                    "Kode presensi berhasil digenerate: " . $kode_presensi;
            } catch (PDOException $e) {
                $error = "Gagal mengenerate kode presensi.";
            }
        }
    } elseif ($action === "close") {
        $session_id = $_POST["session_id"] ?? "";
        if ($session_id) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE presensi_sessions SET status = 'closed' WHERE id = ? AND nip = ?",
                );
                $stmt->execute([$session_id, $nip]);
                $success = "Sesi presensi ditutup.";
            } catch (PDOException $e) {
                $error = "Gagal menutup sesi presensi.";
            }
        }
    }
}

// --- MANUAL ATTENDANCE LOGIC (from HEAD) ---
$kodeMk = trim((string) ($_GET["kode_mk"] ?? ""));
$kelas = trim((string) ($_GET["kelas"] ?? ""));
$tanggal = trim((string) ($_GET["tanggal"] ?? ""));
$semester = trim((string) ($_GET["semester"] ?? ""));
$pertemuan = trim((string) ($_GET["pertemuan"] ?? ""));

$courseFilter = $kodeMk;
$classFilter = $kelas;
$dateFilter = $tanggal;

$jadwalList = [];
try {
    $where = [];
    $params = [];

    if ($courseFilter !== "") {
        $where[] = "kode_mk = :kode_mk";
        $params[":kode_mk"] = $courseFilter;
    }
    if ($classFilter !== "") {
        $where[] = "kelas = :kelas";
        $params[":kelas"] = $classFilter;
    }
    if ($dateFilter !== "") {
        $where[] = "tanggal = :tanggal";
        $params[":tanggal"] = $dateFilter;
    }
    if ($semester !== "") {
        $where[] = "kelas LIKE :sem";
        $params[":sem"] = "%" . $semester . "%";
    }

    $sql =
        "SELECT id, kode_mk, nama_mata_kuliah, kelas, hari, tanggal, jam_mulai, jam_selesai, ruangan, dosen_pengampu FROM jadwal";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY tanggal ASC, jam_mulai ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jadwalList = $stmt->fetchAll();
} catch (PDOException $e) {
    $jadwalList = [];
}

$selectedSession = null;
$sessionId = (int) ($_GET["jadwal_id"] ?? 0);
if ($sessionId > 0) {
    foreach ($jadwalList as $j) {
        if ((int) ($j["id"] ?? 0) === $sessionId) {
            $selectedSession = $j;
            break;
        }
    }
}
if ($selectedSession === null && !empty($jadwalList)) {
    $selectedSession = $jadwalList[0];
    $sessionId = (int) ($selectedSession["id"] ?? 0);
}

$courseOptions = [];
try {
    $stmt = $pdo->query(
        "SELECT DISTINCT kode_mk, nama_mata_kuliah FROM jadwal ORDER BY nama_mata_kuliah ASC",
    );
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

$attendanceTableExists = false;
try {
    $stmt = $pdo->query("SELECT 1 FROM attendance_records LIMIT 1");
    $attendanceTableExists = true;
} catch (Throwable $e) {
    $attendanceTableExists = false;
}

$stats = [
    "total_students" => "0",
    "present" => "0",
    "excused" => "0",
    "sick" => "0",
    "absent" => "0",
    "percentage" => "0%",
];

$studentRows = [];

try {
    if ($selectedSession) {
        $kode = (string) ($selectedSession["kode_mk"] ?? "");
        $stmt = $pdo->prepare(
            "SELECT nim, first_name, last_name, study_program FROM students WHERE nim IN (SELECT nim FROM enrollments WHERE course_code = :kode LIMIT 2000) LIMIT 2000",
        );
        $stmt->execute([":kode" => $kode]);
        $studentRows = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $studentRows = [];
}

$existing = [];
if ($attendanceTableExists && $sessionId > 0 && !empty($studentRows)) {
    try {
        $stmt = $pdo->prepare(
            "SELECT nim, status FROM attendance_records WHERE jadwal_id = :jadwal_id",
        );
        $stmt->execute([":jadwal_id" => $sessionId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $existing[(string) $r["nim"]] = (string) $r["status"];
        }
    } catch (PDOException $e) {
        $existing = [];
    }
}

// Manual POST Handling
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !isset($_POST["action"]) &&
    $attendanceTableExists &&
    $sessionId > 0
) {
    $saved = 0;
    try {
        $pdo->beginTransaction();
        foreach ($studentRows as $stu) {
            $nim = (string) ($stu["nim"] ?? "");
            if ($nim === "") {
                continue;
            }
            $key = "status_" . $nim;
            $status = trim((string) ($_POST[$key] ?? ""));
            if ($status === "") {
                $status = "Absent";
            }

            $stmt = $pdo->prepare(
                "SELECT id FROM attendance_records WHERE jadwal_id = :jadwal_id AND nim = :nim LIMIT 1",
            );
            $stmt->execute([":jadwal_id" => $sessionId, ":nim" => $nim]);
            $row = $stmt->fetch();

            if ($row) {
                $up = $pdo->prepare(
                    "UPDATE attendance_records SET status = :status WHERE jadwal_id = :jadwal_id AND nim = :nim",
                );
                $up->execute([
                    ":status" => $status,
                    ":jadwal_id" => $sessionId,
                    ":nim" => $nim,
                ]);
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO attendance_records (jadwal_id, nim, status) VALUES (:jadwal_id, :nim, :status)",
                );
                $ins->execute([
                    ":jadwal_id" => $sessionId,
                    ":nim" => $nim,
                    ":status" => $status,
                ]);
            }
            $saved++;
        }
        $pdo->commit();
        $success = "Attendance saved.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to save attendance.";
    }
}

if ($attendanceTableExists && $sessionId > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) AS c FROM attendance_records WHERE jadwal_id = :jadwal_id GROUP BY status",
        );
        $stmt->execute([":jadwal_id" => $sessionId]);
        $rows = $stmt->fetchAll();

        $present = 0;
        $excused = 0;
        $sick = 0;
        $absent = 0;
        foreach ($rows as $r) {
            $st = (string) ($r["status"] ?? "");
            $c = (int) ($r["c"] ?? 0);
            if ($st === "Present") {
                $present = $c;
            } elseif ($st === "Excused") {
                $excused = $c;
            } elseif ($st === "Sick") {
                $sick = $c;
            } else {
                $absent = $c;
            }
        }

        $total = $present + $excused + $sick + $absent;
        $perc =
            $total > 0
                ? round((($present + $excused) / $total) * 100) . "%"
                : "0%";

        $stats = [
            "total_students" => (string) $total,
            "present" => (string) $present,
            "excused" => (string) $excused,
            "sick" => (string) $sick,
            "absent" => (string) $absent,
            "percentage" => (string) $perc,
        ];
    } catch (PDOException $e) {
        $stats = [
            "total_students" => "0",
            "present" => "0",
            "excused" => "0",
            "sick" => "0",
            "absent" => "0",
            "percentage" => "0%",
        ];
    }
}

$meetingLabel = $selectedSession
    ? (string) ($selectedSession["hari"] ?? "") .
        " " .
        ($selectedSession["tanggal"] ?? "")
    : "";
$sessionLecturer = $selectedSession
    ? (string) ($selectedSession["dosen_pengampu"] ?? "")
    : "";

// Data for Code Generation (theirs)
$courses = [];
if ($nip) {
    try {
        $stmt = $pdo->prepare(
            "SELECT c.course_code, s.course_name FROM lecturer_courses c JOIN subjects s ON s.course_code = c.course_code WHERE c.nip = ?",
        );
        $stmt->execute([$nip]);
        $courses = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

$sessions = [];
if ($nip) {
    try {
        $stmt = $pdo->prepare(
            "SELECT p.*, s.course_name, (SELECT COUNT(*) FROM student_presensi sp WHERE sp.session_id = p.id) as total_hadir FROM presensi_sessions p JOIN subjects s ON s.course_code = p.course_code WHERE p.nip = ? ORDER BY p.id DESC",
        );
        $stmt->execute([$nip]);
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Attendance Management</h1>
            <p>Kelola kehadiran manual atau generate kode presensi otomatis.</p>
        </div>
    </section>

    <div class="split-grid" style="grid-template-columns: 0.9fr 1.1fr; gap: 24px;">
        <div class="content-card">
            <div class="card-top">
                <h3>Class Selection & Active Codes</h3>
                <a href="dashboard.php" class="action-link">Back</a>
            </div>

            <!-- TAB OR SECTION FOR CODE GENERATION (from theirs) -->
            <div style="padding: 16px; border-bottom: 1px solid var(--border);">
                <h4>Generate Kode Presensi</h4>
                <form method="POST" style="margin-top: 10px; display: grid; gap: 10px;">
                    <input type="hidden" name="action" value="generate">
                    <select required name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border);">
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= htmlspecialchars(
                                $c["course_code"],
                            ) ?>"><?= htmlspecialchars(
    $c["course_code"] . " - " . $c["course_name"],
) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="trigger-btn btn-blue" type="submit" style="border:none;">Generate Kode</button>
                </form>
            </div>

            <div style="padding: 16px;">
                <h4>Filter Sesi Manual</h4>
                <form method="get" style="display:grid; gap: 12px; margin-top: 10px;">
                    <label style="display:block;">
                        <span>Course</span>
                        <select name="kode_mk" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Courses</option>
                            <?php foreach ($courseOptions as $c): ?>
                                <option value="<?= htmlspecialchars(
                                    $c["kode_mk"],
                                ) ?>" <?= (string) $kodeMk ===
(string) $c["kode_mk"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($c["nama_mata_kuliah"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display:block;">
                        <span>Class</span>
                        <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Classes</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars(
                                    $cl["kelas"],
                                ) ?>" <?= (string) $kelas ===
(string) $cl["kelas"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($cl["kelas"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Filter Sesi</button>
                </form>
            </div>

            <div style="margin-top:14px; padding: 0 16px 16px;">
                <div class="card-top" style="margin-bottom: 10px;">
                    <h3>Sesi Terjadwal</h3>
                </div>

                <div class="agenda-rows-stack">
                    <?php if (empty($jadwalList)): ?>
                        <div class="empty-fallback-text border-box-pad">Tidak ada sesi.</div>
                    <?php else: ?>
                        <?php foreach ($jadwalList as $s): ?>
                            <?php $sid = (int) ($s["id"] ?? 0); ?>
                            <a href="attendance.php?jadwal_id=<?= htmlspecialchars(
                                (string) $sid,
                            ) ?>&kode_mk=<?= htmlspecialchars(
    (string) ($s["kode_mk"] ?? ""),
) ?>&kelas=<?= htmlspecialchars(
    (string) ($s["kelas"] ?? ""),
) ?>&tanggal=<?= htmlspecialchars(
    (string) ($s["tanggal"] ?? ""),
) ?>" style="text-decoration:none; display:block;">
                                <div class="task-node" style="border-bottom: 1px solid var(--border); background: <?= $sessionId ===
                                $sid
                                    ? "var(--bg-light)"
                                    : "transparent" ?>;">
                                    <div class="task-node-details" style="display:flex; flex-direction:column; gap:4px;">
                                        <span class="task-node-title"><?= htmlspecialchars(
                                            $s["nama_mata_kuliah"] ?? "",
                                        ) ?></span>
                                        <span class="task-node-time"><?= htmlspecialchars(
                                            (string) ($s["hari"] ?? ""),
                                        ) ?> · <?= htmlspecialchars(
     (string) ($s["tanggal"] ?? ""),
 ) ?> · <?= htmlspecialchars((string) ($s["jam_mulai"] ?? "")) ?></span>
                                    </div>
                                    <svg class="chevron-right-item" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-top">
                <h3>Attendance View</h3>
                <a href="schedule.php" class="action-link">Schedule</a>
            </div>

            <?php if ($success !== ""): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px; background:#dcfce7; border-radius: var(--radius); text-align:left; padding:12px; font-style:normal; font-weight: bold;"><?= htmlspecialchars(
                    $success,
                ) ?></div>
            <?php endif; ?>
            <?php if ($error !== ""): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px; background:#ffffff; border-radius: var(--radius); text-align:left; padding:12px; font-style:normal;"><?= htmlspecialchars(
                    $error,
                ) ?></div>
            <?php endif; ?>

            <!-- ACTIVE CODE SESSIONS (from theirs) -->
            <?php if (!empty($sessions)): ?>
                <div style="padding: 16px; background: #f9fafb; border-bottom: 1px solid var(--border);">
                    <h4 style="margin-bottom: 10px;">Kode Presensi Aktif / Riwayat</h4>
                    <div class="tasks-vertical-stack">
                        <?php foreach ($sessions as $s): ?>
                            <div class="task-node <?= $s["status"] === "closed"
                                ? "done"
                                : "" ?>" style="padding: 8px; margin-bottom: 5px;">
                                <div class="task-node-details" style="flex: 1;">
                                    <span style="font-weight: bold;"><?= htmlspecialchars(
                                        $s["course_name"],
                                    ) ?></span>
                                    <span style="font-size: 0.8rem;"><?= htmlspecialchars(
                                        $s["tanggal"],
                                    ) ?> | Code: <strong style="font-family: monospace;"><?= htmlspecialchars(
     $s["kode_presensi"],
 ) ?></strong></span>
                                </div>
                                <?php if ($s["status"] === "open"): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="close">
                                        <input type="hidden" name="session_id" value="<?= $s[
                                            "id"
                                        ] ?>">
                                        <button type="submit" class="trigger-btn btn-red" style="padding: 4px 8px; font-size: 0.7rem;">Tutup</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$selectedSession): ?>
                <div class="empty-fallback-text border-box-pad">Pilih sesi terjadwal untuk kelola kehadiran mahasiswa secara manual.</div>
            <?php else: ?>
                <div class="metrics-row" style="margin: 16px 16px 0; flex-wrap: wrap; gap: 8px;">
                    <div class="metric-card" style="padding: 10px;"><span class="metric-label" style="font-size: 0.7rem;">Total</span><span class="metric-value" style="font-size: 1rem;"><?= htmlspecialchars(
                        $stats["total_students"],
                    ) ?></span></div>
                    <div class="metric-card" style="padding: 10px;"><span class="metric-label" style="font-size: 0.7rem;">Present</span><span class="metric-value" style="font-size: 1rem;"><?= htmlspecialchars(
                        $stats["present"],
                    ) ?></span></div>
                    <div class="metric-card" style="padding: 10px;"><span class="metric-label" style="font-size: 0.7rem;">%</span><span class="metric-value" style="font-size: 1rem;"><?= htmlspecialchars(
                        $stats["percentage"],
                    ) ?></span></div>
                </div>

                <div style="padding: 16px;">
                    <h4 style="margin-bottom: 4px;"><?= htmlspecialchars(
                        $selectedSession["nama_mata_kuliah"] ?? "",
                    ) ?></h4>
                    <p style="font-size: 0.85rem; color: var(--charcoal); opacity: 0.8;"><?= htmlspecialchars(
                        $meetingLabel,
                    ) ?> | Lecturer: <?= htmlspecialchars(
     $sessionLecturer,
 ) ?></p>
                </div>

                <form method="POST" action="attendance.php?jadwal_id=<?= htmlspecialchars(
                    (string) $sessionId,
                ) ?>&kode_mk=<?= htmlspecialchars(
    (string) ($selectedSession["kode_mk"] ?? ""),
) ?>&kelas=<?= htmlspecialchars(
    (string) ($selectedSession["kelas"] ?? ""),
) ?>&tanggal=<?= htmlspecialchars(
    (string) ($selectedSession["tanggal"] ?? ""),
) ?>" style="padding:0;">
                    <div class="agenda-table-wrapper">
                        <div class="agenda-table-header" style="grid-template-columns: 1.1fr 0.6fr 0.9fr;">
                            <span class="col-head">Student</span>
                            <span class="col-head">NIM</span>
                            <span class="col-head">Status</span>
                        </div>
                        <div class="agenda-rows-stack">
                            <?php if (empty($studentRows)): ?>
                                <div class="empty-fallback-text border-box-pad">Tidak ada mahasiswa untuk sesi ini.</div>
                            <?php else: ?>
                                <?php foreach ($studentRows as $stu): ?>
                                    <?php $nim =
                                        (string) ($stu["nim"] ?? ""); ?>
                                    <?php $labelName = trim(
                                        ($stu["first_name"] ?? "") .
                                            " " .
                                            ($stu["last_name"] ?? ""),
                                    ); ?>
                                    <?php $curr = $existing[$nim] ?? ""; ?>
                                    <div class="agenda-table-row" style="grid-template-columns: 1.1fr 0.6fr 0.9fr;">
                                        <div class="col-cell">
                                            <span class="item-main-headline" style="font-size: 0.85rem;"><?= htmlspecialchars(
                                                $labelName !== ""
                                                    ? $labelName
                                                    : "Student",
                                            ) ?></span>
                                        </div>
                                        <div class="col-cell">
                                            <span class="item-main-headline" style="font-size: 0.85rem;"><?= htmlspecialchars(
                                                $nim,
                                            ) ?></span>
                                        </div>
                                        <div class="col-cell">
                                            <select name="status_<?= htmlspecialchars(
                                                $nim,
                                            ) ?>" style="width:100%; padding:4px; border-radius:6px; border:1px solid var(--border); font-size: 0.8rem;">
                                                <?php $options = [
                                                    "Present",
                                                    "Excused",
                                                    "Sick",
                                                    "Absent",
                                                ]; ?>
                                                <?php foreach (
                                                    $options
                                                    as $opt
                                                ): ?>
                                                    <option value="<?= htmlspecialchars(
                                                        $opt,
                                                    ) ?>" <?= $curr === $opt
    ? "selected"
    : "" ?>><?= htmlspecialchars($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; padding: 16px; align-items:center; flex-wrap:wrap;">
                        <button type="submit" class="trigger-btn btn-blue" style="border:none;">Save Attendance</button>
                        <a href="attendance.php" class="trigger-btn btn-red" style="text-decoration:none;">Reset</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

</div>
</body>
</html>
