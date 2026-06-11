<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION["username"] ?? "Admin";
$initials = strtoupper(substr($username, 0, 2));

$counts = [
    "users" => 0,
    "admins" => 0,
    "students" => 0,
    "tasks" => 0,
];

try {
    $counts["users"] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $counts["admins"] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $counts["students"] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $counts["tasks"] = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Task Reminder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
            <h2>INSPIRE LITE</h2>
        </div>
        <nav class="sidebar-menu">
            <div class="menu-category">ACCOUNT MANAGER</div>
            <a href="dashboard.php" class="menu-item active"><svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> DASHBOARD</a>
            <a href="users/manage.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> MANAGE USERS</a>
            <a href="../logout.php" class="menu-item"><svg viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> LOGOUT</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="navbar">
            <button class="menu-toggle-hamburger" id="hamburgerBtn"><svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg></button>
            <div class="page-title-header"><h1>Admin Dashboard</h1></div>
            <div class="user-panel">
                <div class="profile-clickable-zone">
                    <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                    <div class="user-info-text pc-only">
                        <p class="user-name"><?= htmlspecialchars($username) ?></p>
                        <p class="user-role">Admin</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-viewport">
            <section class="hero-banner">
                <div class="hero-title">
                    <h1>Admin Account Manager</h1>
                    <p>Manage user accounts and keep the student task system organized.</p>
                </div>
                <div class="metrics-row">
                    <div class="metric-card"><span class="metric-label">USERS</span><span class="metric-value"><?= htmlspecialchars((string)$counts["users"]) ?></span></div>
                    <div class="metric-card"><span class="metric-label">ADMINS</span><span class="metric-value"><?= htmlspecialchars((string)$counts["admins"]) ?></span></div>
                    <div class="metric-card"><span class="metric-label">STUDENTS</span><span class="metric-value"><?= htmlspecialchars((string)$counts["students"]) ?></span></div>
                    <div class="metric-card"><span class="metric-label">TASKS</span><span class="metric-value"><?= htmlspecialchars((string)$counts["tasks"]) ?></span></div>
                </div>
            </section>

            <div class="content-card">
                <div class="card-top">
                    <h3>Quick Actions</h3>
                    <a href="users/manage.php" class="action-link">Open user manager</a>
                </div>
                <p class="empty-fallback-text border-box-pad">Use the user manager to add student accounts, update roles, reset passwords, or remove inactive users.</p>
            </div>
        </main>
    </div>
</body>
</html>
