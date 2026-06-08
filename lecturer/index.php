<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "lecturer") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$lecturer_name = "";
$nip = $_SESSION["username"] ?? "";

try {
    $stmt = $pdo->prepare("SELECT nip, first_name, last_name FROM lecturers WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $lec_data = $stmt->fetch();
    if ($lec_data) {
        $lecturer_name = $lec_data['first_name'] . ' ' . $lec_data['last_name'];
        $nip = $lec_data['nip'];
    }
} catch (PDOException $e) {}

$initials = "DS";
if (!empty($lecturer_name)) {
    $parts = explode(" ", $lecturer_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ""));
}
$display_name = $lecturer_name ?: "Dosen";
$unread_count = 0;

$base_path = "../";
$page_title = "Lecturer Dashboard - INSPIRE Lite";
$current_page = "dashboard";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Halo, <?= htmlspecialchars(explode(' ', $display_name)[0]) ?>!</h1>
                <p>Dosen · Portal Akademik · NIP: <?= htmlspecialchars($nip) ?></p>
            </div>
        </section>

        <div class="split-grid flex-equal-align">
            <div class="content-card">
                <div class="card-top"><h3>Aksi Cepat</h3></div>
                <div class="quick-actions-box flex-stretch-actions">
                    <a href="schedule.php" class="action-node"><div class="action-node-icon jadwal"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg></div><span>Jadwal</span></a>
                    <a href="grading.php" class="action-node"><div class="action-node-icon transkrip"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></div><span>Input Nilai</span></a>
                    <a href="attendance.php" class="action-node"><div class="action-node-icon absensi"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div><span>Presensi</span></a>
                    <a href="advising.php" class="action-node"><div class="action-node-icon bimbingan"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div><span>Bimbingan</span></a>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
