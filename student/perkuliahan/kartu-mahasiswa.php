<?php
session_start();
require_once "../../config/db.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$nim = $_SESSION["username"] ?? "";
$student_data = null;
$display_name = $nim;
$initials = "??";

try {
    $stmt = $pdo->prepare(
        "SELECT nim, first_name, last_name, study_program FROM students WHERE user_id = :user_id LIMIT 1",
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

$base_path = "../../";
$page_title = "Kartu Mahasiswa - INSPIRE Lite";
$current_page = "perkuliahan";
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <div class="content-card" style="max-width: 400px; margin: 0 auto; text-align: center;">
            <h3>Kartu Mahasiswa</h3>
            <div style="border: 2px solid #D2232A; border-radius: 12px; padding: 20px; margin-top: 20px; text-align: left;">
                <div style="font-weight: bold; color: #D2232A; margin-bottom: 10px;">INSPIRE LITE</div>
                <div style="width: 80px; height: 100px; background: #e5e7eb; margin-bottom: 10px;"></div>
                <div style="font-size: 1.1rem; font-weight: bold;"><?= htmlspecialchars(
                    ($student_data["first_name"] ?? "") .
                        " " .
                        ($student_data["last_name"] ?? ""),
                ) ?></div>
                <div style="color: #6b7280;"><?= htmlspecialchars($nim) ?></div>
                <div style="margin-top: 10px; font-weight: 600;">Role: Mahasiswa</div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
