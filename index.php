<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

switch ($_SESSION["role"]) {
    case "student":
        header("Location: student/index.php");
        break;
    case "lecturer":
        header("Location: lecturer/index.php");
        break;
    case "staff":
        header("Location: staff/index.php");
        break;
    case "admin":
        header("Location: admin/dashboard.php");
        break;
    default:
        header("Location: login.php");
        break;
}
exit();
