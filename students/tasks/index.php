<?php

session_start();

require_once '../../config/db.php';
require_once '../../includes/auth.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];
$sql = "
SELECT *
FROM tasks
WHERE user_id = ?
ORDER BY deadline ASC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Task Management</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

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
                <a href="../dashboard.php"
                   class="nav-link">
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php"
                   class="nav-link active">
                    Tasks
                </a>
            </li>

            <li class="nav-item">
                <a href="../statistics.php"
                   class="nav-link">
                    Statistics
                </a>
            </li>

            <li class="nav-item">
                <a href="../../logout.php"
                   class="nav-link">
                    Logout
                </a>
            </li>

        </ul>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <div class="top-navbar">

            <input
                type="text"
                class="search-bar"
                placeholder="Search task..."
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

        <div class="section-card">

            <div class="section-header">

                <h2>
                    Task Management
                </h2>

                <a href="add.php"
                   class="btn-primary"
                   style="
                   width:auto;
                   padding:10px 20px;
                   text-decoration:none;
                   ">
                    + Add Task
                </a>

            </div>

            <table class="data-table">

                <thead>

                    <tr>

                        <th>Title</th>

                        <th>Course</th>

                        <th>Status</th>

                        <th>Deadline</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php while($task = mysqli_fetch_assoc($result)): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($task['title']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($task['course']) ?>
                        </td>

                        <td>

<?php if($task['status']=="Completed"): ?>

<span class="badge completed">
    Completed
</span>

<?php else: ?>

<span class="badge pending">
    Pending
</span>

<?php endif; ?>

                        </td>

                        <td>
                            <?= $task['deadline'] ?>
                        </td>

                        <td>

                            <a
                                href="edit.php?id=<?= $task['id'] ?>"
                                style="
                                color:#f59e0b;
                                text-decoration:none;
                                font-weight:600;
                                "
                            >
                                Edit
                            </a>

                            |

                            <a
                                href="delete.php?id=<?= $task['id'] ?>"
                                onclick="return confirm('Delete this task?')"
                                style="
                                color:#ef4444;
                                text-decoration:none;
                                font-weight:600;
                                "
                            >
                                Delete
                            </a>

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