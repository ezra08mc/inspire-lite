<?php

session_start();

require_once '../config/db.php';
require_once '../includes/auth.php';

if($_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit();
}

$adminName = $_SESSION['name'];

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM users"
    )
)['total'];

$totalStudents = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role='student'"
    )
)['total'];

$totalTasks = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM tasks"
    )
)['total'];

$completedTasks = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM tasks
         WHERE status='Completed'"
    )
)['total'];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->

    <aside class="sidebar admin-theme">

        <div class="brand">
            INSPIRE LITE
        </div>

        <ul class="nav-menu">

            <li class="nav-item">

                <a href="dashboard.php"
                   class="nav-link active">

                    Dashboard

                </a>

            </li>

            <li class="nav-item">

                <a href="users/index.php"
                   class="nav-link">

                    Manage Users

                </a>

            </li>

            <li class="nav-item">

                <a href="../logout.php"
                   class="nav-link">

                    Logout

                </a>

            </li>

        </ul>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOP NAVBAR -->

        <div class="top-navbar">

            <h2>
                Admin Dashboard
            </h2>

            <div class="user-profile">

                <div class="user-avatar">

                    <?= strtoupper(substr($adminName,0,1)); ?>

                </div>

                <span>

                    <?= htmlspecialchars($adminName); ?>

                </span>

            </div>

        </div>

        <!-- WELCOME -->

        <div class="welcome-banner">

            <h2>
                Welcome Admin
            </h2>

            <p>
                Manage users, monitor tasks, and oversee the Inspire Lite system.
            </p>

        </div>

        <!-- STATS -->

        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-info">

                    <div class="stat-label">
                        Total Users
                    </div>

                    <div class="stat-value">
                        <?= $totalUsers ?>
                    </div>

                </div>

                <div class="stat-icon icon-total">
                    👥
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-info">

                    <div class="stat-label">
                        Students
                    </div>

                    <div class="stat-value">
                        <?= $totalStudents ?>
                    </div>

                </div>

                <div class="stat-icon icon-completed">
                    🎓
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-info">

                    <div class="stat-label">
                        Total Tasks
                    </div>

                    <div class="stat-value">
                        <?= $totalTasks ?>
                    </div>

                </div>

                <div class="stat-icon icon-pending">
                    📋
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-info">

                    <div class="stat-label">
                        Completed Tasks
                    </div>

                    <div class="stat-value">
                        <?= $completedTasks ?>
                    </div>

                </div>

                <div class="stat-icon icon-overdue">
                    ✓
                </div>

            </div>

        </div>

        <!-- RECENT USERS -->

        <div class="section-card">

            <div class="section-header">

                <h3>
                    Recent Users
                </h3>

            </div>

            <table class="data-table">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>

                    </tr>

                </thead>

                <tbody>

                <?php

                $recentUsers = mysqli_query(
                    $conn,
                    "SELECT *
                     FROM users
                     ORDER BY id DESC
                     LIMIT 5"
                );

                while($user = mysqli_fetch_assoc($recentUsers)):

                ?>

                    <tr>

                        <td>
                            <?= $user['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['username']) ?>
                        </td>

                        <td>

                            <span class="badge <?= $user['role'] ?>">

                                <?= ucfirst($user['role']) ?>

                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>