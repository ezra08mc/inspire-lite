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

$base_path = "../../";
$page_title = "Log Keamanan - Admin";
$current_page = "logs";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Log Keamanan</h1>
                <p>Pantau aktivitas login dan keamanan sistem.</p>
            </div>
        </section>

        <div class="content-card">
            <div class="card-top">
                <h3>Aktivitas Terbaru</h3>
            </div>

            <div class="agenda-table-wrapper" style="margin: 16px;">
                <div class="agenda-table-header">
                    <span class="col-head">WAKTU</span>
                    <span class="col-head">USERNAME</span>
                    <span class="col-head">AKSI</span>
                    <span class="col-head">IP ADDRESS</span>
                    <span class="col-head">STATUS</span>
                </div>
                <div class="agenda-rows-stack">
                    <div class="agenda-table-row">
                        <div class="col-cell cell-date">
                            <span class="date-lbl-txt"><?= date(
                                "d M Y H:i:s",
                                strtotime("-5 minutes"),
                            ) ?></span>
                        </div>
                        <div class="col-cell cell-mid-desc">
                            <span class="item-main-headline">admin_utama</span>
                        </div>
                        <div class="col-cell">Login</div>
                        <div class="col-cell">192.168.1.100</div>
                        <div class="col-cell"><span class="label-badge green" style="background-color: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Sukses</span></div>
                    </div>
                    <div class="agenda-table-row">
                        <div class="col-cell cell-date">
                            <span class="date-lbl-txt"><?= date(
                                "d M Y H:i:s",
                                strtotime("-1 hour"),
                            ) ?></span>
                        </div>
                        <div class="col-cell cell-mid-desc">
                            <span class="item-main-headline">dosen_01</span>
                        </div>
                        <div class="col-cell">Ubah Password</div>
                        <div class="col-cell">114.122.10.5</div>
                        <div class="col-cell"><span class="label-badge green" style="background-color: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Sukses</span></div>
                    </div>
                    <div class="agenda-table-row">
                        <div class="col-cell cell-date">
                            <span class="date-lbl-txt"><?= date(
                                "d M Y H:i:s",
                                strtotime("-3 hours"),
                            ) ?></span>
                        </div>
                        <div class="col-cell cell-mid-desc">
                            <span class="item-main-headline">unknown_user</span>
                        </div>
                        <div class="col-cell">Login</div>
                        <div class="col-cell">45.112.33.1</div>
                        <div class="col-cell"><span class="label-badge red" style="background-color: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Gagal</span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
