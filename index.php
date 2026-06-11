<?php
session_start();
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "student") {
        header("Location: student/index.php");
    } elseif ($_SESSION["role"] === "lecturer") {
        header("Location: lecturer/dashboard.php");
    } elseif ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit();
}
header("Location: login.php");
exit();
