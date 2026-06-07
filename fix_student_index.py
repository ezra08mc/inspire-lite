# -*- coding: utf-8 -*-
import codecs

content = u"""<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_name = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

// Fetch student profile data
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
        $study_program = $student_data["study_program"] ?? "";
        $cohort = $student_data["cohort"] ?? "";
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat profil mahasiswa.";
    error_log($e->getMessage());
}

$academic_year = null;
$hero_meta_parts = [];
if (!empty($study_program)) {
    $hero_meta_parts[] = $study_program;
}
if (!empty($nim)) {
    $hero_meta_parts[] = $nim;
}
$hero_meta = implode(" \u00b7 ", $hero_meta_parts);

$stats = [
    "sks_ditempuh" => "0",
    "ipk_kumulatif" => "0.00",
    "ip_semester" => "0.00",
    "sks_semester" => "0",
];
try {
    $stmt = $pdo->prepare("SELECT sks_ditempuh, ipk_kumulatif, ip_semester, sks_semester FROM student_academic_stats WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $user_id]);
    $db_stats = $stmt->fetch();
    if ($db_stats) {
        $stats["sks_ditempuh"] = $db_stats["sks_ditempuh"] ?? "0";
        $stats["ipk_kumulatif"] = $db_stats["ipk_kumulatif"] ?? "0.00";
        $stats["ip_semester"] = $db_stats["ip_semester"] ?? "0.00";
        $stats["sks_semester"] = $db_stats["sks_semester"] ?? "0";
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat statistik akademik.";
    error_log($e->getMessage());
}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat pengumuman.";
}

$tasks = [];
try {
    $stmt = $pdo->prepare("SELECT name, deadline_text, is_alert, is_completed FROM active_tasks WHERE user_id = :user_id");
    $stmt->execute([":user_id" => $user_id]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat daftar tugas.";
}

$initials = "";
if (!empty($student_name)) {
    $name_parts = preg_split("/\\\\s+/", trim($student_name));
    $initials = strtoupper(substr($name_parts[0] ?? "", 0, 1) . substr($name_parts[1] ?? "", 0, 1));
} else {
    $initials = strtoupper(substr($nim, 0, 2));
}

$display_name = $student_name ?: $nim;
$unread_count = 0;

$base_path = "../";
$page_title = "Beranda - INSPIRE Lite";
$current_page = "dashboard";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>

<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>

    <main class="dashboard-viewport">
        <?php if (!empty($data_errors)): ?>
            <div class="empty-fallback-text border-box-pad"><?= htmlspecialchars($data_errors[0]) ?></div>
        <?php endif; ?>
        
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Halo, <?= htmlspecialchars($first_name ?? "Mahasiswa") ?>!</h1>
                <?php if (!empty($hero_meta)): ?>
                    <p><?= htmlspecialchars($hero_meta) ?></p>
                <?php endif; ?>
            </div>
            <div class="metrics-row">
                <div class="metric-card">
                    <span class="metric-label">SKS DITEMPUH</span>
                    <span class="metric-value"><?= htmlspecialchars($stats["sks_ditempuh"]) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">IPK KUMULATIF</span>
                    <span class="metric-value"><?= htmlspecialchars($stats["ipk_kumulatif"]) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">IP SEMESTER</span>
                    <span class="metric-value"><?= htmlspecialchars($stats["ip_semester"]) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">SKS SEMESTER</span>
                    <span class="metric-value"><?= htmlspecialchars($stats["sks_semester"]) ?></span>
                </div>
            </div>
        </section>

        <div class="split-grid flex-equal-align">
            <div class="content-card">
                <div class="card-top">
                    <h3>Aksi Cepat</h3>
                </div>
                <div class="quick-actions-box flex-stretch-actions">
                    <a href="presensi.php" class="action-node">
                        <div class="action-node-icon absensi">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <span>Absensi</span>
                    </a>
                    <a href="kartu-mahasiswa.php" class="action-node">
                        <div class="action-node-icon k-studi">
                            <svg viewBox="0 0 24 24"><path d="M21 4H3c-1.11 0-2 .89-2 2v12c0 1.1.89 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1.5 3c.83 0 1.5.67 1.5 1.5S20.33 10 19.5 10 18 9.33 18 8.5 18.67 7 19.5 7zM6 15H4v-2h2v2zm0-4H4V9h2v2zm14 4H8v-2h12v2zm0-4H8V9h12v2z"/></svg>
                        </div>
                        <span>Kartu Studi</span>
                    </a>
                    <a href="transkrip.php" class="action-node">
                        <div class="action-node-icon transkrip">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                        </div>
                        <span>Transkrip</span>
                    </a>
                    <a href="jadwal.php" class="action-node">
                        <div class="action-node-icon jadwal">
                            <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                        </div>
                        <span>Jadwal</span>
                    </a>
                </div>
            </div>

            <div class="content-card">
                <div class="card-top">
                    <h3>Tugas Aktif</h3>
                </div>
                <div class="task-list">
                    <?php if (empty($tasks)): ?>
                        <div class="empty-fallback-text">Semua tugas selesai!</div>
                    <?php else: ?>
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-node <?= $t["is_completed"] ? "completed" : "" ?>">
                                <div class="task-status">
                                    <div class="check-circle <?= $t["is_completed"] ? "checked" : "" ?>"></div>
                                </div>
                                <div class="task-info">
                                    <p class="task-name"><?= htmlspecialchars($t["name"]) ?></p>
                                    <p class="task-deadline <?= $t["is_alert"] ? "alert" : "" ?>"><?= htmlspecialchars($t["deadline_text"]) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="split-grid">
            <div class="content-card">
                <div class="card-top">
                    <h3>Pengumuman Terbaru</h3>
                    <a href="announcements.php" class="view-all-link">Lihat Semua</a>
                </div>
                <div class="announcement-stack">
                    <?php if (empty($announcements)): ?>
                        <div class="empty-fallback-text">Belum ada pengumuman.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($announcements, 0, 3) as $a): ?>
                            <div class="announcement-node">
                                <div class="node-badge <?= htmlspecialchars($a["badge_class"]) ?>"><?= htmlspecialchars($a["type"]) ?></div>
                                <div class="node-content">
                                    <p class="node-date"><?= htmlspecialchars($a["date_text"]) ?></p>
                                    <p class="node-title"><?= htmlspecialchars($a["title"]) ?></p>
                                    <p class="node-snippet"><?= htmlspecialchars($a["content"]) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include $base_path . "includes/footer.php"; ?>
"""

with codecs.open("student/index.php", "w", "utf-8") as f:
    f.write(content)

