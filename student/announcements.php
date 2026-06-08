<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_name = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

try {
    $stmt = $pdo->prepare("SELECT nim, first_name, last_name FROM students WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) {
        $first_name = trim($student_data["first_name"] ?? "");
        $last_name = trim($student_data["last_name"] ?? "");
        $student_name = trim($first_name . " " . $last_name);
        $nim = $student_data["nim"] ?? $nim;
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat profil mahasiswa.";
}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY id DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat pengumuman.";
}

$initials = "";
if (!empty($student_name)) {
    $parts = explode(" ", $student_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ""));
} else {
    $initials = strtoupper(substr($nim, 0, 2));
}
$display_name = $student_name ?: $nim;
$unread_count = 0;

$base_path = "../";
$page_title = "Pengumuman - INSPIRE Lite";
$current_page = "announcements";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="content-body" style="padding: 24px;">
        <div class="announcements-stack">
            <?php if (empty($announcements)): ?>
                <div class="content-card" style="text-align:center; padding: 40px; color: #6b7280;">
                    <p>Tidak ada pengumuman saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $a): ?>
                    <div class="content-card announcement-card">
                        <div class="announcement-header">
                            <span class="badge-type <?= htmlspecialchars(strtolower($a["badge_class"])) ?>">
                                <?= htmlspecialchars($a["type"]) ?>
                            </span>
                            <span class="announcement-date"><?= htmlspecialchars($a["date_text"]) ?></span>
                        </div>
                        <h2 class="announcement-title"><?= htmlspecialchars($a["title"]) ?></h2>
                        <div class="announcement-content">
                            <?= nl2br(htmlspecialchars($a["content"])) ?>
                        </div>
                        <div class="announcement-footer">
                            <span class="author-label">Diterbitkan oleh:</span>
                            <span class="author-name"><?= htmlspecialchars($a["author"]) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<style>
    .announcements-stack { display: flex; flex-direction: column; gap: 20px; }
    .announcement-card { padding: 24px; transition: transform 0.2s; }
    .announcement-card:hover { transform: translateY(-2px); }
    .announcement-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .badge-type { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    .badge-type.red { background-color: #fee2e2; color: #ef4444; }
    .badge-type.blue { background-color: #dbeafe; color: #3b82f6; }
    .badge-type.green { background-color: #d1fae5; color: #10b981; }
    .announcement-date { font-size: 0.8rem; color: #6b7280; }
    .announcement-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 12px; color: #111827; }
    .announcement-content { font-size: 0.95rem; line-height: 1.6; color: #4b5563; margin-bottom: 20px; }
    .announcement-footer { display: flex; align-items: center; gap: 8px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 0.8rem; }
    .author-label { color: #6b7280; }
    .author-name { font-weight: 600; color: #1f2937; }
</style>
<?php include $base_path . "includes/footer.php"; ?>
