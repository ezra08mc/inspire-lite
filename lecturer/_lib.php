<?php
// Shared lecturer helpers (minimal for now)

function lecturer_require_role(): void {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
        header('Location: ../login.php');
        exit();
    }
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

