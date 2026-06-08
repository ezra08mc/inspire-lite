<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$nim = $_SESSION["username"] ?? "";
$display_name = $nim;
$initials = "??";

try {
    $stmt = $pdo->prepare(
        "SELECT nim, first_name, last_name FROM students WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) {
        $nim = $student_data["nim"];
        $full_name = trim(
            ($student_data["first_name"] ?? "") .
                " " .
                ($student_data["last_name"] ?? ""),
        );
        if (!empty($full_name)) {
            $display_name = $full_name;
            $name_parts = preg_split("/\s+/", $full_name);
            $initials = strtoupper(
                substr($name_parts[0] ?? "", 0, 1) .
                    (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ""),
            );
        }
    }
} catch (PDOException $e) {
}

$tasks = [];
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM active_tasks WHERE user_id = :user_id ORDER BY is_completed ASC, id DESC",
    );
    $stmt->execute([":user_id" => $user_id]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
}

$base_path = "../../";
$page_title = "Tugas Perkuliahan - INSPIRE Lite";
$current_page = "perkuliahan";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <div class="content-card">
            <div class="card-top">
                <h3>Daftar Tugas Aktif</h3>
            </div>
            <div class="tasks-vertical-stack" style="padding: 16px;">
                <?php if (empty($tasks)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada tugas aktif saat ini.</div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-node <?= $task["is_completed"]
                            ? "done"
                            : "" ?>" style="margin-bottom: 12px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 16px;">
                            <div class="task-checkbox-frame" style="width: 24px; height: 24px; border: 2px solid <?= $task[
                                "is_completed"
                            ]
                                ? "#10b981"
                                : "#d1d5db" ?>; border-radius: 6px; display: flex; align-items: center; justify-content: center; background: <?= $task[
    "is_completed"
]
    ? "#10b981"
    : "transparent" ?>;">
                                <?php if ($task["is_completed"]): ?>
                                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: white;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="task-node-details" style="flex: 1;">
                                <div class="task-node-title" style="font-weight: 700; color: #111827; <?= $task[
                                    "is_completed"
                                ]
                                    ? "text-decoration: line-through; opacity: 0.6;"
                                    : "" ?>"><?= htmlspecialchars(
    $task["name"],
) ?></div>
                                <div class="task-node-time <?= $task["is_alert"]
                                    ? "alert"
                                    : "" ?>" style="font-size: 0.8rem; margin-top: 4px; color: <?= $task[
    "is_alert"
]
    ? "#D2232A"
    : "#6b7280" ?>;">
                                    <?= htmlspecialchars(
                                        $task["deadline_text"],
                                    ) ?>
                                </div>
                            </div>
                            <?php if (!$task["is_completed"]): ?>
                                <button class="trigger-btn" style="padding: 8px 16px; font-size: 0.8rem; background-color: #f3f4f6; color: #374151; border: none;">Kumpulkan</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
