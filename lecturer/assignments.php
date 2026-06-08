<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int)($_SESSION['user_id'] ?? 0);

$success = '';
$error = '';

$search = trim((string)($_GET['q'] ?? ''));
$courseCode = trim((string)($_GET['course_code'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));
$semester = trim((string)($_GET['semester'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$courses = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kode_mk, nama_mata_kuliah FROM jadwal ORDER BY nama_mata_kuliah ASC");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$classOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kelas FROM jadwal ORDER BY kelas ASC");
    $classOptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $classOptions = [];
}

$assignmentRows = [];
try {
    $where = " WHERE user_id = :user_id";
    $params = [':user_id' => $lecturerId];

    if ($search !== '') {
        $where .= " AND (name LIKE :q OR deadline_text LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }

    if ($status !== '') {
        if ($status === 'completed') {
            $where .= " AND is_completed = 1";
        } elseif ($status === 'active') {
            $where .= " AND is_completed = 0";
        }
    }

    if ($courseCode !== '') {
        $where .= " AND name LIKE :course_hint";
        $params[':course_hint'] = '%' . $courseCode . '%';
    }

    if ($kelas !== '') {
        $where .= " AND name LIKE :kelas_hint";
        $params[':kelas_hint'] = '%' . $kelas . '%';
    }

    if ($semester !== '') {
        $where .= " AND deadline_text LIKE :sem_hint";
        $params[':sem_hint'] = '%' . $semester . '%';
    }

    $stmt = $pdo->prepare("SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks" . $where . " ORDER BY id DESC");
    $stmt->execute($params);
    $assignmentRows = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignmentRows = [];
}

function active_tasks_create_payload(string $title, string $courseCode, string $classCode, string $dueText, string $instructions): array {
    $courseCode = trim($courseCode);
    $classCode = trim($classCode);
    $title = trim($title);

    $name = $title;
    $metaParts = [];
    if ($courseCode !== '') $metaParts[] = "Course:" . $courseCode;
    if ($classCode !== '') $metaParts[] = "Class:" . $classCode;
    if (!empty($instructions)) $metaParts[] = "Instr:" . $instructions;

    if (!empty($metaParts)) {
        $name .= " [" . implode(' | ', $metaParts) . "]";
    }

    $deadline_text = trim($dueText);
    return [$name, $deadline_text];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'create');

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $dueDate = trim((string)($_POST['due_date'] ?? ''));
    $dueTime = trim((string)($_POST['due_time'] ?? ''));
    $instructions = trim((string)($_POST['instructions'] ?? ''));
    $kodeMk = trim((string)($_POST['course_code'] ?? ''));
    $kelasVal = trim((string)($_POST['kelas'] ?? ''));
    $dueText = $dueDate;
    if ($dueTime !== '') $dueText .= ' ' . $dueTime;

    if ($title === '') {
        $error = 'Judul tugas wajib diisi.';
    } elseif ($dueText === '') {
        $error = 'Due date wajib diisi.';
    } else {
        try {
            if ($action === 'create') {
                [$name, $deadline_text] = active_tasks_create_payload($title, $kodeMk, $kelasVal, $dueText, $description !== '' ? $description : $instructions);

                $is_alert = isset($_POST['is_alert']) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO active_tasks (user_id, name, deadline_text, is_alert, is_completed) VALUES (:user_id, :name, :deadline_text, :is_alert, 0)");
                $stmt->execute([
                    ':user_id' => $lecturerId,
                    ':name' => $name,
                    ':deadline_text' => $deadline_text,
                    ':is_alert' => $is_alert,
                ]);

                $success = 'Assignment created.';
                header('Location: assignments.php');
                exit();
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM active_tasks WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':id' => $id, ':user_id' => $lecturerId]);
                    $success = 'Assignment deleted.';
                    header('Location: assignments.php');
                    exit();
                }
            }

            if ($action === 'toggle_publish') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE active_tasks SET is_alert = :is_alert WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':is_alert' => isset($_POST['publish']) ? 1 : 0, ':id' => $id, ':user_id' => $lecturerId]);
                    $success = 'Assignment updated.';
                    header('Location: assignments.php');
                    exit();
                }
            }

            if ($action === 'close') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE active_tasks SET is_completed = 1 WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':id' => $id, ':user_id' => $lecturerId]);
                    $success = 'Assignment closed.';
                    header('Location: assignments.php');
                    exit();
                }
            }

            if ($action === 'edit') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    [$name, $deadline_text] = active_tasks_create_payload($title, $kodeMk, $kelasVal, $dueText, $description !== '' ? $description : $instructions);
                    $stmt = $pdo->prepare("UPDATE active_tasks SET name = :name, deadline_text = :deadline_text, is_alert = :is_alert WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':name' => $name, ':deadline_text' => $deadline_text, ':is_alert' => isset($_POST['is_alert']) ? 1 : 0, ':id' => $id, ':user_id' => $lecturerId]);
                    $success = 'Assignment updated.';
                    header('Location: assignments.php');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Failed to process assignment.';
        }
    }
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Assignments</h1>
            <p>Kelola tugas, deadline, dan status publikasi.</p>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Create Assignment</h3>
            </div>

            <?php if ($success !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="padding: 16px;">
                <input type="hidden" name="action" value="create" />

                <label style="display:block; margin-bottom: 10px;">
                    <span>Assignment Title</span>
                    <input name="title" required style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" value="" />
                </label>

                <label style="display:block; margin-bottom: 10px;">
                    <span>Assignment Description</span>
                    <input name="description" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" value="" />
                </label>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Course</span>
                        <select name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Pilih</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['kode_mk']) ?>"><?= htmlspecialchars($c['nama_mata_kuliah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="display:block;">
                        <span>Class</span>
                        <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Pilih</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars((string)$cl['kelas']) ?>"><?= htmlspecialchars((string)$cl['kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <label style="display:block;">
                        <span>Due Date</span>
                        <input type="date" name="due_date" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" required value="" />
                    </label>
                    <label style="display:block;">
                        <span>Due Time</span>
                        <input type="time" name="due_time" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" value="" />
                    </label>
                </div>

                <label style="display:block; margin-top: 12px;">
                    <span>Assignment Instructions</span>
                    <input name="instructions" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" value="" />
                </label>

                <label style="display:flex; align-items:center; gap:10px; margin-top: 12px;">
                    <input type="checkbox" name="is_alert" value="1" />
                    <span>Mark as alert</span>
                </label>

                <label style="display:block; margin-top: 12px;">
                    <span>Attachment Upload</span>
                    <input type="file" name="attachment" accept="*/*" style="width:100%; padding:10px; border-radius:10px; border:1px dashed var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;" />
                </label>

                <div style="display:flex; gap: 10px; margin-top: 16px; flex-wrap:wrap;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Create</button>
                    <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Back</a>
                </div>
            </form>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Assignment List</h3>
            </div>

            <div style="padding: 0 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <select name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['kode_mk']) ?>" <?= $courseCode === (string)$c['kode_mk'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nama_mata_kuliah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Classes</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars((string)$cl['kelas']) ?>" <?= $kelas === (string)$cl['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$cl['kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <select name="semester" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Semester</option>
                            <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
                            <option value="3" <?= $semester === '3' ? 'selected' : '' ?>>Semester 3</option>
                            <option value="4" <?= $semester === '4' ? 'selected' : '' ?>>Semester 4</option>
                            <option value="5" <?= $semester === '5' ? 'selected' : '' ?>>Semester 5</option>
                            <option value="6" <?= $semester === '6' ? 'selected' : '' ?>>Semester 6</option>
                        </select>
                        <select name="status" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Status</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="trigger-btn btn-blue" style="border:none; width: 100%;">Apply Filters</button>
                </form>
            </div>

            <div class="tasks-vertical-stack" style="padding: 0 16px 16px;">
                <?php if (empty($assignmentRows)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada assignment.</div>
                <?php else: ?>
                    <?php foreach ($assignmentRows as $task): ?>
                        <div class="task-node <?= !empty($task['is_completed']) ? 'done' : '' ?>">
                            <div class="task-checkbox-frame">
                                <?php if (!empty($task['is_completed'])): ?>
                                    <svg viewBox="0 0 24 24" class="check-svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="task-node-details">
                                <span class="task-node-title"><?= htmlspecialchars($task['name']) ?></span>
                                <span class="task-node-time <?= !empty($task['is_alert']) ? 'alert' : '' ?>"><?= htmlspecialchars($task['deadline_text']) ?></span>
                            </div>

                            <div style="margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                <?php if (empty($task['is_completed'])): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="close" />
                                        <input type="hidden" name="id" value="<?= (int)$task['id'] ?>" />
                                        <button type="submit" class="trigger-btn btn-blue" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer;">Close</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_publish" />
                                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>" />
                                    <button type="submit" name="publish" value="1" class="trigger-btn" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer; background-color: var(--primary); color:white;">Publish</button>
                                </form>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>" />
                                    <button type="submit" class="trigger-btn btn-red" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer;">Delete</button>
                                </form>
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


