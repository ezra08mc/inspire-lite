<?php
session_start();
require_once "../../config/db.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../../login.php");
    exit();
}
$base_path = "../../";
$page_title = "Presensi - INSPIRE Lite";
$current_page = "perkuliahan";

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kode_presensi = strtoupper(trim($_POST["kode_presensi"] ?? ""));
    if ($kode_presensi && $nim) {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, status FROM presensi_sessions WHERE kode_presensi = ? AND tanggal = CURDATE() LIMIT 1",
            );
            $stmt->execute([$kode_presensi]);
            $session = $stmt->fetch();
            if ($session) {
                if ($session["status"] === "open") {
                    $stmt_check = $pdo->prepare(
                        "SELECT id FROM student_presensi WHERE session_id = ? AND nim = ?",
                    );
                    $stmt_check->execute([$session["id"], $nim]);
                    if ($stmt_check->fetch()) {
                        $error =
                            "Anda sudah melakukan presensi untuk sesi ini.";
                    } else {
                        $stmt_insert = $pdo->prepare(
                            "INSERT INTO student_presensi (session_id, nim) VALUES (?, ?)",
                        );
                        $stmt_insert->execute([$session["id"], $nim]);
                        $success = "Presensi berhasil dicatat!";
                    }
                } else {
                    $error = "Sesi presensi untuk kode ini sudah ditutup.";
                }
            } else {
                $error = "Kode presensi tidak valid atau tidak aktif hari ini.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem.";
        }
    } else {
        $error = "Masukkan kode presensi.";
    }
}

$riwayat = [];
if ($nim) {
    try {
        $stmt = $pdo->prepare(
            "SELECT sp.waktu_presensi, ps.tanggal, s.course_name, l.first_name as dosen_first, l.last_name as dosen_last FROM student_presensi sp JOIN presensi_sessions ps ON ps.id = sp.session_id JOIN subjects s ON s.course_code = ps.course_code JOIN lecturers l ON l.nip = ps.nip WHERE sp.nim = ? ORDER BY sp.id DESC LIMIT 10",
        );
        $stmt->execute([$nim]);
        $riwayat = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport">
        <div class="split-grid" style="gap: 16px; align-items:start;">
            <div class="content-card" style="flex: 1;">
                <div class="card-top">
                    <h3>Isi Presensi</h3>
                </div>
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
                <form method="POST" style="padding: 16px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <svg viewBox="0 0 24 24" style="width: 64px; height: 64px; fill: #D2232A; margin: 0 auto;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                        <p style="color: #6b7280; font-size: 0.9rem; margin-top: 10px;">Masukkan 6 digit kode presensi yang diberikan oleh dosen Anda.</p>
                    </div>
                    <label style="display:block; margin-bottom: 12px; text-align: center;">
                        <input required name="kode_presensi" type="text" maxlength="6" style="width:80%; max-width: 250px; padding:15px; border-radius:10px; border:2px solid #e5e7eb; font-size: 1.5rem; text-align: center; letter-spacing: 5px; text-transform: uppercase; outline: none;" placeholder="XXXXXX" />
                    </label>
                    <div style="text-align: center;">
                        <button class="trigger-btn" type="submit" style="border:none; width: 80%; max-width: 250px; padding: 12px; background-color: #D2232A; color: white;">Hadir</button>
                    </div>
                </form>
            </div>

            <div class="content-card" style="flex: 1.5;">
                <div class="card-top">
                    <h3>Riwayat Presensi</h3>
                </div>
                <div class="agenda-table-wrapper" style="margin: 16px;">
                    <div class="agenda-table-header">
                        <span class="col-head">TANGGAL</span>
                        <span class="col-head">MATA KULIAH</span>
                        <span class="col-head">DOSEN</span>
                    </div>
                    <div class="agenda-rows-stack">
                        <?php if (empty($riwayat)): ?>
                            <div class="empty-fallback-text border-box-pad">Belum ada riwayat presensi.</div>
                        <?php else: ?>
                            <?php foreach ($riwayat as $r): ?>
                                <div class="agenda-table-row">
                                    <div class="col-cell cell-date">
                                        <span class="indicator-circle-bullet green" style="background-color: #10b981;"></span>
                                        <span class="date-lbl-txt"><?= htmlspecialchars(
                                            new DateTime(
                                                $r["waktu_presensi"],
                                            )->format("d M H:i"),
                                        ) ?></span>
                                    </div>
                                    <div class="col-cell cell-mid-desc">
                                        <span class="item-main-headline"><?= htmlspecialchars(
                                            $r["course_name"],
                                        ) ?></span>
                                    </div>
                                    <div class="col-cell cell-room-loc"><?= htmlspecialchars(
                                        $r["dosen_first"] .
                                            " " .
                                            $r["dosen_last"],
                                    ) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
