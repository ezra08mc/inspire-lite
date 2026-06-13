<?php

session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$id = $_GET['id'];

/* OPTIONAL SAFETY: jangan hapus diri sendiri */
if ($id == $_SESSION['user_id']) {
    header("Location: index.php");
    exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: index.php");
exit();