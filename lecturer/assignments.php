<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int) ($_SESSION["user_id"] ?? 0);
$nip = "";
try {
    $stmt = $pdo->prepare(
        "SELECT nip FROM lecturers WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $lecturerId]);
    $lec = $stmt->fetch();
    if ($lec) {
        $nip = $lec["nip"];
    }
} catch (PDOException $e) {
}

$success = "";
$error = "";

$search = trim((string) ($_GET["q"] ?? ""));
$courseCode = trim((string) ($_GET["course_code"] ?? ""));
$kelas = trim((string) ($_GET["kelas"] ?? ""));
$semester = trim((string) ($_GET["semester"] ?? ""));
$status = trim((string) ($_GET["status"] ?? ""));

$courses = [];
if ($nip) {
    try {
        $stmt = $pdo->prepare(
            "SELECT c.course_code, s.course_name FROM lecturer_courses c JOIN subjects s ON s.course_code = c.course_code WHERE c.nip = ?",
        );
        $stmt->execute([$nip]);
        $courses = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

$classOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kelas FROM jadwal ORDER BY kelas ASC");
    $classOptions = $stmt->fetchAll();
} catch (PDOException $e) {
}

$assignmentRows = [];
try {
    $where = " WHERE user_id = :user_id";
    $params = [":user_id" => $lecturerId];

    if ($search !== "") {
        $where .= " AND (name LIKE :q OR deadline_text LIKE :q)";
        $params[":q"] = "%" . $search . "%";
    }

    if ($status !== "") {
        if ($status === "completed") {
            $where .= " AND is_completed = 1";
        } elseif ($status === "active") {
            $where .= " AND is_completed = 0";
        }
    }

    if ($courseCode !== "") {
        $where .= " AND name LIKE :course_hint";
        $params[":course_hint"] = "%" . $courseCode . "%";
    }

    if ($kelas !== "") {
        $where .= " AND name LIKE :kelas_hint";
        $params[":kelas_hint"] = "%" . $kelas . "%";
    }

    if ($semester !== "") {
        $where .= " AND deadline_text LIKE :sem_hint";
        $params[":sem_hint"] = "%" . $semester . "%";
    }

    $stmt = $pdo->prepare(
        "SELECT id, name, deadline_text, is_alert, is_completed FROM active_tasks" .
            $where .
            " ORDER BY id DESC",
    );
    $stmt->execute($params);
    $assignmentRows = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignmentRows = [];
}

function active_tasks_create_payload(
    string $title,
    string $courseCode,
    string $classCode,
    string $dueText,
    string $instructions,
): array {
    $courseCode = trim($courseCode);
    $classCode = trim($classCode);
    $title = trim($title);

    $name = $title;
    $metaParts = [];
    if ($courseCode !== "") {
        $metaParts[] = "Course:" . $courseCode;
    }
    if ($classCode !== "") {
        $metaParts[] = "Class:" . $classCode;
    }
    if (!empty($instructions)) {
        $metaParts[] = "Instr:" . $instructions;
    }

    if (!empty($metaParts)) {
        $name .= " [" . implode(" | ", $metaParts) . "]";
    }

    $deadline_text = trim($dueText);
    return [$name, $deadline_text];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = (string) ($_POST["action"] ?? "create");

    if ($action === "create") {
        $title = trim((string) ($_POST["title"] ?? ($_POST["name"] ?? "")));
        $course_code = $_POST["course_code"] ?? "";
        $kelasVal = $_POST["kelas"] ?? "";
        $deadline_text = trim((string) ($_POST["deadline_text"] ?? ""));
        $description = trim(
            (string) ($_POST["description"] ?? ($_POST["instructions"] ?? "")),
        );

        // Handle due date/time if provided separately (from HEAD)
        $dueDate = trim((string) ($_POST["due_date"] ?? ""));
        $dueTime = trim((string) ($_POST["due_time"] ?? ""));
        if ($deadline_text === "" && $dueDate !== "") {
            $deadline_text = $dueDate;
            if ($dueTime !== "") {
                $deadline_text .= " " . $dueTime;
            }
        }

        if ($title === "" || $deadline_text === "" || $course_code === "") {
            $error = "Nama tugas, deadline, dan mata kuliah wajib diisi.";
        } else {
            try {
                $pdo->beginTransaction();

                [$name, $deadline_text_final] = active_tasks_create_payload(
                    $title,
                    $course_code,
                    $kelasVal,
                    $deadline_text,
                    $description,
                );
                $is_alert = isset($_POST["is_alert"]) ? 1 : 0;

                // 1. Get all students enrolled in this course
                $stmt = $pdo->prepare(
                    "SELECT s.user_id FROM students s JOIN enrollments e ON s.nim = e.nim WHERE e.course_code = ?",
                );
                $stmt->execute([$course_code]);
                $students = $stmt->fetchAll();

                $stmt_insert = $pdo->prepare(
                    "INSERT INTO active_tasks (user_id, name, deadline_text, is_alert, is_completed) VALUES (?, ?, ?, ?, 0)",
                );

                // 2. Distribute to students
                foreach ($students as $st) {
                    $stmt_insert->execute([
                        $st["user_id"],
                        $name,
                        $deadline_text_final,
                        $is_alert,
                    ]);
                }

                // 3. Keep a record for the lecturer
                $stmt_insert->execute([
                    $lecturerId,
                    $name,
                    $deadline_text_final,
                    $is_alert,
                ]);

                $pdo->commit();
                $success =
                    "Tugas berhasil didistribusikan ke " .
                    count($students) .
                    " mahasiswa.";
                header(
                    "Location: assignments.php?success=" . urlencode($success),
                );
                exit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Gagal menambahkan tugas: " . $e->getMessage();
            }
        }
    }

    if ($action === "delete") {
        $id = (int) ($_POST["id"] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM active_tasks WHERE id = :id AND user_id = :user_id",
                );
                $stmt->execute([":id" => $id, ":user_id" => $lecturerId]);
                $success = "Assignment deleted.";
                header("Location: assignments.php");
                exit();
            } catch (PDOException $e) {
                $error = "Failed to delete assignment.";
            }
        }
    }

    if ($action === "toggle_publish") {
        $id = (int) ($_POST["id"] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE active_tasks SET is_alert = :is_alert WHERE id = :id AND user_id = :user_id",
                );
                $stmt->execute([
                    ":is_alert" => isset($_POST["publish"]) ? 1 : 0,
                    ":id" => $id,
                    ":user_id" => $lecturerId,
                ]);
                $success = "Assignment updated.";
                header("Location: assignments.php");
                exit();
            } catch (PDOException $e) {
                $error = "Failed to update assignment.";
            }
        }
    }

    if ($action === "close") {
        $id = (int) ($_POST["id"] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE active_tasks SET is_completed = 1 WHERE id = :id AND user_id = :user_id",
                );
                $stmt->execute([":id" => $id, ":user_id" => $lecturerId]);
                $success = "Assignment closed.";
                header("Location: assignments.php");
                exit();
            } catch (PDOException $e) {
                $error = "Failed to close assignment.";
            }
        }
    }
}

if (isset($_GET["success"])) {
    $success = $_GET["success"];
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Kelola Tugas</h1>
            <p>Berikan tugas kepada mahasiswa yang mengontrak mata kuliah Anda.</p>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Tambah Tugas Baru</h3>
            </div>

            <?php if ($success !== ""): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px; background: #dcfce7; font-weight: bold;"><?= htmlspecialchars(
                    $success,
                ) ?></div>
            <?php endif; ?>
            <?php if ($error !== ""): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= htmlspecialchars(
                    $error,
                ) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="padding: 16px;">
                <input type="hidden" name="action" value="create" />

                <label style="display:block; margin-bottom: 10px;">
                    <span>Nama Tugas / Judul</span>
                    <input name="title" required style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" placeholder="Contoh: Makalah Pertemuan 3" />
                </label>

                <label style="display:block; margin-bottom: 10px;">
                    <span>Mata Kuliah</span>
                    <select required name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                        <option value="">-- Pilih MK --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= htmlspecialchars(
                                $c["course_code"],
                            ) ?>"><?= htmlspecialchars(
    $c["course_code"] . " - " . $c["course_name"],
) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                    <label style="display:block;">
                        <span>Kelas (Opsional)</span>
                        <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Pilih</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars(
                                    (string) $cl["kelas"],
                                ) ?>"><?= htmlspecialchars(
    (string) $cl["kelas"],
) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span>Deadline (Text)</span>
                        <input name="deadline_text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" placeholder="15 Juni 2026 23:59" />
                    </label>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Due Date</span>
                        <input type="date" name="due_date" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                    <label style="display:block;">
                        <span>Due Time</span>
                        <input type="time" name="due_time" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <label style="display:block; margin-top: 12px;">
                    <span>Instruksi / Deskripsi</span>
                    <input name="instructions" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" type="text" placeholder="Tambahkan instruksi tambahan jika ada" />
                </label>

                <label style="display:flex; align-items:center; gap:10px; margin-top: 12px;">
                    <input type="checkbox" name="is_alert" value="1" />
                    <span>Set sebagai peringatan (alert)</span>
                </label>

                <div style="display:flex; gap: 10px; margin-top: 16px; flex-wrap:wrap;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Berikan Tugas</button>
                    <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Kembali</a>
                </div>
            </form>
        </div>

        <div class="content-card" style="flex: 1.2;">
            <div class="card-top">
                <h3>Daftar Tugas Yang Diberikan</h3>
            </div>

            <div style="padding: 0 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <input type="text" name="q" value="<?= htmlspecialchars(
                        $search,
                    ) ?>" placeholder="Cari Tugas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <select name="course_code" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Semua MK</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars(
                                    $c["course_code"],
                                ) ?>" <?= $courseCode ===
(string) $c["course_code"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($c["course_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="kelas" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classOptions as $cl): ?>
                                <option value="<?= htmlspecialchars(
                                    (string) $cl["kelas"],
                                ) ?>" <?= $kelas === (string) $cl["kelas"]
    ? "selected"
    : "" ?>><?= htmlspecialchars((string) $cl["kelas"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="trigger-btn btn-blue" style="border:none; width: 100%;">Terapkan Filter</button>
                </form>
            </div>

            <div class="tasks-vertical-stack" style="padding: 16px;">
                <?php if (empty($assignmentRows)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada assignment ditemukan.</div>
                <?php else: ?>
                    <?php foreach ($assignmentRows as $task): ?>
                        <div class="task-node <?= !empty($task["is_completed"])
                            ? "done"
                            : "" ?>">
                            <div class="task-checkbox-frame">
                                <?php if (!empty($task["is_completed"])): ?>
                                    <svg viewBox="0 0 24 24" class="check-svg"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="task-node-details">
                                <span class="task-node-title"><?= htmlspecialchars(
                                    $task["name"],
                                ) ?></span>
                                <span class="task-node-time <?= !empty(
                                    $task["is_alert"]
                                )
                                    ? "alert"
                                    : "" ?>"><?= htmlspecialchars(
    $task["deadline_text"],
) ?></span>
                            </div>

                            <div style="margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                <?php if (empty($task["is_completed"])): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="close" />
                                        <input type="hidden" name="id" value="<?= (int) $task[
                                            "id"
                                        ] ?>" />
                                        <button type="submit" class="trigger-btn btn-blue" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer;">Selesai</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_publish" />
                                    <input type="hidden" name="id" value="<?= (int) $task[
                                        "id"
                                    ] ?>" />
                                    <button type="submit" name="publish" value="1" class="trigger-btn" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer; background-color: var(--primary); color:white;">Publish</button>
                                </form>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="id" value="<?= (int) $task[
                                        "id"
                                    ] ?>" />
                                    <button type="submit" class="trigger-btn btn-red" style="border:none; text-decoration:none; padding:8px 12px; cursor:pointer;">Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="padding: 16px; border-top: 1px solid #f3f4f6; color: #6b7280; font-size: 0.875rem;">
                <p><strong>Catatan:</strong> Menghapus tugas di sini hanya akan menghapus tampilan di daftar Anda. Tugas yang sudah didistribusikan ke mahasiswa akan tetap ada di dashboard mereka.</p>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>
