<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$message = "";
$error = "";
$edit_user = null;

function go_back(): void
{
    header("Location: manage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role = $_POST["role"] ?? "student";

    if (!in_array($role, ["admin", "staff", "lecturer", "student"], true)) {
        $role = "student";
    }

    try {
        if ($action === "create") {
            if ($username === "" || $password === "") {
                $error = "Username and password are required.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
                $stmt->execute([
                    ":username" => $username,
                    ":password" => $hashed,
                    ":role" => $role,
                ]);
                if ($role === "student") {
                    $new_user_id = (int)$pdo->lastInsertId();
                    $nim = trim($_POST["nim"] ?? "");
                    $first_name = trim($_POST["first_name"] ?? "");
                    $last_name = trim($_POST["last_name"] ?? "");
                    $birth_date = trim($_POST["birth_date"] ?? date("Y-m-d"));
                    $study_program = trim($_POST["study_program"] ?? "Information Systems");
                    $cohort = (int)($_POST["cohort"] ?? date("Y"));
                    if ($nim !== "" && $first_name !== "" && $last_name !== "") {
                        $stmt = $pdo->prepare("INSERT INTO students (nim, user_id, first_name, last_name, birth_date, study_program, cohort) VALUES (:nim, :user_id, :first_name, :last_name, :birth_date, :study_program, :cohort)");
                        $stmt->execute([
                            ":nim" => $nim,
                            ":user_id" => $new_user_id,
                            ":first_name" => $first_name,
                            ":last_name" => $last_name,
                            ":birth_date" => $birth_date,
                            ":study_program" => $study_program,
                            ":cohort" => $cohort,
                        ]);
                    }
                }
                go_back();
            }
        }

        if ($action === "update") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id <= 0 || $username === "") {
                $error = "Please complete the account form.";
            } else {
                if ($password !== "") {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id");
                    $stmt->execute([
                        ":username" => $username,
                        ":password" => password_hash($password, PASSWORD_DEFAULT),
                        ":role" => $role,
                        ":id" => $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
                    $stmt->execute([
                        ":username" => $username,
                        ":role" => $role,
                        ":id" => $id,
                    ]);
                }
                go_back();
            }
        }

        if ($action === "delete") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([":id" => $id]);
            }
            go_back();
        }
    } catch (PDOException $e) {
        $error = "Account manager database error.";
        error_log($e->getMessage());
    }
}

if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => (int)$_GET["edit"]]);
    $edit_user = $stmt->fetch() ?: null;
}

$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Unable to load users.";
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Student Task Reminder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="main-content" style="margin-left:0;">
        <header class="navbar">
            <div class="page-title-header"><h1>Manage Users</h1></div>
            <div class="user-panel">
                <a href="../dashboard.php" class="action-link">Back to dashboard</a>
            </div>
        </header>

        <main class="content-body">
            <?php if ($error !== ""): ?>
                <div class="error-banner" style="margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="task-grid">
                <div class="content-card">
                    <div class="card-top">
                        <h3><?= $edit_user ? "Edit Account" : "Add Account" ?></h3>
                        <?php if ($edit_user): ?><a href="manage.php" class="action-link">Cancel</a><?php endif; ?>
                    </div>
                    <form method="POST" class="task-form">
                        <input type="hidden" name="action" value="<?= $edit_user ? "update" : "create" ?>">
                        <?php if ($edit_user): ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)$edit_user["id"]) ?>"><?php endif; ?>
                        <label><span>Username</span><input type="text" name="username" value="<?= htmlspecialchars($edit_user["username"] ?? "") ?>" required></label>
                        <label><span>Password <?= $edit_user ? "(leave blank to keep current)" : "" ?></span><input type="password" name="password" <?= $edit_user ? "" : "required" ?>></label>
                        <label>
                            <span>Role</span>
                            <select name="role">
                                <?php $selected_role = $edit_user["role"] ?? "student"; ?>
                                <?php foreach (["admin", "lecturer", "staff", "student"] as $role_option): ?>
                                    <option value="<?= $role_option ?>" <?= $selected_role === $role_option ? "selected" : "" ?>><?= ucfirst($role_option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label><span>NIM</span><input type="text" name="nim" placeholder="Only for student accounts"></label>
                        <label><span>First Name</span><input type="text" name="first_name" placeholder="Only for student accounts"></label>
                        <label><span>Last Name</span><input type="text" name="last_name" placeholder="Only for student accounts"></label>
                        <label><span>Birth Date</span><input type="date" name="birth_date"></label>
                        <label><span>Study Program</span><input type="text" name="study_program" placeholder="Only for student accounts"></label>
                        <label><span>Cohort</span><input type="number" name="cohort" min="2000" max="2100" placeholder="Only for student accounts"></label>

                        <button type="submit" class="btn-submit-task"><?= $edit_user ? "Update Account" : "Add Account" ?></button>
                    </form>
                </div>

                <div class="content-card">
                    <div class="card-top"><h3>User List</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4" class="text-center">No users found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($user["username"]) ?></strong></td>
                                            <td><?= htmlspecialchars(ucfirst($user["role"])) ?></td>
                                            <td><?= htmlspecialchars((new DateTime($user["created_at"]))->format("d M Y H:i")) ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="manage.php?edit=<?= htmlspecialchars((string)$user["id"]) ?>" class="btn-table-action">Edit</a>
                                                    <form method="POST" onsubmit="return confirm('Delete this user?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$user["id"]) ?>">
                                                        <button type="submit" class="btn-table-action danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
