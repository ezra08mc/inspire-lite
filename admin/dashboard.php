<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION["username"] ?? "Admin";
$initials = strtoupper(substr($username, 0, 2));

$stats = [
    "users" => 0,
    "admins" => 0,
    "students" => 0,
    "tasks" => 0
];

try {

    $stats["users"] = (int)$pdo
        ->query("SELECT COUNT(*) FROM users")
        ->fetchColumn();

    $stats["admins"] = (int)$pdo
        ->query("SELECT COUNT(*) FROM users WHERE role='admin'")
        ->fetchColumn();

    $stats["students"] = (int)$pdo
        ->query("SELECT COUNT(*) FROM users WHERE role='student'")
        ->fetchColumn();

    $stats["tasks"] = (int)$pdo
        ->query("SELECT COUNT(*) FROM tasks")
        ->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
}

$base_path = "../";
$page_title = "Admin Dashboard";
$current_page = "dashboard";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>

<div class="main-content">

    <?php include $base_path . "includes/topbar.php"; ?>

    <main class="dashboard-viewport" style="padding:24px;">

        <section class="hero-banner">

            <div class="hero-title">
                <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
                <p>Student Task Reminder and Assignment Tracker</p>
            </div>

            <div class="metrics-row">

                <div class="metric-card">
                    <span class="metric-label">TOTAL USERS</span>
                    <span class="metric-value">
                        <?= $stats["users"] ?>
                    </span>
                </div>

                <div class="metric-card">
                    <span class="metric-label">TOTAL ADMINS</span>
                    <span class="metric-value">
                        <?= $stats["admins"] ?>
                    </span>
                </div>

                <div class="metric-card">
                    <span class="metric-label">TOTAL STUDENTS</span>
                    <span class="metric-value">
                        <?= $stats["students"] ?>
                    </span>
                </div>

                <div class="metric-card">
                    <span class="metric-label">TOTAL TASKS</span>
                    <span class="metric-value">
                        <?= $stats["tasks"] ?>
                    </span>
                </div>

            </div>

        </section>

        <div class="content-card">

            <div class="card-top">
                <h3>Quick Actions</h3>
            </div>

            <div class="quick-actions-box">

                <a href="users/manage.php" class="action-node">
                    <span>Manage Users</span>
                </a>

                <a href="users/add.php" class="action-node">
                    <span>Add User</span>
                </a>

                <a href="../logout.php" class="action-node">
                    <span>Logout</span>
                </a>

            </div>

        </div>

        <div class="content-card" style="margin-top:20px;">

            <div class="card-top">
                <h3>System Overview</h3>
            </div>

            <p>
                This admin panel is used to manage student and administrator
                accounts within the Student Task Reminder and Assignment
                Tracker system.
            </p>

            <ul style="margin-top:15px;">
                <li>Create new student accounts</li>
                <li>Update user information</li>
                <li>Delete inactive accounts</li>
                <li>Monitor task statistics</li>
            </ul>

        </div>

    </main>

</div>

<?php include $base_path . "includes/footer.php"; ?>
