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
        "SELECT * FROM students WHERE user_id = :user_id LIMIT 1",
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
        <div class="content-card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-top">
                <h3>Kartu Tanda Mahasiswa Digital</h3>
                <button onclick="window.print()" class="trigger-btn" style="padding: 6px 12px; font-size: 0.75rem; background-color: #D2232A; color: white; border: none;">Unduh PDF</button>
            </div>

            <div class="ktm-container" style="padding: 24px; display: flex; justify-content: center;">
                <!-- KTM Design -->
                <div class="ktm-card" style="width: 100%; max-width: 450px; aspect-ratio: 1.58 / 1; background: linear-gradient(135deg, #D2232A 0%, #8B0000 100%); border-radius: 20px; position: relative; overflow: hidden; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.2); padding: 20px; display: flex; flex-direction: column;">
                    <!-- Background Pattern -->
                    <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                    <!-- Card Header -->
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; position: relative; z-index: 1;">
                        <div style="width: 40px; height: 40px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: #D2232A;"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 800; letter-spacing: 1px;">INSPIRE LITE</h4>
                            <p style="margin: 0; font-size: 0.6rem; opacity: 0.8; font-weight: 600;">KARTU TANDA MAHASISWA</p>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div style="display: flex; gap: 20px; flex: 1; position: relative; z-index: 1;">
                        <!-- Photo Placeholder -->
                        <div style="width: 100px; height: 125px; background: #f3f4f6; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(255,255,255,0.2); overflow: hidden;">
                            <svg viewBox="0 0 24 24" style="width: 60px; height: 60px; fill: #d1d5db;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>

                        <!-- Info -->
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                            <div style="margin-bottom: 12px;">
                                <p style="margin: 0; font-size: 0.6rem; opacity: 0.7; text-transform: uppercase;">Nama Lengkap</p>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars(
                                    $display_name,
                                ) ?></h3>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <p style="margin: 0; font-size: 0.6rem; opacity: 0.7; text-transform: uppercase;">Nomor Induk Mahasiswa</p>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; letter-spacing: 1px;"><?= htmlspecialchars(
                                    $nim,
                                ) ?></h3>
                            </div>
                            <div>
                                <p style="margin: 0; font-size: 0.6rem; opacity: 0.7; text-transform: uppercase;">Program Studi</p>
                                <p style="margin: 0; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars(
                                    $student_data["study_program"] ?? "-",
                                ) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 1;">
                         <p style="margin: 0; font-size: 0.5rem; opacity: 0.6;">Diterbitkan pada: <?= date(
                             "d/m/Y",
                         ) ?></p>
                         <div style="width: 40px; height: 40px; background: white; padding: 4px; border-radius: 4px;">
                             <!-- Mock QR Code -->
                             <div style="width: 100%; height: 100%; display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px;">
                                 <?php for ($i = 0; $i < 16; $i++): ?>
                                     <div style="background: <?= rand(0, 1)
                                         ? "#000"
                                         : "#fff" ?>;"></div>
                                 <?php endfor; ?>
                             </div>
                         </div>
                    </div>
                </div>
            </div>

            <div style="padding: 24px; border-top: 1px solid #f3f4f6; text-align: center;">
                <p style="font-size: 0.85rem; color: #6b7280; line-height: 1.6;">Gunakan kartu ini sebagai tanda pengenal resmi di lingkungan kampus. <br>Anda dapat mencetak kartu ini untuk keperluan fisik.</p>
            </div>
        </div>
    </main>
</div>

<style>
@media print {
    .sidebar, .navbar, .card-top button, .main-content > header { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .content-card { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; }
    .ktm-card { box-shadow: none !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<?php include $base_path . "includes/footer.php"; ?>
