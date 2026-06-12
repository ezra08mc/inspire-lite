<?php
session_start();
require_once "config/db.php";

if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (!empty($username) && !empty($password)) {

        try {

            $stmt = $pdo->prepare("
                SELECT * 
                FROM users 
                WHERE username = :username 
                LIMIT 1
            ");

            $stmt->execute([
                ":username" => $username
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] === "admin") {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: student/dashboard.php");
                }

                exit();

            } else {
                $error_message = "Invalid username or password.";
            }

        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }

    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Tracker System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="./assets/css/style.css" rel="stylesheet">

    <script src="./assets/js/main.js"></script>
</head>

<body>

<header class="top-navbar">
    <a href="#" class="nav-link">
        Student Task Tracker
    </a>
</header>

<div class="main-wrapper">

    <div class="login-card">

        <div class="brand-container">
            <h1>Inspire-Lite</h1>
            <p>Manage Your Academic Tasks Easily</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner">
                ⚠ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="form-group">
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Username"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-signin">
                LOGIN
            </button>

        </form>

        <br>

        <a href="members.php" class="forgot-password-link">
            View Team Members
        </a>

    </div>

</div>

<footer class="form-footer">
    <p>© 2026 Inspire-Lite</p>
</footer>

</body>
</html>
