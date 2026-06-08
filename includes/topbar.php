<?php
$initials = $initials ?? "??";
$display_name = $display_name ?? ($_SESSION["username"] ?? "User");
$role_label = ucfirst($_SESSION["role"] ?? "User");
$user_id_sub = $_SESSION["username"] ?? "-";
?>
<header class="navbar">
    <button class="menu-toggle-hamburger" id="hamburgerBtn">
        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>

    <div class="search-container">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" placeholder="Cari di portal...">
    </div>

    <div class="user-panel">
        <div class="notification-wrapper">
            <button class="notification-bell" id="notifBellBtn">
                <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                <?php if (isset($unread_count) && $unread_count > 0): ?>
                    <span class="bell-badge"><?= (int)$unread_count ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-panel-notif" id="notifDropdown">
                <div class="dropdown-header">Notifikasi</div>
                <div class="dropdown-list-container">
                    <div class="empty-fallback-text">Tidak ada notifikasi baru.</div>
                </div>
            </div>
        </div>

        <div class="account-interaction-wrapper">
            <div class="profile-clickable-zone" id="profileMenuBtn">
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info-text pc-only">
                    <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
                    <span class="user-role"><?= htmlspecialchars($role_label) ?></span>
                </div>
            </div>
            
            <div class="dropdown-panel-account" id="accountDropdown">
                <div class="dropdown-account-header">
                    <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-meta">
                        <p class="head-title"><?= htmlspecialchars($display_name) ?></p>
                        <p class="head-sub"><?= htmlspecialchars($user_id_sub) ?></p>
                    </div>
                </div>
                <a href="<?= $base_path ?>student/profile.php" class="account-drop-link">
                    <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Profil Saya
                </a>
                <div class="divider"></div>
                <a href="<?= $base_path ?>logout.php" class="account-drop-link logout">
                    <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>
