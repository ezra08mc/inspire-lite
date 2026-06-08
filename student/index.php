<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_name = null;
$nim = $_SESSION["username"] ?? "";
$study_program = null;
$cohort = null;
$academic_year = null;
$semester_label = null;
$data_errors = [];

try {
    $stmt = $pdo->prepare(
        "SELECT nim, first_name, last_name, study_program, cohort FROM students WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) {
        $first_name = trim($student_data["first_name"] ?? "");
        $last_name = trim($student_data["last_name"] ?? "");
        $student_name = trim($first_name . " " . $last_name);
        $nim = $student_data["nim"] ?? $nim;
        $study_program = $student_data["study_program"] ?? null;
        $cohort = $student_data["cohort"] ?? null;
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat profil mahasiswa.";
    error_log($e->getMessage());
}

if (!empty($nim)) {
    try {
        $stmt = $pdo->prepare(
            "SELECT academic_year, semester_type FROM enrollments WHERE nim = :nim ORDER BY id DESC LIMIT 1",
        );
        $stmt->execute([":nim" => $nim]);
        $enrollment = $stmt->fetch();
        if ($enrollment) {
            $academic_year = $enrollment["academic_year"] ?? null;
            if (!empty($enrollment["semester_type"])) {
                $semester_label = "Semester " . $enrollment["semester_type"];
            }
        }
    } catch (PDOException $e) {
        $data_errors[] = "Gagal memuat data perkuliahan.";
        error_log($e->getMessage());
    }
}

if ($academic_year === null && !empty($cohort)) {
    $academic_year = $cohort . "/" . ($cohort + 1);
}

$stats = [
    "sks_ditempuh" => null,
    "ipk_kumulatif" => null,
    "ip_semester" => null,
    "sks_semester" => null,
];
try {
    $stmt = $pdo->prepare(
        "SELECT sks_ditempuh, ipk_kumulatif, ip_semester, sks_semester FROM student_academic_stats WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $db_stats = $stmt->fetch();
    if ($db_stats) {
        $stats = $db_stats;
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat statistik akademik.";
    error_log($e->getMessage());
}

$announcements = [];
try {
    $stmt = $pdo->query(
        "SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC",
    );
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$tasks = [];
try {
    $stmt = $pdo->prepare(
        "SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks WHERE user_id = :user_id ORDER BY id ASC",
    );
    $stmt->execute([":user_id" => $user_id]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat daftar tugas.";
    error_log($e->getMessage());
}

$agenda = [];
try {
    $stmt = $pdo->prepare(
        "SELECT nama_mata_kuliah, jam_mulai, jam_selesai, ruangan, tanggal, hari FROM jadwal WHERE tanggal >= CURDATE() ORDER BY tanggal ASC, jam_mulai ASC",
    );
    $stmt->execute();
    $db_agenda = $stmt->fetchAll();

    $today = new DateTime()->format("Y-m-d");

    foreach ($db_agenda as $item) {
        $date_badge = new DateTime($item["tanggal"])->format("d M");
        if ($item["tanggal"] === $today) {
            $date_badge = "Hari Ini";
        }

        $agenda[] = [
            "date_badge" => $date_badge,
            "title" => $item["nama_mata_kuliah"],
            "time_range" =>
                new DateTime($item["jam_mulai"])->format("H:i") .
                " – " .
                new DateTime($item["jam_selesai"])->format("H:i"),
            "location" => $item["ruangan"],
            "dot_color" => "blue",
        ];
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat agenda dari jadwal.";
    error_log($e->getMessage());
}

$notifications = [];
try {
    $stmt = $pdo->query(
        "SELECT title, content, created_at FROM student_notifications ORDER BY created_at DESC LIMIT 5",
    );
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat notifikasi.";
    error_log($e->getMessage());
}

$display_name = $student_name ?: $nim;
$first_name = "";
$initials = "";
if (!empty($student_name)) {
    $name_parts = preg_split("/\s+/", trim($student_name));
    $first_name = $name_parts[0] ?? "";
    $initials = strtoupper(
        substr($name_parts[0] ?? "", 0, 1) . substr($name_parts[1] ?? "", 0, 1),
    );
} elseif (!empty($nim)) {
    $initials = strtoupper(substr($nim, 0, 2));
}

$hero_meta_parts = [];
if (!empty($study_program)) {
    $hero_meta_parts[] = $study_program;
}
if (!empty($semester_label)) {
    $hero_meta_parts[] = $semester_label;
}
if (!empty($academic_year)) {
    $hero_meta_parts[] = "TA " . $academic_year;
}
$hero_meta = implode(" · ", $hero_meta_parts);

$current_page = "dashboard";
$base_path = "../";

$current_month_label = null;
$current_day = null;
$calendar_weeks = [];
$calendar_marks = [];
try {
    $current_date = new DateTime();
    $month_number = (int) $current_date->format("n");
    $year_number = (int) $current_date->format("Y");
    $current_day = (int) $current_date->format("j");
    $month_names = [
        1 => "Januari",
        2 => "Februari",
        3 => "Maret",
        4 => "April",
        5 => "Mei",
        6 => "Juni",
        7 => "Juli",
        8 => "Agustus",
        9 => "September",
        10 => "Oktober",
        11 => "November",
        12 => "Desember",
    ];
    $current_month_label = $month_names[$month_number] . " " . $year_number;

    $first_day = new DateTime($current_date->format("Y-m-01"));
    $days_in_month = (int) $current_date->format("t");
    $start_weekday = (int) $first_day->format("w");

    $total_cells = $start_weekday + $days_in_month;
    $weeks = (int) ceil($total_cells / 7);

    for ($week = 0; $week < $weeks; $week++) {
        $week_days = [];
        for ($day_index = 0; $day_index < 7; $day_index++) {
            $cell_index = $week * 7 + $day_index;
            $day_number = $cell_index - $start_weekday + 1;
            if ($day_number < 1 || $day_number > $days_in_month) {
                $week_days[] = null;
            } else {
                $week_days[] = $day_number;
            }
        }
        $calendar_weeks[] = $week_days;
    }

    $stmt_jadwal = $pdo->prepare(
        "SELECT tanggal FROM jadwal WHERE MONTH(tanggal) = :month AND YEAR(tanggal) = :year",
    );
    $stmt_jadwal->execute([
        ":month" => $month_number,
        ":year" => $year_number,
    ]);
    $jadwal_in_month = $stmt_jadwal->fetchAll();

    foreach ($jadwal_in_month as $item) {
        $day_number = (int) new DateTime($item["tanggal"])->format("j");
        if ($day_number >= 1 && $day_number <= $days_in_month) {
            $calendar_marks[$day_number] = "blue";
        }
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat kalender.";
    error_log($e->getMessage());
}

$unread_count = 0;
?>
<?php
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
            <?php if (!empty($data_errors)): ?>
                <div class="empty-fallback-text border-box-pad"><?= htmlspecialchars(
                    $data_errors[0],
                ) ?></div>
            <?php endif; ?>
            <section class="hero-banner">
                <div class="hero-title">
                    <?php if (!empty($first_name)): ?>
                        <h1>Halo, <?= htmlspecialchars($first_name) ?>! 👋</h1>
                    <?php else: ?>
                        <h1>Halo! 👋</h1>
                    <?php endif; ?>
                    <?php if (!empty($hero_meta)): ?>
                        <p><?= htmlspecialchars($hero_meta) ?></p>
                    <?php endif; ?>
                </div>
                <div class="metrics-row">
                    <div class="metric-card">
                        <span class="metric-label">SKS DITEMPUH</span>
                        <span class="metric-value"><?= htmlspecialchars(
                            $stats["sks_ditempuh"] ?? "0",
                        ) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">IPK KUMULATIF</span>
                        <span class="metric-value"><?= htmlspecialchars(
                            $stats["ipk_kumulatif"] ?? "0.00",
                        ) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">IP SEMESTER</span>
                        <span class="metric-value"><?= htmlspecialchars(
                            $stats["ip_semester"] ?? "0.00",
                        ) ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">SKS SEMESTER</span>
                        <span class="metric-value"><?= htmlspecialchars(
                            $stats["sks_semester"] ?? "0",
                        ) ?></span>
                    </div>
                </div>
            </section>

            <div class="split-grid">
                <div class="content-card">
                    <div class="card-top">
                        <h3>Aksi Cepat</h3>
                    </div>
                    <div class="quick-actions-box flex-stretch-actions">
                        <a href="perkuliahan/presensi.php" class="action-node">
                            <div class="action-node-icon absensi">
                                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <span>Absensi</span>
                        </a>
                        <a href="perkuliahan/kartu-mahasiswa.php" class="action-node">
                            <div class="action-node-icon k-studi">
                                <svg viewBox="0 0 24 24"><path d="M21 4H3c-1.11 0-2 .89-2 2v12c0 1.1.89 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1.5 3c.83 0 1.5.67 1.5 1.5S20.33 10 19.5 10 18 9.33 18 8.5 18.67 7 19.5 7zM6 15H4v-2h2v2zm0-4H4V9h2v2zm14 4H8v-2h12v2zm0-4H8V9h12v2z"/></svg>
                            </div>
                            <span>Kartu Studi</span>
                        </a>
                        <a href="perkuliahan/transkrip.php" class="action-node">
                            <div class="action-node-icon transkrip">
                                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                            </div>
                            <span>Transkrip</span>
                        </a>
                        <a href="perkuliahan/jadwal.php" class="action-node">
                            <div class="action-node-icon jadwal">
                                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                            </div>
                            <span>Jadwal</span>
                        </a>
                        <a href="perkuliahan/krs.php" class="action-node">
                            <div class="action-node-icon krs">
                                <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <span>KRS</span>
                        </a>
                        <a href="perkuliahan/bimbingan.php" class="action-node">
                            <div class="action-node-icon bimbingan">
                                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                            </div>
                            <span>Bimbingan Akademik</span>
                        </a>
                    </div>
                    <div class="card-action-triggers">
                        <a href="https://sia.example.ac.id" target="_blank" class="trigger-btn btn-blue">Portal Utama</a>
                        <a href="https://elearning.example.ac.id" target="_blank" class="trigger-btn btn-red">E-Learning</a>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Pengumuman</h3>
                        <a href="announcements.php" class="action-link">Lihat semua →</a>
                    </div>
                    <div class="announcements-feed">
                        <?php if (empty($announcements)): ?>
                            <div class="empty-fallback-text">Tidak ada pengumuman akademik terbaru.</div>
                        <?php else: ?>
                            <?php foreach ($announcements as $annc): ?>
                                <div class="annc-node">
                                    <div class="annc-top-line">
                                        <div class="annc-icon-frame <?= htmlspecialchars(
                                            $annc["badge_class"],
                                        ) ?>">
                                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                        </div>
                                        <div class="annc-title-area">
                                            <div class="annc-meta">
                                                <?php if (
                                                    $annc["type"] === "PENTING"
                                                ): ?>
                                                    <span class="label-badge red">PENTING</span>
                                                <?php endif; ?>
                                                <span class="annc-date"><?= htmlspecialchars(
                                                    $annc["date_text"],
                                                ) ?></span>
                                            </div>
                                            <h4><?= htmlspecialchars(
                                                $annc["title"],
                                            ) ?></h4>
                                        </div>
                                    </div>
                                    <p class="annc-body-text"><?= htmlspecialchars(
                                        $annc["content"],
                                    ) ?></p>
                                    <span class="annc-dept"><?= htmlspecialchars(
                                        $annc["author"],
                                    ) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="split-grid">
                <div class="content-card">
                    <div class="card-top">
                        <h3>Tugas Aktif</h3>
                        <a href="perkuliahan/tugas.php" class="action-link">Lihat semua →</a>
                    </div>
                    <div class="tasks-vertical-stack">
                        <?php if (empty($tasks)): ?>
                            <div class="empty-fallback-text border-box-pad">Seluruh tugas perkuliahan telah diselesaikan.</div>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <div class="task-node <?= $task["is_completed"]
                                    ? "done"
                                    : "" ?>">
                                    <div class="task-checkbox-frame">
                                        <?php if ($task["is_completed"]): ?>
                                            <svg viewBox="0 0 24 24" class="check-svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-node-details">
                                        <span class="task-node-title"><?= htmlspecialchars(
                                            $task["name"],
                                        ) ?></span>
                                        <span class="task-node-time <?= $task[
                                            "is_alert"
                                        ]
                                            ? "alert"
                                            : "" ?>"><?= htmlspecialchars(
    $task["deadline_text"],
) ?></span>
                                    </div>
                                    <svg class="chevron-right-item" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Agenda Mendatang</h3>
                        <select class="agenda-month-dropdown" <?= $current_month_label
                            ? ""
                            : "disabled" ?>>
                            <?php if ($current_month_label): ?>
                                <option><?= htmlspecialchars(
                                    $current_month_label,
                                ) ?></option>
                            <?php else: ?>
                                <option>Tidak tersedia</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if (!empty($calendar_weeks)): ?>
                        <div class="calendar-component">
                            <div class="calendar-days-grid">
                                <span class="grid-header-cell">Min</span>
                                <span class="grid-header-cell">Sen</span>
                                <span class="grid-header-cell">Sel</span>
                                <span class="grid-header-cell">Rab</span>
                                <span class="grid-header-cell">Kam</span>
                                <span class="grid-header-cell">Jum</span>
                                <span class="grid-header-cell">Sab</span>
                                <?php foreach ($calendar_weeks as $week): ?>
                                    <?php foreach ($week as $day): ?>
                                        <?php if ($day === null): ?>
                                            <span></span>
                                        <?php else: ?>
                                            <?php
                                            $day_classes = ["grid-day-cell"];
                                            if ($current_day === $day) {
                                                $day_classes[] = "active";
                                            }
                                            if (!empty($calendar_marks[$day])) {
                                                $day_classes[] = "marked";
                                                $day_classes[] =
                                                    $calendar_marks[$day];
                                            }
                                            ?>
                                            <span class="<?= htmlspecialchars(
                                                implode(" ", $day_classes),
                                            ) ?>"><?= $day ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="legend-indicators-row">
                            <span class="legend-node"><span class="dot crimson"></span>Hari ini</span>
                            <span class="legend-node"><span class="dot coral"></span>Deadline</span>
                            <span class="legend-node"><span class="dot blue"></span>Kegiatan</span>
                        </div>
                    <?php else: ?>
                        <div class="empty-fallback-text border-box-pad">Kalender belum tersedia.</div>
                    <?php endif; ?>

                    <div class="agenda-table-wrapper">
                        <div class="agenda-table-header">
                            <span class="col-head">TANGGAL</span>
                            <span class="col-head">KEGIATAN</span>
                            <span class="col-head">LOKASI</span>
                        </div>
                        <div class="agenda-rows-stack">
                            <?php if (empty($agenda)): ?>
                                <div class="empty-fallback-text border-box-pad">Tidak ada agenda akademik perkuliahan terdekat.</div>
                            <?php else: ?>
                                <?php foreach ($agenda as $item): ?>
                                    <div class="agenda-table-row">
                                        <div class="col-cell cell-date">
                                            <span class="indicator-circle-bullet <?= htmlspecialchars(
                                                $item["dot_color"],
                                            ) ?>"></span>
                                            <span class="date-lbl-txt"><?= htmlspecialchars(
                                                $item["date_badge"],
                                            ) ?></span>
                                        </div>
                                        <div class="col-cell cell-mid-desc">
                                            <span class="item-main-headline"><?= htmlspecialchars(
                                                $item["title"],
                                            ) ?></span>
                                            <span class="item-sub-clock"><?= htmlspecialchars(
                                                $item["time_range"],
                                            ) ?></span>
                                        </div>
                                        <div class="col-cell cell-room-loc"><?= htmlspecialchars(
                                            $item["location"],
                                        ) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
<?php include $base_path . "includes/footer.php"; ?>
