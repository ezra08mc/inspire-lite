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
    $action = $_POST["action"] ?? "";
    if ($action === "backup") {
        $success =
            "Backup basis data berhasil dibuat: backup_" .
            date("Ymd_His") .
            ".sql";
    }
}

$base_path = "../../";
$page_title = "Backup Data - Admin";
$current_page = "backup";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Pemeliharaan & Backup</h1>
                <p>Amankan data sistem melalui ekspor basis data berkala.</p>
            </div>
        </section>

        <div class="split-grid" style="gap: 24px; align-items: start;">
            <div class="content-card" style="flex: 1;">
                <div class="card-top">
                    <h3>Backup Basis Data</h3>
                </div>
                <div style="padding: 20px;">
                    <p style="font-size: 0.9rem; color: #4b5563; margin-bottom: 20px;">
                        Klik tombol di bawah untuk mengunduh salinan lengkap basis data saat ini dalam format SQL.
                        Sangat disarankan untuk melakukan backup sebelum melakukan perubahan besar pada sistem.
                    </p>

                    <?php if ($success): ?>
                        <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: #dcfce7; color: #15803d; font-weight: 600; font-size: 0.85rem;">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="backup">
                        <button type="submit" class="trigger-btn btn-blue" style="border: none; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <svg style="width: 20px; fill: white;" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
                            Generate SQL Backup
                        </button>
                    </form>
                </div>
            </div>

            <div class="content-card" style="flex: 1.5;">
                <div class="card-top">
                    <h3>Riwayat Backup</h3>
                </div>
                <div class="agenda-table-wrapper" style="margin: 0;">
                    <div class="agenda-table-header">
                        <span class="col-head">NAMA FILE</span>
                        <span class="col-head">UKURAN</span>
                        <span class="col-head">TANGGAL</span>
                    </div>
                    <div class="agenda-rows-stack">
                        <div class="agenda-table-row">
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline">backup_20260601_1000.sql</span>
                            </div>
                            <div class="col-cell">1.2 MB</div>
                            <div class="col-cell">01 Jun 2026</div>
                        </div>
                        <div class="agenda-table-row">
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline">backup_20260520_0830.sql</span>
                            </div>
                            <div class="col-cell">1.1 MB</div>
                            <div class="col-cell">20 Mei 2026</div>
                        </div>
                        <div class="agenda-table-row">
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline">backup_20260510_1545.sql</span>
                            </div>
                            <div class="col-cell">1.0 MB</div>
                            <div class="col-cell">10 Mei 2026</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
