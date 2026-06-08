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

$grades = [];
$total_sks = 0;
$total_grade_points = 0;
try {
    $stmt = $pdo->prepare(
        "SELECT g.*, s.course_name, s.sks FROM academic_grades g JOIN subjects s ON g.course_code = s.course_code WHERE g.nim = ? ORDER BY s.semester DESC",
    );
    $stmt->execute([$nim]);
    $grades = $stmt->fetchAll();

    foreach ($grades as $g) {
        $total_sks += $g["sks"];
        $total_grade_points += $g["grade_point"] * $g["sks"];
    }
} catch (PDOException $e) {
}

$ips = $total_sks > 0 ? round($total_grade_points / $total_sks, 2) : "0.00";

$base_path = "../../";
$page_title = "KHS (Kartu Hasil Studi) - INSPIRE Lite";
$current_page = "perkuliahan";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <div class="content-card" style="margin-bottom: 24px;">
            <div class="card-top">
                <h3>Ringkasan Semester</h3>
            </div>
            <div class="metrics-row" style="padding: 16px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                <div class="metric-card">
                    <span class="metric-label">IPS</span>
                    <span class="metric-value"><?= htmlspecialchars(
                        $ips,
                    ) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">SKS DIAMBIL</span>
                    <span class="metric-value"><?= htmlspecialchars(
                        $total_sks,
                    ) ?></span>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-top">
                <h3>Hasil Studi Semester</h3>
            </div>
            <div class="agenda-table-wrapper" style="margin: 16px;">
                <div class="agenda-table-header">
                    <span class="col-head">KODE</span>
                    <span class="col-head">MATA KULIAH</span>
                    <span class="col-head">SKS</span>
                    <span class="col-head">NILAI</span>
                    <span class="col-head">BOBOT</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($grades)): ?>
                        <div class="empty-fallback-text border-box-pad">Data nilai semester belum tersedia.</div>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <div class="agenda-table-row">
                                <div class="col-cell cell-date">
                                    <span class="date-lbl-txt"><?= htmlspecialchars(
                                        $g["course_code"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars(
                                        $g["course_name"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc"><?= htmlspecialchars(
                                    $g["sks"],
                                ) ?></div>
                                <div class="col-cell" style="flex: 0.3; font-weight: 700; color: #111827;">
                                    <?= htmlspecialchars($g["grade_letter"]) ?>
                                </div>
                                <div class="col-cell" style="flex: 0.3; font-weight: 600; color: #6b7280;">
                                    <?= htmlspecialchars($g["grade_point"]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
