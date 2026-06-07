<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int)($_SESSION['user_id'] ?? 0);

// Since database.sql only defines active_tasks (generic by user_id), we implement
// assignment management on top of active_tasks for lecturer.
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $deadline_text = trim($_POST['deadline_text'] ?? '');
    $is_alert = isset($_POST['is_alert']) ? 1 : 0;

    if ($name === '' || $deadline_text === '') {
        $error = 'Nama tugas dan deadline harus diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO active_tasks (user_id, name, deadline_text, is_alert, is_completed) VALUES (:user_id, :name, :deadline_text, :is_alert, 0)");
            $stmt->execute([
                ':user_id' => $lecturerId,
                ':name' => $name,
                ':deadline_text' => $deadline_text,
                ':is_alert' => $is_alert,
            ]);
            $success = 'Tugas berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan tugas.';
        }
    }
}

if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    try {
        $stmt = $pdo->prepare("UPDATE active_tasks SET is_completed = 1 WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $id, ':user_id' => $lecturerId]);
        $success = 'Tugas ditandai selesai.';
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui status tugas.';
    }
}

$tasks = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks WHERE user_id = :user_id ORDER BY id ASC");
    $stmt->execute([':user_id' => $lecturerId]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Kelola Tugas</h1>
            <p>Buat, tandai selesai, dan kelola deadline.</p>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Tambah Tugas</h3>
            </div>

            <?php if ($success): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="padding: 16px;">
                <label style="display:block; margin-bottom: 10px;">
                    <span>Nama Tugas</span>
                    <input name="name" required style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" />
                </label>

                <label style="display:block; margin-bottom: 10px;">
                    <span>Deadline</span>
                    <input name="deadline_text" required style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" />
                </label>

                <label style="display:flex; align-items:center; gap:10px; margin-bottom: 12px;">
                    <input type="checkbox" name="is_alert" value="1" />
                    <span>Set sebagai alert</span>
                </label>

                <button type="submit" class="trigger-btn btn-blue" style="border:none;">Tambah</button>
                <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none; margin-left: 10px; display:inline-flex; align-items:center; justify-content:center;">Kembali</a>
            </form>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Daftar Tugas</h3>
            </div>

            <div class="tasks-vertical-stack" style="padding: 0 16px 16px;">
                <?php if (empty($tasks)): ?>
                    <div class="empty-fallback-text border-box-pad">Belum ada tugas.</div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-node <?= $task['is_completed'] ? 'done' : '' ?>">
                            <div class="task-checkbox-frame">
                                <?php if (!empty($task['is_completed'])): ?>
                                    <svg viewBox="0 0 24 24" class="check-svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="task-node-details">
                                <span class="task-node-title"><?= htmlspecialchars($task['name']) ?></span>
                                <span class="task-node-time <?= !empty($task['is_alert']) ? 'alert' : '' ?>"><?= htmlspecialchars($task['deadline_text']) ?></span>
                            </div>

                            <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
                                <?php if (empty($task['is_completed'])): ?>
                                    <a class="trigger-btn btn-blue" style="text-decoration:none; padding:8px 12px;" href="assignments.php?complete=<?= (int)$task['id'] ?>">Tandai Selesai</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>

