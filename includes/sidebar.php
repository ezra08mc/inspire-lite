<?php
if (!isset($base_path)) {
    $base_path = "./";
}
if (!isset($current_page)) {
    $current_page = "";
}
$role = $_SESSION["role"] ?? "";
?>
<aside class="sidebar" id="sidebarMenu">
    <div class="sidebar-brand">
        <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
        <h2>INSPIRE LITE</h2>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-category">MAIN MENU</div>

        <?php if ($role === "student"): ?>
            <a href="<?= $base_path ?>student/index.php" class="menu-item <?= $current_page ===
"dashboard"
    ? "active"
    : "" ?>">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            <a href="<?= $base_path ?>student/profile.php" class="menu-item <?= $current_page ===
"profile"
    ? "active"
    : "" ?>">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg> PROFIL
            </a>
            <div class="menu-category-toggle" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>PUSAT INFORMASI</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="<?= $base_path ?>student/announcements.php" class="sub-menu-link">Pengumuman</a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v2.56L12 19l7-3.26v-2.56L12 16.5l-7-3.32z"/></svg>
                <span>PERKULIAHAN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="<?= $base_path ?>student/perkuliahan/jadwal.php" class="sub-menu-link">Jadwal Kuliah</a>
                <a href="<?= $base_path ?>student/perkuliahan/krs.php" class="sub-menu-link">KRS</a>
                <a href="<?= $base_path ?>student/perkuliahan/khs.php" class="sub-menu-link">KHS</a>
                <a href="<?= $base_path ?>student/perkuliahan/presensi.php" class="sub-menu-link">Presensi</a>
                <a href="<?= $base_path ?>student/perkuliahan/tugas.php" class="sub-menu-link">Tugas</a>
                <a href="<?= $base_path ?>student/perkuliahan/bimbingan.php" class="sub-menu-link">Bimbingan</a>
                <a href="<?= $base_path ?>student/perkuliahan/kartu-mahasiswa.php" class="sub-menu-link">Kartu Mahasiswa</a>
                <a href="<?= $base_path ?>student/perkuliahan/transkrip.php" class="sub-menu-link">Transkrip</a>
            </div>
        <?php elseif ($role === "admin"): ?>

            <a href="<?= $base_path ?>admin/index.php" class="menu-item <?= $current_page ===
"dashboard"
    ? "active"
    : "" ?>">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>

            <div class="menu-category-toggle <?= in_array($current_page, [
                "manage_users",
                "provisions",
            ])
                ? "expanded"
                : "" ?>" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <span>PENGGUNA</span>
                <svg class="arrow <?= in_array($current_page, [
                    "manage_users",
                    "provisions",
                ])
                    ? "down"
                    : "" ?>" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: <?= in_array(
                $current_page,
                ["manage_users", "provisions"],
            )
                ? "flex"
                : "none" ?>;">
                <a href="<?= $base_path ?>admin/users/manage.php" class="sub-menu-link <?= $current_page ===
"manage_users"
    ? "active"
    : "" ?>">Kelola Pengguna</a>
                <a href="<?= $base_path ?>admin/users/provisions.php" class="sub-menu-link <?= $current_page ===
"provisions"
    ? "active"
    : "" ?>">Penyediaan Akun</a>
            </div>

            <div class="menu-category-toggle <?= in_array($current_page, [
                "announcements",
                "calendar",
            ])
                ? "expanded"
                : "" ?>" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                <span>AKADEMIK</span>
                <svg class="arrow <?= in_array($current_page, [
                    "announcements",
                    "calendar",
                ])
                    ? "down"
                    : "" ?>" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: <?= in_array(
                $current_page,
                ["announcements", "calendar"],
            )
                ? "flex"
                : "none" ?>;">
                <a href="<?= $base_path ?>admin/announcements.php" class="sub-menu-link <?= $current_page ===
"announcements"
    ? "active"
    : "" ?>">Pengumuman</a>
                <a href="<?= $base_path ?>admin/settings/calendar.php" class="sub-menu-link <?= $current_page ===
"calendar"
    ? "active"
    : "" ?>">Jadwal & Kalender</a>
            </div>

            <div class="menu-category-toggle <?= in_array($current_page, [
                "branding",
                "backup",
                "logs",
            ])
                ? "expanded"
                : "" ?>" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                <span>SISTEM</span>
                <svg class="arrow <?= in_array($current_page, [
                    "branding",
                    "backup",
                    "logs",
                ])
                    ? "down"
                    : "" ?>" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: <?= in_array(
                $current_page,
                ["branding", "backup", "logs"],
            )
                ? "flex"
                : "none" ?>;">
                <a href="<?= $base_path ?>admin/assets/branding.php" class="sub-menu-link <?= $current_page ===
"branding"
    ? "active"
    : "" ?>">Branding Portal</a>
                <a href="<?= $base_path ?>admin/infrastructure/backup.php" class="sub-menu-link <?= $current_page ===
"backup"
    ? "active"
    : "" ?>">Backup Data</a>
                <a href="<?= $base_path ?>admin/security/logs.php" class="sub-menu-link <?= $current_page ===
"logs"
    ? "active"
    : "" ?>">Log Keamanan</a>
            </div>

        <?php elseif ($role === "lecturer"): ?>
            <a href="<?= $base_path ?>lecturer/index.php" class="menu-item <?= $current_page ===
"dashboard"
    ? "active"
    : "" ?>">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            <a href="<?= $base_path ?>lecturer/profile.php" class="menu-item <?= $current_page ===
"profile"
    ? "active"
    : "" ?>">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg> PROFIL
            </a>
            <div class="menu-category-toggle" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>PUSAT INFORMASI</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="<?= $base_path ?>lecturer/announcements.php" class="sub-menu-link">Pengumuman</a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                <span>PERKULIAHAN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="<?= $base_path ?>lecturer/schedule.php" class="sub-menu-link">Jadwal Mengajar</a>
                <a href="<?= $base_path ?>lecturer/courses.php" class="sub-menu-link">Mata Kuliah</a>
                <a href="<?= $base_path ?>lecturer/attendance.php" class="sub-menu-link">Absensi</a>
                <a href="<?= $base_path ?>lecturer/assignments.php" class="sub-menu-link">Tugas</a>
                <a href="<?= $base_path ?>lecturer/grading.php" class="sub-menu-link">Penilaian</a>
                <a href="<?= $base_path ?>lecturer/materials.php" class="sub-menu-link">Materi</a>
                <a href="<?= $base_path ?>lecturer/advising.php" class="sub-menu-link">Bimbingan</a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(event, this)">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5z"/></svg>
                <span>LAPORAN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="<?= $base_path ?>lecturer/reports.php" class="sub-menu-link">Laporan</a>
            </div>
        <?php endif; ?>

        <div class="menu-category">SYSTEM</div>
        <a href="<?= $base_path ?>logout.php" class="menu-item">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg> KELUAR
        </a>
    </nav>
</aside>
