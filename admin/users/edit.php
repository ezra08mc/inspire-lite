<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");

$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $role = $_POST["role"];

    try {

        $update = $pdo->prepare("
            UPDATE users
            SET username = ?, role = ?
            WHERE id = ?
        ");

        $update->execute([
            $username,
            $role,
            $id
        ]);

        header("Location: manage.php");
        exit();

    } catch (PDOException $e) {

        if ($e->getCode() == 23000) {
            $error = "Username already exists.";
        } else {
            $error = "Failed to update user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="content-card">

    <div class="card-top">
        <h3>Edit User</h3>
        <a href="manage.php">Back</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            value="<?= htmlspecialchars($user["username"]) ?>"
            required
        >

        <label>Role</label>

        <select name="role">

            <option value="admin"
                <?= $user["role"] === "admin" ? "selected" : "" ?>>
                Admin
            </option>

            <option value="student"
                <?= $user["role"] === "student" ? "selected" : "" ?>>
                Student
            </option>

        </select>

        <button type="submit">
            Update User
        </button>

    </form>

</div>

</body>
</html>