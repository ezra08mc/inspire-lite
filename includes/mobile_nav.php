<?php
if (!isset($base_path)) $base_path = "./";
if (!isset($role)) $role = $_SESSION["role"] ?? "student";
if (!isset($initials)) $initials = "??";
if (!isset($display_name)) $display_name = $_SESSION["username"] ?? "User";
if (!isset($user_id_sub)) $user_id_sub = $nim ?? ($_SESSION["username"] ?? "-");
if (!isset($current_page)) $current_page = "";
?>

<div class="mobile-exclusive-profile-flyout" id="mobileProfileFlyout">
    <div class="dropdown-account-header">
        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
        <div>
            <p class="head-title"><?= htmlspecialchars($display_name) ?></p>
            <p class="head-sub"><?= htmlspecialchars($user_id_sub) ?></p>
        </div>
    </div>
    <a href="<?= $base_path . $role ?>/profile.php" class="account-drop-link">
        <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Profil Saya
    </a>
    <div class="divider"></div>
    <a href="<?= $base_path ?>logout.php" class="account-drop-link logout">
        <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
    </a>
</div>

<nav class="mobile-bottom-navigation-dock-bar">
    <a href="<?= $base_path . $role ?>/index.php" class="mobile-nav-tab <?= ($current_page === 'dashboard') ? 'active' : '' ?>">
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
