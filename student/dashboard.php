<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

$total_tasks = 0;
$completed_tasks = 0;
$pending_tasks = 0;
$nearest_deadline = "-";
$tasks = [];

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM tasks
        WHERE user_id = ?
        ORDER BY deadline ASC
    ");

    $stmt->execute([$user_id]);

    $tasks = $stmt->fetchAll();

    $total_tasks = count($tasks);

    foreach ($tasks as $task) {

        if ($task['status'] == 'Completed') {
            $completed_tasks++;
        }

        if ($task['status'] == 'Pending') {
            $pending_tasks++;
        }
    }

    $stmt = $pdo->prepare("
        SELECT deadline
        FROM tasks
        WHERE user_id = ?
        AND status='Pending'
        ORDER BY deadline ASC
        LIMIT 1
    ");

    $stmt->execute([$user_id]);

    $deadline = $stmt->fetchColumn();

    if ($deadline) {
        $nearest_deadline = date('d M Y', strtotime($deadline));
    }

} catch(PDOException $e){
    die($e->getMessage());
}

$initials = strtoupper(substr($username,0,2));
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Plus Jakarta Sans',sans-serif;
}

body{
background:#f5f7fb;
display:flex;
min-height:100vh;
}

.sidebar{
width:250px;
background:#1e293b;
color:white;
padding:20px;
}

.logo{
font-size:22px;
font-weight:700;
margin-bottom:40px;
}

.sidebar a{
display:block;
color:white;
text-decoration:none;
padding:12px;
border-radius:8px;
margin-bottom:10px;
}

.sidebar a:hover{
background:#334155;
}

.main{
flex:1;
padding:30px;
}

.header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.avatar{
width:45px;
height:45px;
background:#2563eb;
border-radius:50%;
display:flex;
align-items:center;
justify-content:center;
color:white;
font-weight:bold;
}

.cards{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin-bottom:30px;
}

.card{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.card h3{
font-size:14px;
color:#666;
margin-bottom:10px;
}

.card p{
font-size:28px;
font-weight:700;
}

.table-box{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 2px 10px rgba(0,0,0,.05);
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
padding:12px;
text-align:left;
border-bottom:1px solid #ddd;
}

.status{
padding:5px 10px;
border-radius:20px;
font-size:12px;
font-weight:600;
}

.completed{
background:#dcfce7;
color:#166534;
}

.pending{
background:#fee2e2;
color:#991b1b;
}

.btn{
background:#2563eb;
color:white;
padding:10px 15px;
text-decoration:none;
border-radius:8px;
}

@media(max-width:900px){

.cards{
grid-template-columns:1fr 1fr;
}

.sidebar{
display:none;
}

}

</style>

</head>
<body>

<div class="sidebar">

<div class="logo">
INSPIRE LITE
</div>

<a href="dashboard.php">Dashboard</a>
<a href="tasks.php">My Tasks</a>
<a href="add_task.php">Add Task</a>
<a href="../logout.php">Logout</a>

</div>

<div class="main">

<div class="header">

<div>
<h1>Dashboard</h1>
<p>Welcome back, <?= htmlspecialchars($username) ?></p>
</div>

<div class="avatar">
<?= $initials ?>
</div>

</div>

<div class="cards">

<div class="card">
<h3>Total Tasks</h3>
<p><?= $total_tasks ?></p>
</div>

<div class="card">
<h3>Completed</h3>
<p><?= $completed_tasks ?></p>
</div>

<div class="card">
<h3>Pending</h3>
<p><?= $pending_tasks ?></p>
</div>

<div class="card">
<h3>Nearest Deadline</h3>
<p style="font-size:18px;">
<?= $nearest_deadline ?>
</p>
</div>

</div>

<div class="table-box">

<div style="display:flex;justify-content:space-between;margin-bottom:20px;">
<h2>Recent Tasks</h2>

<a href="add_task.php" class="btn">
+ Add Task
</a>
</div>

<table>

<thead>
<tr>
<th>Title</th>
<th>Course</th>
<th>Deadline</th>
<th>Status</th>
</tr>
</thead>

<tbody>

<?php if(empty($tasks)): ?>

<tr>
<td colspan="4">
No tasks available.
</td>
</tr>

<?php else: ?>

<?php foreach(array_slice($tasks,0,5) as $task): ?>

<tr>

<td>
<?= htmlspecialchars($task['title']) ?>
</td>

<td>
<?= htmlspecialchars($task['course']) ?>
</td>

<td>
<?= date('d M Y', strtotime($task['deadline'])) ?>
</td>

<td>

<span class="status <?= strtolower($task['status']) ?>">

<?= htmlspecialchars($task['status']) ?>

</span>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</body>
</html>
