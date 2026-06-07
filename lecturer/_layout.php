<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "lecturer") {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)($_SESSION["user_id"] ?? 0);
$lecturer_name = '';
$nip = '';
$expertise = '';
$cohort = (int)date('Y');

try {
    $stmt = $pdo->prepare("SELECT nip, first_name, last_name, degree, expertise, birth_date FROM lecturers WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $lecturer = $stmt->fetch();
    if ($lecturer) {
        $lecturer_name = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
        $nip = $lecturer['nip'];
        $expertise = $lecturer['expertise'];
    }
} catch (PDOException $e) {
    // ignore
}

$initials = 'BS';
if (!empty($lecturer_name)) {
    $name_array = preg_split('/\s+/', trim($lecturer_name));
    $initials = strtoupper(substr($name_array[0] ?? '', 0, 1) . (substr($name_array[1] ?? '', 0, 1)));
    if ($initials === '') $initials = 'BS';
}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
}

$notifications = [];
$unread_count = 0;
try {
    // Match database.sql structure for student_notifications:
    // id, title, content, category, sender, created_at (no user_id/text_content/time_ago/is_read/icon_symbol)
    // We'll still show latest announcements for the lecturer screen.
    $stmt = $pdo->prepare("SELECT id, title, content, category, sender, created_at FROM student_notifications ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll();

    // No is_read field in schema; keep badge hidden unless you later add read-tracking.
    $unread_count = 0;
} catch (PDOException $e) {
    $notifications = [];
    $unread_count = 0;
}


$activePage = basename($_SERVER['PHP_SELF']);
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>INSPIRE LITE - Portal Dosen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
<body>

<aside class="sidebar" id="sidebarMenu">
    <div class="sidebar-brand">
        <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
        <h2>INSPIRE LITE</h2>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-category">MAIN MENU</div>
        <a href="dashboard.php" class="menu-item <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
        </a>
        <a href="profile.php" class="menu-item <?= $activePage === 'profile.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg> PROFIL
        </a>

        <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
            <span>PUSAT INFORMASI</span>
            <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
        </div>
        <div class="submenu-items" style="display: none;">
            <a href="announcements.php" class="sub-menu-link">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                Pengumuman <span class="nav-badge"><?= count($announcements) ?></span>
            </a>
        </div>

        <div class="menu-category-toggle expanded" onclick="toggleSubmenu(this)">
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
            <span>PERKULIAHAN</span>
            <svg class="arrow down" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
        </div>
        <div class="submenu-items" style="display: flex;">
            <a href="schedule.php" class="sub-menu-link <?= $activePage === 'schedule.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Jadwal Mengajar
            </a>
            <a href="courses.php" class="sub-menu-link <?= $activePage === 'courses.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2z"/></svg> Mata Kuliah
            </a>
            <a href="attendance.php" class="sub-menu-link <?= $activePage === 'attendance.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Absensi
            </a>
            <a href="assignments.php" class="sub-menu-link <?= $activePage === 'assignments.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-2 16H8v-2h4v2zm3-4H8v-2h7v2zm0-4H8V8h7v2z"/></svg> Tugas
            </a>
            <a href="grading.php" class="sub-menu-link <?= $activePage === 'grading.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg> Penilaian
            </a>
            <a href="materials.php" class="sub-menu-link <?= $activePage === 'materials.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 15l-4-4 1.41-1.41L12 15.17l6.59-6.59L20 10l-8 8z"/></svg> Materi
            </a>
            <a href="advising.php" class="sub-menu-link <?= $activePage === 'advising.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5z"/></svg> Bimbingan
            </a>
        </div>

        <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5z"/></svg>
            <span>Laporan</span>
            <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
        </div>
        <div class="submenu-items" style="display: none;">
            <a href="reports.php" class="sub-menu-link <?= $activePage === 'reports.php' ? 'active-sub' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4V4zm2 4h12V6H6v2zm0 4h12v-2H6v2zm0 4h12v-2H6v2z"/></svg>
                Laporan
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
            <input type="text" placeholder="Cari mata kuliah, jadwal, nilai, pengumuman...">
        </div>

        <div class="user-panel">
            <div class="notification-wrapper">
                <button class="notification-bell" id="notifBellBtn">
                    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                    <?php if ($unread_count > 0): ?>
                        <span class="bell-badge"><?= (int)$unread_count ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-panel-notif" id="notifDropdown">
                    <div class="dropdown-header">Notifikasi</div>
                    <div class="dropdown-list-container">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-fallback-text">Tidak ada notifikasi aktif.</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="dropdown-item-node">
                                    <div class="node-icon"><?= h($n['category'] ?? '') ?></div>
                                    <div class="node-body">
                                        <p><?= h($n['content'] ?? ($n['title'] ?? '')) ?></p>
                                        <span><?= isset($n['created_at']) ? h($n['created_at']) : '' ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="account-interaction-wrapper pc-only-wrapper">
                <div class="profile-clickable-zone" id="profileMenuBtn">
                    <div class="avatar-circle"><?= h($initials) ?></div>
                    <div class="user-meta">
                        <span class="user-name-text"><?= h($lecturer_name ?: 'Dosen') ?></span>
                        <span class="user-id-text"><?= h($nip ?: '-') ?></span>
                    </div>
                </div>

                <div class="dropdown-panel-account" id="accountDropdown">
                    <div class="dropdown-account-header">
                        <div class="avatar-circle"><?= h($initials) ?></div>
                        <div>
                            <p class="head-title"><?= h($lecturer_name ?: 'Dosen') ?></p>
                            <p class="head-sub"><?= h($nip ?: '-') ?></p>
                        </div>
                    </div>
                    <a href="profile.php" class="account-drop-link">
                        <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Profil Saya
                    </a>
                    <div class="divider"></div>
                    <a href="../logout.php" class="account-drop-link logout">
                        <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    </header>

<?php
// the page content will be echoed by pages using this layout
?>

<div class="dashboard-body">


