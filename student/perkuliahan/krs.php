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

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enroll"])) {
    $course_code = $_POST["course_code"];
    $academic_year = "2023/2024"; // Should ideally be dynamic
    $semester_type = "Genap"; // Should ideally be dynamic

    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM enrollments WHERE nim = ? AND course_code = ? AND academic_year = ?",
        );
        $stmt->execute([$nim, $course_code, $academic_year]);
        if ($stmt->fetch()) {
            $error = "Mata kuliah ini sudah diambil.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO enrollments (nim, course_code, academic_year, semester_type, status) VALUES (?, ?, ?, ?, 'approved')",
            );
            $stmt->execute([
                $nim,
                $course_code,
                $academic_year,
                $semester_type,
            ]);
            $success = "Mata kuliah berhasil ditambahkan ke KRS.";
        }
    } catch (PDOException $e) {
        $error = "Gagal mengambil mata kuliah.";
    }
}

$enrolled_courses = [];
try {
    $stmt = $pdo->prepare(
        "SELECT e.*, s.course_name, s.sks FROM enrollments e JOIN subjects s ON e.course_code = s.course_code WHERE e.nim = ? ORDER BY e.id DESC",
    );
    $stmt->execute([$nim]);
    $enrolled_courses = $stmt->fetchAll();
} catch (PDOException $e) {
}

$available_courses = [];
try {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY course_code ASC");
    $available_courses = $stmt->fetchAll();
} catch (PDOException $e) {
}

$base_path = "../../";
$page_title = "KRS (Kartu Rencana Studi) - INSPIRE Lite";
$current_page = "perkuliahan";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <?php if ($success): ?>
            <div style="padding: 15px; margin: 15px; border-radius: 8px; background: #dcfce7; color: #15803d; font-weight: bold;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="padding: 15px; margin: 15px; border-radius: 8px; background: #fee2e2; color: #b91c1c;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="content-card" style="margin-bottom: 24px;">
            <div class="card-top">
                <h3>KRS Saat Ini</h3>
            </div>
            <div class="agenda-table-wrapper" style="margin: 16px;">
                <div class="agenda-table-header">
                    <span class="col-head">KODE</span>
                    <span class="col-head">MATA KULIAH</span>
                    <span class="col-head">SKS</span>
                    <span class="col-head">STATUS</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($enrolled_courses)): ?>
                        <div class="empty-fallback-text border-box-pad">Belum ada mata kuliah yang diambil.</div>
                    <?php else: ?>
                        <?php foreach ($enrolled_courses as $ec): ?>
                            <div class="agenda-table-row">
                                <div class="col-cell cell-date">
                                    <span class="date-lbl-txt"><?= htmlspecialchars(
                                        $ec["course_code"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars(
                                        $ec["course_name"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc"><?= htmlspecialchars(
                                    $ec["sks"],
                                ) ?> SKS</div>
                                <div class="col-cell" style="flex: 0.5; font-size: 0.8rem; font-weight: 700; color: #059669;">
                                    <?= strtoupper($ec["status"]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-top">
                <h3>Penawaran Mata Kuliah</h3>
            </div>
            <div class="agenda-table-wrapper" style="margin: 16px;">
                <div class="agenda-table-header">
                    <span class="col-head">KODE</span>
                    <span class="col-head">MATA KULIAH</span>
                    <span class="col-head">SKS</span>
                    <span class="col-head">AKSI</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($available_courses)): ?>
                        <div class="empty-fallback-text border-box-pad">Tidak ada mata kuliah tersedia untuk saat ini.</div>
                    <?php else: ?>
                        <?php foreach ($available_courses as $ac): ?>
                            <div class="agenda-table-row">
                                <div class="col-cell cell-date">
                                    <span class="date-lbl-txt"><?= htmlspecialchars(
                                        $ac["course_code"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars(
                                        $ac["course_name"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc"><?= htmlspecialchars(
                                    $ac["sks"],
                                ) ?> SKS</div>
                                <div class="col-cell" style="flex: 0.5;">
                                    <form method="POST">
                                        <input type="hidden" name="course_code" value="<?= htmlspecialchars(
                                            $ac["course_code"],
                                        ) ?>">
                                        <button name="enroll" type="submit" class="trigger-btn" style="padding: 6px 12px; font-size: 0.75rem; background-color: #3b82f6; color: white; border: none;">Ambil</button>
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
<?php include $base_path . "includes/footer.php"; ?>
