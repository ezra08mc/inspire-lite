<?php

session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$id = $_GET['id'];

/* GET USER */
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    /* password optional */
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "UPDATE users SET name=?, username=?, password=?, role=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $username, $password, $role, $id);
    } else {
        $sql = "UPDATE users SET name=?, username=?, role=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $name, $username, $role, $id);
    }

    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="dashboard-wrapper">

<main class="main-content">

<div class="section-card">

<h2>Edit User</h2>

<form method="POST">

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">
    </div>

    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
    </div>

    <div class="form-group">
        <label>Password (optional)</label>
        <input type="password" name="password">
    </div>

    <div class="form-group">
        <label>Role</label>
        <select name="role">
            <option value="student" <?= $user['role']=='student'?'selected':'' ?>>Student</option>
            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
        </select>
    </div>

    <button class="btn-add" type="submit">Update</button>

</form>

</div>

</main>

</div>

</body>
</html>