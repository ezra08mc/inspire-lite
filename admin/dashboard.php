<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$admin_name = "";
$admin_id = $_SESSION["username"];

try {
    $stmt = $pdo->prepare("SELECT admin_id, first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
        $admin_id = $admin_data['admin_id'];
    }
} catch (PDOException $e) {}

$stats = ['total_students' => 0, 'total_lecturers' => 0, 'total_staff' => 0, 'total_announcements' => 0];
try {
    $stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $stats['total_lecturers'] = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
    $stats['total_staff'] = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
    $stats['total_announcements'] = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
} catch (PDOException $e) {}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC LIMIT 3");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {}

$initials = "AD";
if (!empty($admin_name)) {
    $parts = explode(" ", $admin_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ""));
}
$display_name = $admin_name ?: "Administrator";
$unread_count = 0;

$base_path = "../";
$page_title = "Admin Dashboard - INSPIRE Lite";
$current_page = "dashboard";

include $base_path . "includes/header.php";
?>

<style>
    /* Pengunci Skala SVG agar tidak terjadi pembengkakan elemen */
    svg {
        max-width: 100% !important;
        height: auto !important;
    }
    .sidebar-brand svg, .sidebar-logo-svg {
        width: 24px !important;
        height: 24px !important;
    }
    .avatar-circle svg, .profile-clickable-zone svg, .user-panel svg {
        width: 20px !important;
        height: 20px !important;
    }
    .action-node-icon svg {
        width: 24px !important;
        height: 24px !important;
    }
    
    /* Konstruksi flexbox darurat untuk menyatukan sidebar dan konten utama */
    .app-layout-wrapper {
        display: flex !important;
        width: 100% !important;
        min-height: 100vh !important;
        flex-direction: row !important;
        overflow-x: hidden !important;
    }
    .main-content {
        flex: 1 !important;
        min-width: 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }
</style>

<div class="app-layout-wrapper">
    <?php include $base_path . "includes/sidebar.php"; ?>
    <div class="main-content">
        <?php include $base_path . "includes/topbar.php"; ?>
        <main class="dashboard-viewport" style="padding: 24px;">
            <section class="hero-banner">
                <div class="hero-title">
                    <h1>Halo, <?= htmlspecialchars(explode(' ', $display_name)[0]) ?>!</h1>
                    <p>Administrator · Panel Kontrol Sistem</p>
                </div>
                <div class="metrics-row">
                    <div class="metric-card"><span class="metric-label">TOTAL MAHASISWA</span><span class="metric-value"><?= $stats['total_students'] ?></span></div>
                    <div class="metric-card"><span class="metric-label">TOTAL DOSEN</span><span class="metric-value"><?= $stats['total_lecturers'] ?></span></div>
                    <div class="metric-card"><span class="metric-label">TOTAL STAF</span><span class="metric-value"><?= $stats['total_staff'] ?></span></div>
                    <div class="metric-card"><span class="metric-label">PENGUMUMAN</span><span class="metric-value"><?= $stats['total_announcements'] ?></span></div>
                </div>
            </section>

            <div class="split-grid flex-equal-align">
                <div class="content-card">
                    <div class="card-top"><h3>Aksi Cepat</h3></div>
                    <div class="quick-actions-box flex-stretch-actions">
                        <a href="users/manage.php" class="action-node"><div class="action-node-icon absensi"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div><span>Kelola Pengguna</span></a>
                        <a href="users/provisions.php" class="action-node"><div class="action-node-icon k-studi"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div><span>Penyediaan Akun</span></a>
                        <a href="announcements.php" class="action-node"><div class="action-node-icon krs"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg></div><span>Pengumuman</span></a>
                        <a href="settings/calendar.php" class="action-node"><div class="action-node-icon jadwal"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg></div><span>Kalender</span></a>
                    </div>
                </div>
                <div class="content-card">
                    <div class="card-top"><h3>Pengumuman Terbaru</h3><a href="announcements.php" class="view-all-link">Kelola</a></div>
                    <div class="announcement-stack">
                        <?php foreach ($announcements as $a): ?>
                            <div class="announcement-node">
                                <div class="node-badge <?= htmlspecialchars($a["badge_class"]) ?>"><?= htmlspecialchars($a["type"]) ?></div>
                                <div class="node-content"><p class="node-date"><?= htmlspecialchars($a["date_text"]) ?></p><p class="node-title"><?= htmlspecialchars($a["title"]) ?></p></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include $base_path . "includes/footer.php"; ?>
