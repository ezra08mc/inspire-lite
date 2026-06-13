<?php

session_start();

require_once '../config/db.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$totalTasks = 0;
$pendingTasks = 0;
$completedTasks = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
    FROM tasks
    WHERE user_id = $userId"
);

if($row = mysqli_fetch_assoc($result)){
    $totalTasks = $row['total'];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
    FROM tasks
    WHERE user_id = $userId
    AND status='Pending'"
);

if($row = mysqli_fetch_assoc($result)){
    $pendingTasks = $row['total'];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
    FROM tasks
    WHERE user_id = $userId
    AND status='Completed'"
);

if($row = mysqli_fetch_assoc($result)){
    $completedTasks = $row['total'];
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Student Dashboard</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body>

<div class="dashboard-wrapper">

    <aside class="sidebar">

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
                <a href="tasks/index.php"
                   class="nav-link">
                    Tasks
                </a>
            </li>

            <li class="nav-item">
                <a href="statistics.php"
                   class="nav-link">
                    Statistics
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

    <main class="main-content">

        <div class="top-navbar">

    <input
        type="text"
        class="search-bar"
        placeholder="Search tasks..."
    >

    <div class="user-profile">

        <div class="user-avatar">
            <?= strtoupper(substr($userName,0,1)); ?>
        </div>

        <span>
            <?= htmlspecialchars($userName); ?>
        </span>

    </div>

</div>
<div class="welcome-banner">

    <h2>
        Welcome Back,
        <?= htmlspecialchars($userName); ?>
    </h2>

    <p>
        Stay organized and manage your academic tasks efficiently.
    </p>

</div>

        <div class="content">

           <div class="stats-grid">

                <div class="stat-card">

    <div class="stat-info">

        <div class="stat-label">
            Total Tasks
        </div>

        <div class="stat-value">
            <?= $totalTasks ?>
        </div>

    </div>

    <div class="stat-icon icon-total">
        📋
    </div>

</div>

                <div class="stat-card">

    <div class="stat-info">

        <div class="stat-label">
            Completed
        </div>

        <div class="stat-value">
            <?= $completedTasks ?>
        </div>

    </div>

    <div class="stat-icon icon-completed">
        ✓
    </div>

</div>

<div class="section-card">

    <div class="section-header">

        <h3>
            Upcoming Tasks
        </h3>

    </div>

    <div class="task-list">

        <?php
        $taskQuery = mysqli_query(
            $conn,
            "SELECT *
             FROM tasks
             WHERE user_id = $userId
             ORDER BY deadline ASC
             LIMIT 5"
        );

        while($task = mysqli_fetch_assoc($taskQuery)):
        ?>

        <div class="task-item">

            <div>

                <strong>
                    <?= htmlspecialchars($task['title']) ?>
                </strong>

                <br>

                <small>
                    <?= htmlspecialchars($task['course']) ?>
                </small>

            </div>

            <div>
                <?= $task['deadline'] ?>
            </div>

        </div>

        <?php endwhile; ?>

    </div>

</div>

            </div>

        </div>

    </main>

</div>

</body>
</html>