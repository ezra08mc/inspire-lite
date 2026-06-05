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
$position = "Administrator";

try {
    $stmt = $pdo->prepare("SELECT admin_id, first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
        $admin_id = $admin_data['admin_id'];
    }
} catch (PDOException $e) {}

$current_month = "Juni 2026";
$current_day = 5;
$current_year = 2026;

// Get system statistics
$stats = [
    'total_students' => 0,
    'total_lecturers' => 0,
    'total_staff' => 0,
    'total_announcements' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $result = $stmt->fetch();
    $stats['total_students'] = $result['count'];
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lecturers");
    $result = $stmt->fetch();
    $stats['total_lecturers'] = $result['count'];
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
    $result = $stmt->fetch();
    $stats['total_staff'] = $result['count'];
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
    $result = $stmt->fetch();
    $stats['total_announcements'] = $result['count'];
} catch (PDOException $e) {}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {}

// Get admin tasks
$tasks = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks WHERE user_id = :user_id ORDER BY id ASC");
    $stmt->execute([':user_id' => $user_id]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {}

// Get admin notifications
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT text_content, time_ago, is_read, icon_symbol FROM student_notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 5");
    $stmt->execute([':user_id' => $user_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {}
$unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));

$initials = "";
if (!empty($admin_name)) {
    $name_array = explode(' ', $admin_name);
    $initials = strtoupper(substr($name_array[0], 0, 1) . substr($name_array[1] ?? '', 0, 1));
} else {
    $initials = "AD";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>INSPIRE LITE - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <script>
        (function() {
            const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            if (width <= 768) {
                document.documentElement.classList.add('preload-collapsed');
            } else {
                document.documentElement.classList.add('preload-expanded');
            }
        })();
    </script>
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="dashboard-page">

    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
            <h2>INSPIRE LITE</h2>
        </div>
        
        <nav class="sidebar-menu">
            <div class="menu-category">MAIN MENU</div>
            <a href="dashboard.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            
            <div class="menu-category-toggle expanded" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <span>PENGELOLAAN PENGGUNA</span>
                <svg class="arrow down" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: flex;">
                <a href="users/manage.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2c1.66 0 3-1.34 3-3S7.66 4 6 4 3 5.34 3 7s1.34 3 3 3zm0 4c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm9 0c-.29 0-.62.02-.97.05 1.16.89 1.97 2.48 1.97 4.21v3h6v-3c0-2.66-4.05-4-4-4z"/></svg> Kelola Pengguna
                </a>
                <a href="users/provisions.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Penyediaan Akun <span class="nav-badge">0</span>
                </a>
            </div>

            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                <span>INFORMASI & KONTEN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="announcements.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg> Pengumuman <span class="nav-badge"><?= $stats['total_announcements'] ?></span>
                </a>
                <a href="calendar.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Kalender Akademik
                </a>
            </div>

            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                <span>AKADEMIK</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="academic/enrollment.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Data Pendaftaran
                </a>
                <a href="academic/grades.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg> Nilai Akademik
                </a>
                <a href="academic/subjects.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg> Mata Kuliah
                </a>
            </div>

            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 1C5.92 1 1 5.92 1 12s4.92 11 11 11 11-4.92 11-11S18.08 1 12 1zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                <span>SISTEM</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="infrastructure/backup.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-6-11l-4 4h3v4h2v-4h3l-4-4z"/></svg> Backup Data
                </a>
                <a href="security/logs.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 1C5.92 1 1 5.92 1 12s4.92 11 11 11 11-4.92 11-11S18.08 1 12 1zm1 16h-2v2h2v-2zm0-14h-2v12h2V3z"/></svg> Log Sistem
                </a>
                <a href="settings/calendar.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Pengaturan
                </a>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="navbar">
            <button class="menu-toggle-hamburger" id="hamburgerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>

            <div class="search-container">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" placeholder="Cari data, pengguna, pengumuman...">
            </div>
            
            <div class="user-panel">
                <div class="notification-wrapper">
                    <button class="notification-bell" id="notifBellBtn">
                        <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                        <?php if ($unread_count > 0): ?>
                            <span class="bell-badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-panel-notif" id="notifDropdown">
                        <div class="dropdown-header">Notifikasi</div>
                        <div class="dropdown-list-container">
                            <?php if (empty($notifications)): ?>
                                <div class="empty-fallback-text">Tidak ada notifikasi aktif.</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <div class="dropdown-item-node <?= $n['is_read'] ? '' : 'unread' ?>">
                                        <div class="node-icon"><?= htmlspecialchars($n['icon_symbol']) ?></div>
                                        <div class="node-body">
                                            <p><?= htmlspecialchars($n['text_content']) ?></p>
                                            <span><?= htmlspecialchars($n['time_ago']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="account-interaction-wrapper pc-only-wrapper">
                    <div class="profile-clickable-zone" id="profileMenuBtn">
                        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                        <div class="user-meta">
                            <span class="user-name-text"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></span>
                            <span class="user-id-text"><?= htmlspecialchars($admin_id) ?></span>
                        </div>
                    </div>
                    <div class="dropdown-panel-account" id="accountDropdown">
                        <div class="dropdown-account-header">
                            <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                            <div>
                                <p class="head-title"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></p>
                                <p class="head-sub"><?= htmlspecialchars($admin_id) ?></p>
                            </div>
                        </div>
                        <a href="settings/profile.php" class="account-drop-link">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Profil Saya
                        </a>
                        <a href="settings/preferences.php" class="account-drop-link">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Preferensi
                        </a>
                        <div class="divider"></div>
                        <a href="../logout.php" class="account-drop-link logout">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-viewport">
            <section class="hero-banner">
                <div class="hero-title">
                    <h1>Selamat datang, <?= htmlspecialchars(explode(' ', $admin_name ?: 'Administrator')[0]) ?>! 👋</h1>
                    <p>Administrator · Panel Kontrol Sistem · TA <?= $current_year ?></p>
                </div>
                <div class="metrics-row">
                    <div class="metric-card">
                        <span class="metric-label">TOTAL MAHASISWA</span>
                        <span class="metric-value"><?= $stats['total_students'] ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">TOTAL DOSEN</span>
                        <span class="metric-value"><?= $stats['total_lecturers'] ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">TOTAL STAF</span>
                        <span class="metric-value"><?= $stats['total_staff'] ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">PENGUMUMAN</span>
                        <span class="metric-value"><?= $stats['total_announcements'] ?></span>
                    </div>
                </div>
            </section>

            <div class="split-grid flex-equal-align">
                <div class="content-card taller-box">
                    <div class="card-top">
                        <h3>Menu Utama</h3>
                        <a href="#" class="action-link">Selengkapnya →</a>
                    </div>
                    <div class="quick-actions-box flex-stretch-actions">
                        <div class="action-node">
                            <div class="action-node-icon absensi">
                                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <span>Kelola Pengguna</span>
                        </div>
                        <div class="action-node">
                            <div class="action-node-icon k-studi">
                                <svg viewBox="0 0 24 24"><path d="M21 4H3c-1.11 0-2 .89-2 2v12c0 1.1.89 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1.5 3c.83 0 1.5.67 1.5 1.5S20.33 10 19.5 10 18 9.33 18 8.5 18.67 7 19.5 7zM6 15H4v-2h2v2zm0-4H4V9h2v2zm14 4H8v-2h12v2zm0-4H8V9h12v2z"/></svg>
                            </div>
                            <span>Data Akademik</span>
                        </div>
                        <div class="action-node">
                            <div class="action-node-icon transkrip">
                                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                            </div>
                            <span>Laporan Sistem</span>
                        </div>
                        <div class="action-node">
                            <div class="action-node-icon jadwal">
                                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                            </div>
                            <span>Kalender</span>
                        </div>
                        <div class="action-node">
                            <div class="action-node-icon krs">
                                <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            </div>
                            <span>Pengumuman</span>
                        </div>
                        <div class="action-node">
                            <div class="action-node-icon bimbingan">
                                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
                            </div>
                            <span>Pengaturan Sistem</span>
                        </div>
                    </div>
                    <div class="card-action-triggers">
                        <button class="trigger-btn btn-blue">Lihat Dashboard Lengkap</button>
                        <button class="trigger-btn btn-red">Laporan Cepat</button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Pengumuman Terbaru</h3>
                        <a href="announcements.php" class="action-link">Kelola semua →</a>
                    </div>
                    <div class="announcements-feed">
                        <?php if (empty($announcements)): ?>
                            <div class="empty-fallback-text">Tidak ada pengumuman terbaru.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($announcements, 0, 3) as $annc): ?>
                                <div class="annc-node">
                                    <div class="annc-top-line">
                                        <div class="annc-icon-frame <?= htmlspecialchars($annc['badge_class']) ?>">
                                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                        </div>
                                        <div class="annc-title-area">
                                            <div class="annc-meta">
                                                <?php if($annc['type'] === 'PENTING'): ?>
                                                    <span class="label-badge red">PENTING</span>
                                                <?php endif; ?>
                                                <span class="annc-date"><?= htmlspecialchars($annc['date_text']) ?></span>
                                            </div>
                                            <h4><?= htmlspecialchars($annc['title']) ?></h4>
                                        </div>
                                    </div>
                                    <p class="annc-body-text"><?= htmlspecialchars(substr($annc['content'], 0, 100)) ?>...</p>
                                    <span class="annc-dept"><?= htmlspecialchars($annc['author']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="split-grid height-fluid">
                <div class="content-card">
                    <div class="card-top">
                        <h3>Tugas Admin</h3>
                        <a href="#" class="action-link">Lihat semua →</a>
                    </div>
                    <div class="tasks-vertical-stack">
                        <?php if (empty($tasks)): ?>
                            <div class="empty-fallback-text border-box-pad">Semua tugas telah diselesaikan. Sistem berjalan normal.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($tasks, 0, 5) as $task): ?>
                                <div class="task-node <?= $task['is_completed'] ? 'done' : '' ?>">
                                    <div class="task-checkbox-frame">
                                        <?php if($task['is_completed']): ?>
                                            <svg viewBox="0 0 24 24" class="check-svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-node-details">
                                        <span class="task-node-title"><?= htmlspecialchars($task['name']) ?></span>
                                        <span class="task-node-time <?= $task['is_alert'] ? 'alert' : '' ?>"><?= htmlspecialchars($task['deadline_text']) ?></span>
                                    </div>
                                    <svg class="chevron-right-item" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Aktivitas Sistem</h3>
                        <select class="agenda-month-dropdown">
                            <option><?= htmlspecialchars($current_month) ?></option>
                        </select>
                    </div>
                    <div class="calendar-component">
                        <div class="calendar-days-grid">
                            <span class="grid-header-cell">Min</span><span class="grid-header-cell">Sen</span><span class="grid-header-cell">Sel</span><span class="grid-header-cell">Rab</span><span class="grid-header-cell">Kam</span><span class="grid-header-cell">Jum</span><span class="grid-header-cell">Sab</span>
                            <span></span><span></span><span></span><span></span><span></span><span></span><span class="grid-day-cell">1</span>
                            <span class="grid-day-cell">2</span><span class="grid-day-cell">3</span><span class="grid-day-cell">4</span><span class="grid-day-cell active">5</span><span class="grid-day-cell">6</span><span class="grid-day-cell">7</span><span class="grid-day-cell">8</span>
                            <span class="grid-day-cell">9</span><span class="grid-day-cell">10</span><span class="grid-day-cell">11</span><span class="grid-day-cell">12</span><span class="grid-day-cell">13</span><span class="grid-day-cell">14</span><span class="grid-day-cell">15</span>
                            <span class="grid-day-cell">16</span><span class="grid-day-cell">17</span><span class="grid-day-cell">18</span><span class="grid-day-cell">19</span><span class="grid-day-cell">20</span><span class="grid-day-cell">21</span><span class="grid-day-cell">22</span>
                            <span class="grid-day-cell">23</span><span class="grid-day-cell">24</span><span class="grid-day-cell">25</span><span class="grid-day-cell">26</span><span class="grid-day-cell">27</span><span class="grid-day-cell">28</span><span class="grid-day-cell">29</span>
                            <span class="grid-day-cell">30</span>
                        </div>
                    </div>
                    
                    <div class="legend-indicators-row">
                        <span class="legend-node"><span class="dot crimson"></span>Hari Ini</span>
                        <span class="legend-node"><span class="dot coral"></span>Pemeliharaan</span>
                        <span class="legend-node"><span class="dot blue"></span>Event Penting</span>
                    </div>

                    <div class="agenda-table-wrapper">
                        <div class="agenda-table-header">
                            <span class="col-head">AKTIVITAS</span>
                            <span class="col-head">WAKTU</span>
                            <span class="col-head">STATUS</span>
                        </div>
                        <div class="agenda-rows-stack">
                            <div class="agenda-table-row">
                                <div class="col-cell cell-date">
                                    <span class="indicator-circle-bullet blue"></span>
                                    <span class="date-lbl-txt">Backup Sistem</span>
                                </div>
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline">Backup Database Otomatis</span>
                                    <span class="item-sub-clock">Setiap hari pukul 02:00 WIB</span>
                                </div>
                                <div class="col-cell cell-room-loc"><span style="color: #10b981;">✓ Aktif</span></div>
                            </div>
                            <div class="agenda-table-row">
                                <div class="col-cell cell-date">
                                    <span class="indicator-circle-bullet blue"></span>
                                    <span class="date-lbl-txt">Update Sistem</span>
                                </div>
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline">Pembaruan Keamanan</span>
                                    <span class="item-sub-clock">Terakhir: 1 Juni 2026</span>
                                </div>
                                <div class="col-cell cell-room-loc"><span style="color: #10b981;">✓ Terbaru</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <div class="mobile-exclusive-profile-flyout" id="mobileProfileFlyout">
            <div class="dropdown-account-header">
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <p class="head-title"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></p>
                    <p class="head-sub"><?= htmlspecialchars($admin_id) ?></p>
                </div>
            </div>
            <a href="settings/profile.php" class="account-drop-link">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Profil Saya
            </a>
            <a href="settings/preferences.php" class="account-drop-link">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Preferensi
            </a>
            <div class="divider"></div>
            <a href="../logout.php" class="account-drop-link logout">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
            </a>
        </div>

        <nav class="mobile-bottom-navigation-dock-bar">
            <a href="#" class="mobile-nav-tab active">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span>Beranda</span>
            </a>
            <button class="mobile-nav-tab" id="mobileProfileTabBtn">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                <span>Profil</span>
            </button>
            <button class="mobile-nav-tab" id="mobileMenuTriggerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                <span>Menu</span>
            </button>
        </nav>
    </div>
</body>
</html>
