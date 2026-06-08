<?php
session_start();
require_once "../../config/db.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") { header("Location: ../../login.php"); exit(); }
$user_id = $_SESSION["user_id"]; $admin_name = ""; $admin_id = $_SESSION["username"];
try {
    $stmt = $pdo->prepare("SELECT admin_id, first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) { $admin_name = trim($admin_data["first_name"] . " " . $admin_data["last_name"]); $admin_id = $admin_data["admin_id"]; }
} catch (PDOException $e) {}
$initials = "AD"; if (!empty($admin_name)) { $parts = explode(" ", $admin_name); $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : "")); }
$display_name = $admin_name ?: "Administrator";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    try {
        if ($_POST["action"] === "delete") {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST["user_id"]]);
            $message = "User berhasil dihapus."; $message_type = "success";
        } elseif ($_POST["action"] === "update") {
            $uid = $_POST["user_id"]; $new_role = $_POST["role"]; $fn = $_POST["first_name"]; $ln = $_POST["last_name"]; $bd = $_POST["birth_date"];
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $uid]);
            if (!empty($_POST["password"])) { $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($_POST["password"], PASSWORD_BCRYPT), $uid]); }
            if ($new_role === "student") { $pdo->prepare("INSERT INTO students (user_id, nim, first_name, last_name, birth_date, study_program, cohort) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nim=?, first_name=?, last_name=?, birth_date=?, study_program=?, cohort=?")->execute([$uid, $_POST["nim"], $fn, $ln, $bd, $_POST["study_program"], $_POST["cohort"], $_POST["nim"], $fn, $ln, $bd, $_POST["study_program"], $_POST["cohort"]]); }
            elseif ($new_role === "lecturer") { $pdo->prepare("INSERT INTO lecturers (user_id, nip, first_name, last_name, birth_date, degree, expertise) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nip=?, first_name=?, last_name=?, birth_date=?, degree=?, expertise=?")->execute([$uid, $_POST["nip"], $fn, $ln, $bd, $_POST["degree"], $_POST["kdk"], $_POST["nip"], $fn, $ln, $bd, $_POST["degree"], $_POST["kdk"]]); }
            elseif ($new_role === "staff") { $pdo->prepare("INSERT INTO staff (user_id, staff_id, first_name, last_name, birth_date, division, position) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE staff_id=?, first_name=?, last_name=?, birth_date=?, division=?, position=?")->execute([$uid, $_POST["staff_id"], $fn, $ln, $bd, $_POST["division"], $_POST["position"], $_POST["staff_id"], $fn, $ln, $bd, $_POST["division"], $_POST["position"]]); }
            elseif ($new_role === "admin") { $pdo->prepare("INSERT INTO admins (user_id, admin_id, first_name, last_name) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE first_name=?, last_name=?")->execute([$uid, $_POST["admin_id"], $fn, $ln, $fn, $ln]); }
            $pdo->commit(); $message = "Data user berhasil diperbarui."; $message_type = "success";
        }
    } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $message = "Error: " . $e->getMessage(); $message_type = "error"; }
}
$search = $_GET["q"] ?? "";
try {
    $sql = "SELECT u.id, u.username, u.role, COALESCE(CONCAT(a.first_name, " ", a.last_name), CONCAT(s.first_name, " ", s.last_name), CONCAT(l.first_name, " ", l.last_name), CONCAT(st.first_name, " ", st.last_name), u.username) AS full_name, COALESCE(a.first_name, s.first_name, l.first_name, st.first_name, "") AS first_name, COALESCE(a.last_name, s.last_name, l.last_name, st.last_name, "") AS last_name, COALESCE(a.admin_id, s.staff_id, l.nip, st.nim, "") AS external_id, CASE WHEN u.role = "student" THEN CONCAT("Prodi: ", st.study_program) WHEN u.role = "lecturer" THEN CONCAT("KDK: ", l.expertise) WHEN u.role = "staff" THEN CONCAT("Divisi: ", s.division) WHEN u.role = "admin" THEN "Administrator" ELSE "" END AS detail, st.nim, st.study_program, st.cohort, l.nip, l.degree, l.expertise as kdk, s.staff_id, s.division, s.position, a.admin_id, COALESCE(st.birth_date, l.birth_date, s.birth_date, "2000-01-01") as birth_date FROM users u LEFT JOIN admins a ON a.user_id = u.id LEFT JOIN staff s ON s.user_id = u.id LEFT JOIN lecturers l ON l.user_id = u.id LEFT JOIN students st ON st.user_id = u.id";
    if ($search) $sql .= " WHERE u.username LIKE :q OR a.first_name LIKE :q OR a.last_name LIKE :q OR st.first_name LIKE :q OR st.last_name LIKE :q OR l.first_name LIKE :q OR l.last_name LIKE :q";
    $sql .= " ORDER BY u.role ASC, u.username ASC";
    $stmt = $pdo->prepare($sql); if ($search) $stmt->execute([";q" => "%$search%"]); else $stmt->execute(); $users = $stmt->fetchAll();
} catch (PDOException $e) {}
$base_path = "../../"; $page_title = "Kelola Pengguna - Admin"; $current_page = "manage_users";
include $base_path . "includes/header.php"; include $base_path . "includes/sidebar.php";
?>
<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
.modal-panel { background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 500px; }
.modal-body { display: flex; flex-direction: column; gap: 15px; max-height: 70vh; overflow-y: auto; }
.modal-body label { font-size: 0.85rem; font-weight: 600; display: flex; flex-direction: column; gap: 5px; color: #374151; }
.modal-body input, .modal-body select { padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.action-btn { padding: 6px 12px; border-radius: 6px; cursor: pointer; border: 1px solid #e5e7eb; background: white; }
.btn-save { background: #FF3B30; color: white; border: none; }
</style>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main style="padding: 24px;">
        <div class="content-card">
            <div class="card-top"><h3>Kelola Pengguna</h3><a href="provisions.php" style="text-decoration:none; color:#FF3B30; font-weight:600;">+ Tambah</a></div>
            <form action="" method="GET" style="margin: 20px 0; display: flex; gap: 10px;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari..." style="flex:1; padding: 10px; border:1px solid #e5e7eb; border-radius:8px;">
                <button type="submit" style="padding:10px 20px; background:#374151; color:white; border:none; border-radius:8px;">Cari</button>
            </form>
            <?php if (isset($message) && $message): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: <?= $message_type === "success" ? "#dcfce7" : "#fee2e2" ?>; color: <?= $message_type === "success" ? "#15803d" : "#b91c1c" ?>;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: #f9fafb; text-align: left;">
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">USERNAME</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">PERAN</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">NAMA</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">DETAIL</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($u["username"]) ?></td>
                                <td style="padding: 12px;"><?= strtoupper($u["role"]) ?></td>
                                <td style="padding: 12px;"><?= htmlspecialchars($u["full_name"]) ?></td>
                                <td style="padding: 12px; color: #6b7280;"><?= htmlspecialchars($u["external_id"]) ?> \u00b7 <?= htmlspecialchars($u["detail"]) ?></td>
                                <td style="padding: 12px; text-align: right;">
                                    <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)">Edit</button>
                                    <form action="" method="POST" style="display: inline;" onsubmit="return confirm("Hapus?")">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $u["id"] ?>">
                                        <button type="submit" class="action-btn" style="color:#b91c1c;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<div id="editModal" class="modal-overlay">
    <div class="modal-panel">
        <div class="modal-header"><h3>Edit Akun</h3><button onclick="closeModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button></div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update"><input type="hidden" name="user_id" id="edit_uid">
            <div class="modal-body">
                <label>Peran <select name="role" id="edit_role" onchange="toggleEditFields()"><option value="admin">Admin</option><option value="staff">Staff</option><option value="lecturer">Dosen</option><option value="student">Mahasiswa</option></select></label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;"><label>Nama Depan <input type="text" name="first_name" id="edit_fn"></label><label>Nama Belakang <input type="text" name="last_name" id="edit_ln"></label></div>
                <label>Password Baru <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"></label>
                <label>Tanggal Lahir <input type="date" name="birth_date" id="edit_bd"></label>
                <div id="edit_student" style="display:none; flex-direction:column; gap:10px;">
                    <label>NIM <input type="text" name="nim" id="edit_nim"></label><label>Prodi <input type="text" name="study_program" id="edit_sp"></label><label>Angkatan <input type="number" name="cohort" id="edit_ch"></label>
                </div>
                <div id="edit_lecturer" style="display:none; flex-direction:column; gap:10px;">
                    <label>NIP <input type="text" name="nip" id="edit_nip"></label><label>Gelar <input type="text" name="degree" id="edit_dg"></label><label>KDK <input type="text" name="kdk" id="edit_kdk"></label>
                </div>
                <div id="edit_staff" style="display:none; flex-direction:column; gap:10px;">
                    <label>Staff ID <input type="text" name="staff_id" id="edit_sid"></label><label>Divisi <input type="text" name="division" id="edit_dv"></label><label>Jabatan <input type="text" name="position" id="edit_ps"></label>
                </div>
                <div id="edit_admin" style="display:none;"><label>Admin ID <input type="text" name="admin_id" id="edit_aid"></label></div>
            </div>
            <div class="modal-actions"><button type="button" class="action-btn" onclick="closeModal()">Batal</button><button type="submit" class="action-btn btn-save">Simpan</button></div>
        </form>
    </div>
</div>
<script>
function openEditModal(u) {
    document.getElementById("edit_uid").value = u.id;
    document.getElementById("edit_role").value = u.role;
    document.getElementById("edit_fn").value = u.first_name;
    document.getElementById("edit_ln").value = u.last_name;
    document.getElementById("edit_bd").value = u.birth_date;
    document.getElementById("edit_nim").value = u.nim || "";
    document.getElementById("edit_sp").value = u.study_program || "";
    document.getElementById("edit_ch").value = u.cohort || "";
    document.getElementById("edit_nip").value = u.nip || "";
    document.getElementById("edit_dg").value = u.degree || "";
    document.getElementById("edit_kdk").value = u.kdk || "";
    document.getElementById("edit_sid").value = u.staff_id || "";
    document.getElementById("edit_dv").value = u.division || "";
    document.getElementById("edit_ps").value = u.position || "";
    document.getElementById("edit_aid").value = u.admin_id || "";
    toggleEditFields();
    document.getElementById("editModal").style.display = "flex";
}
function closeModal() { document.getElementById("editModal").style.display = "none"; }
function toggleEditFields() {
    const role = document.getElementById("edit_role").value;
    document.getElementById("edit_student").style.display = role === "student" ? "flex" : "none";
    document.getElementById("edit_lecturer").style.display = role === "lecturer" ? "flex" : "none";
    document.getElementById("edit_staff").style.display = role === "staff" ? "flex" : "none";
    document.getElementById("edit_admin").style.display = role === "admin" ? "flex" : "none";
}
</script>
<?php include $base_path . "includes/footer.php"; ?>
