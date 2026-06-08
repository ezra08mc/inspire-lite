<?php
session_start();
require_once "../../config/db.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../../login.php");
    exit();
}
$base_path = "../../";
$page_title = "Bimbingan - INSPIRE Lite";
$current_page = "perkuliahan";

$studentId = (int) ($_SESSION["user_id"] ?? 0);

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $receiver_id = (int) ($_POST["receiver_id"] ?? 0);
    $message = trim($_POST["message"] ?? "");

    if ($receiver_id && $message) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)",
            );
            $stmt->execute([$studentId, $receiver_id, $message]);
            $success = "Pesan terkirim.";
        } catch (PDOException $e) {
            $error = "Gagal mengirim pesan.";
        }
    } else {
        $error = "Pilih dosen dan ketik pesan.";
    }
}

// Get distinct lecturers
$lecturers = [];
try {
    $stmt = $pdo->query(
        "SELECT u.id, l.first_name, l.last_name, l.nip FROM users u JOIN lecturers l ON l.user_id = u.id ORDER BY l.first_name",
    );
    $lecturers = $stmt->fetchAll();
} catch (PDOException $e) {
}

$selected_lecturer_id = (int) ($_GET["lecturer"] ?? 0);
$chat_history = [];
if ($selected_lecturer_id) {
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC",
        );
        $stmt->execute([
            $studentId,
            $selected_lecturer_id,
            $selected_lecturer_id,
            $studentId,
        ]);
        $chat_history = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
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

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>

<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <div class="split-grid" style="gap: 16px; align-items:start;">
            <div class="content-card" style="flex: 1;">
                <div class="card-top">
                    <h3>Daftar Dosen</h3>
                </div>
                <div style="padding: 16px; max-height: 500px; overflow-y: auto;">
                    <?php foreach ($lecturers as $lec): ?>
                        <a href="?lecturer=<?= $lec[
                            "id"
                        ] ?>" style="display:block; padding:10px; border-bottom:1px solid #e5e7eb; text-decoration:none; color: <?= $selected_lecturer_id ==
$lec["id"]
    ? "#D2232A"
    : "#374151" ?>; font-weight: <?= $selected_lecturer_id == $lec["id"]
    ? "bold"
    : "normal" ?>;">
                            <?= htmlspecialchars(
                                $lec["first_name"] . " " . $lec["last_name"],
                            ) ?> <br>
                            <span style="font-size:0.75rem; color:#6b7280; font-weight:normal;">NIP: <?= htmlspecialchars(
                                $lec["nip"],
                            ) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="content-card" style="flex: 2; display: flex; flex-direction: column; height: 550px;">
                <div class="card-top">
                    <h3>Ruang Konsultasi</h3>
                </div>

                <?php if ($selected_lecturer_id): ?>
                    <div style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <?php if (empty($chat_history)): ?>
                            <div style="text-align: center; color: #6b7280; margin-top: 20px;">Belum ada pesan. Mulai percakapan sekarang.</div>
                        <?php else: ?>
                            <?php foreach ($chat_history as $msg): ?>
                                <?php $is_me =
                                    $msg["sender_id"] == $studentId; ?>
                                <div style="max-width: 70%; padding: 10px 14px; border-radius: 12px; <?= $is_me
                                    ? "align-self: flex-end; background: #D2232A; color: white;"
                                    : "align-self: flex-start; background: white; border: 1px solid #e5e7eb; color: #1F2937;" ?>">
                                    <div style="margin-bottom: 4px;"><?= nl2br(
                                        htmlspecialchars($msg["message"]),
                                    ) ?></div>
                                    <div style="font-size: 0.65rem; text-align: right; <?= $is_me
                                        ? "color: #ffcccc;"
                                        : "color: #9ca3af;" ?>">
                                        <?= htmlspecialchars(
                                            new DateTime(
                                                $msg["created_at"],
                                            )->format("d M H:i"),
                                        ) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 16px;">
                        <?php if ($error): ?>
                            <div style="color: #b91c1c; font-size: 0.8rem; margin-bottom: 5px;"><?= htmlspecialchars(
                                $error,
                            ) ?></div>
                        <?php endif; ?>
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="receiver_id" value="<?= $selected_lecturer_id ?>">
                            <input type="text" name="message" required placeholder="Ketik pesan Anda..." style="flex: 1; padding: 10px; border-radius: 20px; border: 1px solid #e5e7eb; outline: none;">
                            <button type="submit" class="trigger-btn btn-blue" style="border: none; border-radius: 20px; padding: 10px 20px; background-color: #D2232A; color: white;">Kirim</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #6b7280; text-align: center; padding: 20px;">
                        Silakan pilih dosen dari daftar di sebelah kiri untuk memulai sesi bimbingan akademik.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
