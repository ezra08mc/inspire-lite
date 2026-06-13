<?php
session_start();

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    }

    if ($_SESSION['role'] === 'student') {
        header("Location: student/dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>INSPIRE LITE</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

</head>

<body>

<div class="login-container">

    <!-- LEFT SIDE -->

    <div class="login-left">

        <div class="logo">
            INSPIRE LITE
        </div>

        <div class="brand-content">

            <h1>
                Manage Your Academic Tasks Efficiently
            </h1>

            <p>
                Track assignments, monitor deadlines,
                and improve productivity with Inspire Lite.
            </p>

        </div>

    </div>

    <!-- RIGHT SIDE -->

    <div class="login-right">

        <div class="login-form-box">

            <h2>
                Welcome Back
            </h2>

            <p class="subtitle">
                Sign in to continue
            </p>

            <form
                action="proces_login.php"
                method="POST"
            >

                <div class="form-group">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Enter Username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter Password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Login
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>