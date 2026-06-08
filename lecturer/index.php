<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "lecturer") {
    header("Location: ../login.php");
    exit();
}

$userId = (int) ($_SESSION["user_id"] ?? 0);

$lecturer_name = "";
$nip = "";
$expertise = "";
$academic_year = "";

try {
    $stmt = $pdo->prepare(
        "SELECT nip, first_name, last_name, degree, expertise, birth_date FROM lecturers WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $userId]);
    $lecturer = $stmt->fetch();
    if ($lecturer) {
        $lecturer_name =
            (string) ($lecturer["first_name"] ?? "") .
            " " .
            (string) ($lecturer["last_name"] ?? "");
        $nip = (string) ($lecturer["nip"] ?? "");
        $expertise = (string) ($lecturer["expertise"] ?? "");
    }
} catch (PDOException $e) {
}

$cohort = (int) date("Y");
$academic_year = $cohort . "/" . ($cohort + 1);

$current_month = date("F Y");

$quick_tasks_count = 0;
$recent_activities = [];

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS c FROM lecturer_recent_activities WHERE user_id = :user_id",
    );
    $stmt->execute([":user_id" => $userId]);
    $row = $stmt->fetch();
    $quick_tasks_count = (int) ($row["c"] ?? 0);
} catch (PDOException $e) {
}

$agenda = [];
try {
    $stmt = $pdo->prepare(
        "SELECT tanggal, nama_mata_kuliah AS title, CONCAT(jam_mulai, '–', jam_selesai) AS time_range, ruangan AS location, CASE WHEN DATE(tanggal)=CURDATE() THEN 'red' WHEN DATE(tanggal) >= CURDATE() THEN 'blue' ELSE 'coral' END AS dot_color, CONCAT(DAY(tanggal),' ', MONTH(tanggal)) AS date_badge FROM jadwal WHERE user_id = :user_id OR 1=1 ORDER BY tanggal ASC LIMIT 7",
    );
    $stmt->execute([":user_id" => $userId]);
    $agenda = $stmt->fetchAll();
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query(
            "SELECT tanggal, nama_mata_kuliah AS title, CONCAT(jam_mulai, '–', jam_selesai) AS time_range, ruangan AS location, CASE WHEN DATE(tanggal)=CURDATE() THEN 'red' WHEN DATE(tanggal) >= CURDATE() THEN 'blue' ELSE 'coral' END AS dot_color, CONCAT(DAY(tanggal),' ', MONTH(tanggal)) AS date_badge FROM jadwal ORDER BY tanggal ASC LIMIT 7",
        );
        $agenda = $stmt->fetchAll();
    } catch (PDOException $e2) {
    }
}

$announcements = [];
try {
    $stmt = $pdo->query(
        "SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC",
    );
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
}

$notifications = [];
$unread_count = 0;
try {
    $stmt = $pdo->prepare(
        "SELECT id, title, content, category, sender, created_at FROM student_notifications ORDER BY id DESC LIMIT 5",
    );
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    $unread_count = 0;
} catch (PDOException $e) {
    $notifications = [];
    $unread_count = 0;
}

$lecturer_stats = [
    "total_courses_taught" => "—",
    "total_students" => "—",
    "attendance_sessions" => "—",
    "academic_advisees" => "—",
];
try {
    $stmt = $pdo->prepare(
        "SELECT total_courses_taught, total_students, attendance_sessions, academic_advisees FROM lecturer_stats WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $userId]);
    $s = $stmt->fetch();
    if ($s) {
        $lecturer_stats = [
            "total_courses_taught" =>
                (string) ($s["total_courses_taught"] ?? "—"),
            "total_students" => (string) ($s["total_students"] ?? "—"),
            "attendance_sessions" =>
                (string) ($s["attendance_sessions"] ?? "—"),
            "academic_advisees" => (string) ($s["academic_advisees"] ?? "—"),
        ];
    }
} catch (PDOException $e) {
}

$initials = "D";
if (!empty($lecturer_name)) {
    $name_array = preg_split("/\s+/", trim($lecturer_name));
    $initials = strtoupper(
        substr($name_array[0] ?? "", 0, 1) . substr($name_array[1] ?? "", 0, 1),
    );
    if ($initials === "") {
        $initials = "D";
    }
}

$base_path = "../";
$page_title = "Lecturer Dashboard - INSPIRE Lite";
$current_page = "dashboard";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
<main class="dashboard-viewport">
            <section class="hero-banner">
                <div class="hero-title">
                    <h1>Halo, <?= htmlspecialchars(
                        explode(" ", $lecturer_name ?: "Dosen")[0],
                    ) ?>! 👋</h1>
                    <p><?= htmlspecialchars(
                        $nip ?: "-",
                    ) ?> · <?= htmlspecialchars(
     $expertise ?: "Keahlian",
 ) ?> · TA <?= htmlspecialchars($academic_year) ?></p>
                </div>
            </section>

            <div class="split-grid flex-equal-align">
                <div class="content-card taller-box">
                    <div class="card-top">
                        <h3>Aksi Cepat</h3>
                        <a href="#" class="action-link">Edit →</a>
                    </div>
                    <div class="quick-actions-box flex-stretch-actions">
                        <a href="schedule.php" class="action-node">
                            <div class="action-node-icon jadwal">
                                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                            </div>
                            <span>Teaching Schedule</span>
                        </a>
                        <a href="attendance.php" class="action-node">
                            <div class="action-node-icon absensi">
                                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <span>Attendance Management</span>
                        </a>
                        <a href="grading.php" class="action-node">
                            <div class="action-node-icon transkrip">
                                <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <span>Grade Management</span>
                        </a>
                        <a href="advising.php" class="action-node">
                            <div class="action-node-icon bimbingan">
                                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                            </div>
                            <span>Academic Advising Students</span>
                        </a>
                    </div>
                    <div class="card-action-triggers">
                        <button class="trigger-btn btn-blue">Portal Utama</button>
                        <button class="trigger-btn btn-red">E-Learning</button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Announcements Board</h3>
                        <a href="#" class="action-link">Lihat semua →</a>
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

            <div class="split-grid height-fluid">
                <div class="content-card">
                    <div class="card-top">
                        <h3>Tugas Aktif</h3>
                        <a href="#" class="action-link">Lihat semua →</a>
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
                        <select class="agenda-month-dropdown">
                            <option><?= htmlspecialchars(
                                $current_month,
                            ) ?></option>
                        </select>
                    </div>
                    <div class="calendar-component">
                        <div class="calendar-days-grid">
                            <span class="grid-header-cell">Min</span><span class="grid-header-cell">Sen</span><span class="grid-header-cell">Sel</span><span class="grid-header-cell">Rab</span><span class="grid-header-cell">Kam</span><span class="grid-header-cell">Jum</span><span class="grid-header-cell">Sab</span>
                            <span></span><span></span><span></span><span></span><span></span><span class="grid-day-cell">1</span><span class="grid-day-cell">2</span>
                            <span class="grid-day-cell">3</span><span class="grid-day-cell">4</span><span class="grid-day-cell">5</span><span class="grid-day-cell">6</span><span class="grid-day-cell">7</span><span class="grid-day-cell">8</span><span class="grid-day-cell">9</span>
                            <span class="grid-day-cell">10</span><span class="grid-day-cell">11</span><span class="grid-day-cell">12</span><span class="grid-day-cell">13</span><span class="grid-day-cell">14</span><span class="grid-day-cell">15</span><span class="grid-day-cell">16</span>
                            <span class="grid-day-cell">17</span><span class="grid-day-cell">18</span><span class="grid-day-cell">19</span><span class="grid-day-cell">20</span><span class="grid-day-cell">21</span><span class="grid-day-cell">22</span><span class="grid-day-cell active">23</span>
                            <span class="grid-day-cell">24</span><span class="grid-day-cell marked coral">25</span><span class="grid-day-cell">26</span><span class="grid-day-cell marked blue">27</span><span class="grid-day-cell marked coral">28</span><span class="grid-day-cell">29</span><span class="grid-day-cell marked coral">30</span>
                            <span class="grid-day-cell">31</span>
                        </div>
                    </div>

                    <div class="legend-indicators-row">
                        <span class="legend-node"><span class="dot crimson"></span>Hari ini</span>
                        <span class="legend-node"><span class="dot coral"></span>Deadline</span>
                        <span class="legend-node"><span class="dot blue"></span>Kegiatan</span>
                    </div>

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
</div>
<?php include $base_path . "includes/footer.php"; ?>
