<?php

session_start();

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

$sql = "SELECT * FROM users WHERE username = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User not found");
}

if ($password !== $user['password']) {
    die("Wrong password");
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['role'] = $user['role'];
if ($user['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

if ($user["role"] === "student") {
    header("Location: students/dashboard.php"); // Sesuaikan dengan nama foldernya
    exit();
}