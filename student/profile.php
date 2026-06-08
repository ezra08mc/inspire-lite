<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_data = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) { $nim = $student_data["nim"] ?? $nim; }
} catch (PDOException $e) { $data_errors[] = "Gagal memuat profil mahasiswa."; }

$stats = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM student_academic_stats WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $user_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {}

$student_name = $student_data ? ($student_data["first_name"] . " " . $student_data["last_name"]) : $nim;
$initials = "";
if ($student_data) {
    $initials = strtoupper(substr($student_data["first_name"] ?? "", 0, 1) . substr($student_data["last_name"] ?? "", 0, 1));
} else if (!empty($nim)) {
    $initials = strtoupper(substr($nim, 0, 2));
}

$unread_count = 0;
$base_path = "../";
$page_title = "Profil Mahasiswa - INSPIRE Lite";
$current_page = "profile";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="content-body" style="padding: 24px;">
        <div class="content-card pf-hero-card">
            <div class="pf-hero-inner">
                <div class="pf-avatar-xl"><?= htmlspecialchars($initials) ?></div>
                <div class="pf-hero-info">
                    <div class="pf-hero-name"><?= htmlspecialchars($student_name) ?></div>
                    <div class="pf-hero-meta">
                        <span class="pf-chip"><?= htmlspecialchars($nim) ?></span>
                        <span class="pf-chip"><?= htmlspecialchars($student_data["study_program"] ?? "-") ?></span>
                        <span class="pf-badge-active"><span class="pf-badge-dot"></span>Aktif</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pf-two-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="content-card">
                <div class="card-top"><h3>Biodata Diri</h3></div>
                <div class="pf-biodata" style="padding: 10px 0;">
                    <div class="pf-row"><span>Nama Lengkap</span><strong><?= htmlspecialchars($student_name) ?></strong></div>
                    <div class="pf-row"><span>NIM</span><strong><?= htmlspecialchars($nim) ?></strong></div>
                    <div class="pf-row"><span>Program Studi</span><strong><?= htmlspecialchars($student_data["study_program"] ?? "-") ?></strong></div>
                    <div class="pf-row"><span>Angkatan</span><strong><?= htmlspecialchars($student_data["cohort"] ?? "-") ?></strong></div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-top"><h3>Ringkasan Akademik</h3></div>
                <div class="pf-stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="pf-stat-tile"><span>IPK</span><strong><?= htmlspecialchars($stats["ipk_kumulatif"] ?? "0.00") ?></strong></div>
                    <div class="pf-stat-tile"><span>SKS</span><strong><?= htmlspecialchars($stats["sks_ditempuh"] ?? "0") ?></strong></div>
                </div>
            </div>
        </div>
    </main>
</div>
<style>
    .pf-hero-card { margin-bottom: 20px; padding: 24px; }
    .pf-hero-inner { display: flex; align-items: center; gap: 20px; }
    .pf-avatar-xl { width: 64px; height: 64px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; }
    .pf-hero-name { font-size: 1.25rem; font-weight: 800; color: #111827; }
    .pf-hero-meta { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
    .pf-chip { background: #f3f4f6; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
    .pf-badge-active { background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 5px; }
    .pf-badge-dot { width: 6px; height: 6px; background: #10b981; border-radius: 50%; }
    .pf-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; }
    .pf-stat-tile { background: #f9fafb; padding: 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 5px; }
    .pf-stat-tile strong { font-size: 1.5rem; color: var(--primary); }
</style>
<?php include $base_path . "includes/footer.php"; ?>
