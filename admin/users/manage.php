<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$stmt = $pdo->query("
    SELECT id, username, role, created_at
    FROM users
    ORDER BY id DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-card">
    <div class="card-top">
        <h3>User Management</h3>

        <a href="add.php" class="action-link">
            + Add User
        </a>
    </div>

    <table class="custom-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user["id"] ?></td>
                    <td><?= htmlspecialchars($user["username"]) ?></td>
                    <td><?= htmlspecialchars($user["role"]) ?></td>
                    <td><?= $user["created_at"] ?></td>

                    <td>
                        <a href="edit.php?id=<?= $user['id'] ?>" class="btn-edit">
                            Edit
                        </a>

                        <a href="delete.php?id=<?= $user['id'] ?>" class="btn-delete"
                            onclick="return confirm('Delete this user?')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>