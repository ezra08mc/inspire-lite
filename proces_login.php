<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    die("Username dan password wajib diisi.");
}

$sql = "SELECT id, name, password, role FROM users WHERE username = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    die("User not found");
}

if ($password !== $user['password']) {
    die("Wrong password");
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['role'] = $user['role'];

$role = strtolower($user['role']);

if ($role === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
} elseif ($role === 'student') {
    header("Location: students/dashboard.php");
    exit();
} else {
    die("Role tidak dikenali");
}
