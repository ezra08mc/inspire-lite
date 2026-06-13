<?php

session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $sql = "INSERT INTO users (name, username, password, role)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $username, $password, $role);
    mysqli_stmt_execute($stmt);

    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="dashboard-wrapper">

<main class="main-content">

<div class="section-card">

<h2>Add User</h2>

<form method="POST">

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required>
    </div>

    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>

    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>

    <div class="form-group">
        <label>Role</label>
        <select name="role">
            <option value="student">Student</option>
            <option value="admin">Admin</option>
        </select>
    </div>

    <button class="btn-add" type="submit">Save</button>

</form>

</div>

</main>

</div>

</body>
</html>