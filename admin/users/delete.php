<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$id = (int)($_GET["id"] ?? 0);

$currentAdmin = $_SESSION["username"] ?? "";

$stmt = $pdo->prepare("
    SELECT username
    FROM users
    WHERE id = ?
");

$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: manage.php");
    exit();
}

if ($user["username"] === $currentAdmin) {
    die("You cannot delete your own account.");
}

$delete = $pdo->prepare("
    DELETE FROM users
    WHERE id = ?
");

$delete->execute([$id]);

header("Location: manage.php");
exit();