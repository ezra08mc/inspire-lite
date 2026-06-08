<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_name = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

try {
    $stmt = $pdo->prepare(
        "SELECT nim, first_name, last_name FROM students WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) {
        $first_name = trim($student_data["first_name"] ?? "");
        $last_name = trim($student_data["last_name"] ?? "");
        $student_name = trim($first_name . " " . $last_name);
        $nim = $student_data["nim"] ?? $nim;
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat profil mahasiswa.";
}

$full_schedule = [];
try {
    $stmt = $pdo->prepare(
        "SELECT kode_mk, nama_mata_kuliah, sks, kelas, dosen_pengampu, hari, jam_mulai, jam_selesai, ruangan FROM jadwal ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_mulai ASC",
    );
    $stmt->execute();
    $full_schedule = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat jadwal kuliah.";
}

$initials = "";
if (!empty($student_name)) {
    $parts = explode(" ", $student_name);
    $initials = strtoupper(
        substr($parts[0], 0, 1) .
            (isset($parts[1]) ? substr($parts[1], 0, 1) : ""),
    );
} else {
    $initials = strtoupper(substr($nim, 0, 2));
}
$display_name = $student_name ?: $nim;
$unread_count = 0;

$base_path = "../../";
$page_title = "Jadwal Kuliah - INSPIRE Lite";
$current_page = "perkuliahan";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="content-body" style="padding: 24px;">
        <div class="content-card">
            <div class="card-top">
                <h3>Daftar Jadwal Mata Kuliah</h3>
                <button class="btn-primary-outline" onclick="window.print()" style="padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24"><path d="M18 3H6v4h12V3m1 5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3M7 19v-5h10v5H7m11-9c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>
                    Cetak Jadwal
                </button>
            </div>
            <div class="table-responsive" style="margin-top: 20px; overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">HARI</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">JAM</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">MATA KULIAH</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">SKS</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">KELAS</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">RUANGAN</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #6b7280;">DOSEN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($full_schedule)): ?>
                            <tr><td colspan="7" style="padding: 24px; text-align: center; color: #6b7280;">Belum ada jadwal kuliah yang tersedia.</td></tr>
                        <?php else: ?>
                            <?php foreach ($full_schedule as $item): ?>
                                <tr>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><strong><?= htmlspecialchars(
                                        $item["hari"],
                                    ) ?></strong></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars(
                                        substr($item["jam_mulai"], 0, 5),
                                    ) ?> - <?= htmlspecialchars(
     substr($item["jam_selesai"], 0, 5),
 ) ?></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><div style="display: flex; flex-direction: column;"><span style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars(
                                        $item["kode_mk"],
                                    ) ?></span><span style="font-weight: 600;"><?= htmlspecialchars(
    $item["nama_mata_kuliah"],
) ?></span></div></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars(
                                        $item["sks"],
                                    ) ?></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars(
                                        $item["kelas"],
                                    ) ?></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><span style="padding: 4px 8px; background: #eff6ff; color: #1d4ed8; border-radius: 4px; font-size: 0.75rem;"><?= htmlspecialchars(
                                        $item["ruangan"],
                                    ) ?></span></td>
                                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 0.875rem;"><?= htmlspecialchars(
                                        $item["dosen_pengampu"],
                                    ) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
