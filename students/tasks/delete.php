<?php

session_start();

require_once '../../config/db.php';
require_once '../../includes/auth.php';

$userId = $_SESSION['user_id'];

$id = (int)$_GET['id'];

$sql = "
DELETE FROM tasks
WHERE id = ?
AND user_id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id,
    $userId
);

mysqli_stmt_execute($stmt);

header("Location: index.php");
exit();