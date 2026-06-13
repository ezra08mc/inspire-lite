<?php

session_start();

require_once '../../config/db.php';
require_once '../../includes/auth.php';

$userName = $_SESSION['name'];
$userId = $_SESSION['user_id'];

$id = (int)$_GET['id'];

$sql = "
SELECT *
FROM tasks
WHERE id = ?
AND user_id = ?
";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id,
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$task = mysqli_fetch_assoc($result);

if(!$task){
    die("Task not found");
}

if(isset($_POST['submit'])){

    $title = trim($_POST['title']);
    $course = trim($_POST['course']);
    $status = $_POST['status'];
    $deadline = $_POST['deadline'];

    $updateSql = "
    UPDATE tasks
    SET
        title=?,
        course=?,
        status=?,
        deadline=?
    WHERE id=?
    AND user_id=?
    ";

    $updateStmt = mysqli_prepare(
        $conn,
        $updateSql
    );

    mysqli_stmt_bind_param(
        $updateStmt,
        "ssssii",
        $title,
        $course,
        $status,
        $deadline,
        $id,
        $userId
    );

    mysqli_stmt_execute($updateStmt);

    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>

    <title>Edit Task</title>

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

            <h2>Edit Task</h2>

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
                        value="<?= htmlspecialchars($task['title']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Course</label>

                    <input
                        type="text"
                        name="course"
                        value="<?= htmlspecialchars($task['course']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option
                            value="Pending"
                            <?= $task['status']=='Pending' ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Completed"
                            <?= $task['status']=='Completed' ? 'selected' : '' ?>
                        >
                            Completed
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Deadline</label>

                    <input
                        type="date"
                        name="deadline"
                        value="<?= $task['deadline'] ?>"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="submit"
                    class="btn-primary"
                >
                    Update Task
                </button>

            </form>

        </div>

    </main>

</div>

</body>
</html>