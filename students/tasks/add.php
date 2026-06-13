<?php

session_start();

require_once '../../config/db.php';
require_once '../../includes/auth.php';

$userName = $_SESSION['name'];
if(isset($_POST['submit'])){

    $userId = $_SESSION['user_id'];

    $title = trim($_POST['title']);
    $course = trim($_POST['course']);
    $status = $_POST['status'];
    $deadline = $_POST['deadline'];

    $sql = "
    INSERT INTO tasks
    (
        user_id,
        title,
        course,
        status,
        deadline
    )
    VALUES
    (
        ?, ?, ?, ?, ?
    )
    ";

    $stmt = mysqli_prepare($conn,$sql);

    mysqli_stmt_bind_param(
        $stmt,
        "issss",
        $userId,
        $title,
        $course,
        $status,
        $deadline
    );

    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>

    <title>Add Task</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

</head>

<body>

<div class="dashboard-wrapper">

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

    <main class="main-content">

        <div class="top-navbar">

            <h2>Add New Task</h2>

            <div class="user-profile">

                <div class="user-avatar">
                    <?= strtoupper(substr($userName,0,1)); ?>
                </div>

                <?= htmlspecialchars($userName); ?>

            </div>

        </div>

        <div class="section-card">

            <form method="POST">

                <div class="form-group">

                    <label>Task Title</label>

                    <input
                        type="text"
                        name="title"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Course</label>

                    <input
                        type="text"
                        name="course"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Completed">
                            Completed
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Deadline</label>

                    <input
                        type="date"
                        name="deadline"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="submit"
                    class="btn-primary"
                >
                    Save Task
                </button>

            </form>

        </div>

    </main>

</div>

</body>
</html>