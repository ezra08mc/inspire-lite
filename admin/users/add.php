<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = $_POST["role"];

    if (empty($username) || empty($password) || empty($role)) {

        $error = "All fields are required.";

    } else {

        try {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    username,
                    password,
                    role
                )
                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $username,
                $hashedPassword,
                $role
            ]);

            $success = "User created successfully.";

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "Username already exists.";

            } else {

                $error = "Failed to create user.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="content-card">

    <div class="card-top">
        <h3>Add New User</h3>

        <a href="manage.php" class="action-link">
            Back to User Manager
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-message">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="user-form">

        <div class="form-group">
            <label>Username</label>

            <input
                type="text"
                name="username"
                required
            >
        </div>

        <div class="form-group">
            <label>Password</label>

            <input
                type="password"
                name="password"
                required
            >
        </div>

        <div class="form-group">
            <label>Role</label>

            <select name="role" required>
                <option value="student">
                    Student
                </option>

                <option value="admin">
                    Admin
                </option>
            </select>
        </div>

        <button
            type="submit"
            class="btn-primary">
            Create User
        </button>

    </form>

</div>

</body>
</html>