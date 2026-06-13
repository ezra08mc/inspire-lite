<?php

session_start();

require_once '../config/db.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];/*
|--------------------------------------------------------------------------
| Total Tasks
|--------------------------------------------------------------------------
*/

$totalQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM tasks
     WHERE user_id = $userId"
);

$totalTasks = mysqli_fetch_assoc(
    $totalQuery
)['total'];

/*
|--------------------------------------------------------------------------
| Completed Tasks
|--------------------------------------------------------------------------
*/

$completedQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM tasks
     WHERE user_id = $userId
     AND status='Completed'"
);

$completedTasks = mysqli_fetch_assoc(
    $completedQuery
)['total'];

/*
|--------------------------------------------------------------------------
| Pending Tasks
|--------------------------------------------------------------------------
*/

$pendingQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM tasks
     WHERE user_id = $userId
     AND status='Pending'"
);

$pendingTasks = mysqli_fetch_assoc(
    $pendingQuery
)['total'];

/*
|--------------------------------------------------------------------------
| Completion Rate
|--------------------------------------------------------------------------
*/

$completionRate = 0;

if($totalTasks > 0){

    $completionRate = round(
        ($completedTasks / $totalTasks) * 100
    );
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Statistics</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="brand">
            INSPIRE LITE
        </div>

        <ul class="nav-menu">

            <li class="nav-item">
                <a href="dashboard.php"
                   class="nav-link">
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
                   class="nav-link active">
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

    <!-- CONTENT -->

    <main class="main-content">

        <div class="top-navbar">

            <h2>
                Statistics
            </h2>

            <div class="user-profile">

                <div class="user-avatar">
                    <?= strtoupper(substr($userName,0,1)); ?>
                </div>

                <?= htmlspecialchars($userName); ?>

            </div>

        </div>

        <!-- STATS -->

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

            <div class="stat-card">

                <div class="stat-info">

                    <div class="stat-label">
                        Pending
                    </div>

                    <div class="stat-value">
                        <?= $pendingTasks ?>
                    </div>

                </div>

                <div class="stat-icon icon-pending">
                    ⏳
                </div>

            </div>

        </div>

        <br>

        <!-- CHARTS -->

        <div class="charts-grid">

            <div class="chart-container">

                <h3>
                    Task Distribution
                </h3>

                <canvas id="taskChart"></canvas>

            </div>

            <div class="chart-container">

                <h3>
                    Completion Rate
                </h3>

                <div class="completion-box">

                    <div class="completion-number">
                        <?= $completionRate ?>%
                    </div>

                    <div class="progress">

                        <div
                            class="progress-fill"
                            style="width:<?= $completionRate ?>%"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<script>

const ctx =
document.getElementById('taskChart');

new Chart(ctx, {

    type: 'doughnut',

    data: {

        labels: [
            'Completed',
            'Pending'
        ],

        datasets: [{

            data: [
                <?= $completedTasks ?>,
                <?= $pendingTasks ?>
            ],

            backgroundColor: [
                '#10b981',
                '#f59e0b'
            ],

            borderWidth: 0

        }]
    },

    options: {

        responsive:true,

        plugins: {

            legend: {
                position:'bottom'
            }

        }

    }

});

</script>

</body>
</html>