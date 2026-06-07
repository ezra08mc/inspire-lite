<?php
$initials = $initials ?? "??";
$display_name = $display_name ?? ($_SESSION["username"] ?? "User");
$role_label = ucfirst($_SESSION["role"] ?? "User");
?>
<header class="top-nav">
    <div class="nav-left">
        <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
            <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <div class="search-bar">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" placeholder="Cari di portal...">
        </div>
    </div>
    <div class="nav-right">
        <div class="notif-bell">
            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <span class="bell-dot"></span>
            <?php endif; ?>
        </div>
        <div class="user-profile-trigger">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info-text">
                <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
                <span class="user-role"><?= htmlspecialchars($role_label) ?></span>
            </div>
        </div>
    </div>
</header>
