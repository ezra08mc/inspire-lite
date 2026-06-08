<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$admin_name = "";
$admin_id = $_SESSION["username"];

try {
    $stmt = $pdo->prepare(
        "SELECT admin_id, first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name =
            $admin_data["first_name"] . " " . $admin_data["last_name"];
        $admin_id = $admin_data["admin_id"];
    }
} catch (PDOException $e) {
}

$initials = "AD";
if (!empty($admin_name)) {
    $parts = explode(" ", $admin_name);
    $initials = strtoupper(
        substr($parts[0], 0, 1) .
            (isset($parts[1]) ? substr($parts[1], 0, 1) : ""),
    );
}
$display_name = $admin_name ?: "Administrator";

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $success = "Pengaturan branding berhasil diperbarui (Simulasi).";
}

$base_path = "../../";
$page_title = "Branding Portal - Admin";
$current_page = "branding";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Branding Portal</h1>
                <p>Sesuaikan tampilan visual portal akademik.</p>
            </div>
        </section>

        <div class="content-card" style="max-width: 800px;">
            <div class="card-top">
                <h3>Identitas Visual</h3>
            </div>

            <?php if ($success): ?>
                <div style="padding: 15px; margin: 15px; border-radius: 8px; background: #dcfce7; color: #15803d;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="padding: 20px; display: grid; gap: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Nama Institusi</span>
                        <input type="text" value="UNIVERSITAS CONTOH" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Singkatan</span>
                        <input type="text" value="UNCONT" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Logo Utama (Header)</span>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="<?= $base_path ?>assets/img/logo.png" style="height: 40px; background: #f3f4f6; padding: 5px; border-radius: 4px;">
                            <input type="file" style="font-size: 0.8rem;">
                        </div>
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Logo Sidebar (Mini)</span>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; background: #D2232A; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white;">
                                <svg style="width: 24px; fill: white;" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2z"/></svg>
                            </div>
                            <input type="file" style="font-size: 0.8rem;">
                        </div>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Warna Utama</span>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" value="#D2232A" style="width: 40px; height: 40px; border: none; padding: 0; cursor: pointer;">
                            <input type="text" value="#D2232A" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; flex: 1;">
                        </div>
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="font-weight: 600; font-size: 0.9rem;">Warna Sekunder</span>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" value="#1F2937" style="width: 40px; height: 40px; border: none; padding: 0; cursor: pointer;">
                            <input type="text" value="#1F2937" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; flex: 1;">
                        </div>
                    </label>
                </div>

                <button type="submit" class="trigger-btn btn-blue" style="border: none; margin-top: 10px;">Simpan Perubahan</button>
            </form>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
