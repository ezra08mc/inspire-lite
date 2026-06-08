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

$base_path = "../../";
$page_title = "$title - INSPIRE Lite";
$current_page = "perkuliahan";
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh;">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background-color: rgb(255, 240, 240); padding: 16px; border-radius: 1rem;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-construction w-8 h-8" style="color: rgb(210, 35, 42); width: 32px; height: 32px;"><rect x="2" y="6" width="20" height="8" rx="1"></rect><path d="M17 14v7"></path><path d="M7 14v7"></path><path d="M17 3v3"></path><path d="M7 3v3"></path><path d="M10 14 2.3 6.3"></path><path d="m14 6 7.7 7.7"></path><path d="m8 6 8 8"></path></svg></div>
        <h2 style="font-size: 1.5rem; color: #1F2937; margin-bottom: 10px;"><?= $title ?></h2>
        <p style="color: #6b7280;">Halaman ini sedang dalam pengembangan dan akan segera tersedia.</p>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
