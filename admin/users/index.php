<?php

session_start();

require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$adminName = $_SESSION['name'] ?? 'Admin';

$sql = "SELECT id, name, username, role FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar admin-theme">

        <div class="brand">
            INSPIRE LITE
        </div>

        <ul class="nav-menu">

            <li class="nav-item">
                <a href="../dashboard.php" class="nav-link">
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php" class="nav-link active">
                    Manage Users
                </a>
            </li>

            <li class="nav-item">
                <a href="../../logout.php" class="nav-link">
                    Logout
                </a>
            </li>

        </ul>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOP NAV -->
        <div class="top-navbar">

            <h2>Users Management</h2>

            <div class="user-profile">

                <div class="user-avatar">
                    <?= strtoupper(substr($adminName, 0, 1)); ?>
                </div>

                <span>
                    <?= htmlspecialchars($adminName); ?>
                </span>

            </div>

        </div>

        <!-- SECTION CARD -->
        <div class="section-card">

            <!-- SECTION HEADER (UPDATED) -->
            <div class="section-header">

                <h3>All Users</h3>

                <a href="add.php" class="btn-add">
                    + Add User
                </a>

            </div>

            <!-- TABLE -->
            <table class="data-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php while ($user = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td><?= $user['id'] ?></td>

                            <td><?= htmlspecialchars($user['name']) ?></td>

                            <td><?= htmlspecialchars($user['username']) ?></td>

                            <td>
                                <span class="badge <?= $user['role'] === 'admin' ? 'completed' : 'pending' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>

                            <td>

                                <a href="edit.php?id=<?= $user['id'] ?>" style="color:#f59e0b; font-weight:600;">
                                    Edit
                                </a>

                                |

                                <a href="delete.php?id=<?= $user['id'] ?>"
                                   onclick="return confirm('Delete this user?')"
                                   style="color:#ef4444; font-weight:600;">
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" style="text-align:center;">
                            No users found
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

</body>
</html>