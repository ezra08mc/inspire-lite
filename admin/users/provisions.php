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
    $stmt = $pdo->prepare("SELECT admin_id, first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name = trim($admin_data['first_name'] . ' ' . $admin_data['last_name']);
        $admin_id = $admin_data['admin_id'];
    }
} catch (PDOException $e) {}

$initials = "AD";
if (!empty($admin_name)) {
    $parts = explode(" ", $admin_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ""));
}
$display_name = $admin_name ?: "Administrator";

$message = ''; $message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    if (empty($username) || empty($password) || empty($role) || empty($first_name) || empty($last_name)) {
        $message = 'Semua field wajib diisi.'; $message_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) throw new Exception('Username sudah terdaftar.');

            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->execute([':username' => $username, ':password' => password_hash($password, PASSWORD_BCRYPT), ':role' => $role]);
            $new_user_id = $pdo->lastInsertId();

            if ($role === 'admin') {
                $stmt = $pdo->prepare("INSERT INTO admins (admin_id, user_id, first_name, last_name) VALUES (:aid, :uid, :fn, :ln)");
                $stmt->execute([':aid' => 'ADM-'.str_pad($new_user_id, 2, '0', STR_PAD_LEFT), ':uid' => $new_user_id, ':fn' => $first_name, ':ln' => $last_name]);
            } elseif ($role === 'staff') {
                $stmt = $pdo->prepare("INSERT INTO staff (staff_id, user_id, first_name, last_name, birth_date, division, position) VALUES (:sid, :uid, :fn, :ln, :bd, :dv, :ps)");
                $stmt->execute([':sid' => 'STF-'.str_pad($new_user_id, 2, '0', STR_PAD_LEFT), ':uid' => $new_user_id, ':fn' => $first_name, ':ln' => $last_name, ':bd' => $_POST['birth_date'] ?? '2000-01-01', ':dv' => $_POST['division'] ?? 'Umum', ':ps' => $_POST['position'] ?? 'Staff']);
            } elseif ($role === 'lecturer') {
                $nip = trim($_POST['nip'] ?? '');
                if (empty($nip)) throw new Exception('NIP wajib diisi untuk dosen.');
                $stmt = $pdo->prepare("INSERT INTO lecturers (nip, user_id, first_name, last_name, birth_date, degree, expertise) VALUES (:nip, :uid, :fn, :ln, :bd, :dg, :ex)");
                $stmt->execute([':nip' => $nip, ':uid' => $new_user_id, ':fn' => $first_name, ':ln' => $last_name, ':bd' => $_POST['birth_date'] ?? '2000-01-01', ':dg' => $_POST['degree'] ?? 'S1', ':ex' => $_POST['kdk'] ?? 'Umum']);
            } elseif ($role === 'student') {
                $nim = trim($_POST['nim'] ?? '');
                if (empty($nim)) throw new Exception('NIM wajib diisi untuk mahasiswa.');
                $stmt = $pdo->prepare("INSERT INTO students (nim, user_id, first_name, last_name, birth_date, study_program, cohort) VALUES (:nim, :uid, :fn, :ln, :bd, :sp, :ch)");
                $stmt->execute([':nim' => $nim, ':uid' => $new_user_id, ':fn' => $first_name, ':ln' => $last_name, ':bd' => $_POST['birth_date'] ?? '2000-01-01', ':sp' => $_POST['study_program'] ?? 'Teknik Informatika', ':ch' => $_POST['cohort'] ?? '2026']);
            }
            $pdo->commit();
            $message = 'Akun berhasil dibuat. Username: ' . $username; $message_type = 'success';
        } catch (Exception $e) { $pdo->rollBack(); $message = 'Error: ' . $e->getMessage(); $message_type = 'error'; }
    }
}

$base_path = "../../";
$page_title = "Penyediaan Akun - Admin";
$current_page = "provisions";
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <div class="content-card">
            <div class="card-top"><h3>Form Penyediaan Akun Baru</h3></div>
            <?php if ($message): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: <?= $message_type === 'success' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $message_type === 'success' ? '#15803d' : '#b91c1c' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <form action="" method="POST" style="display: grid; gap: 20px; max-width: 600px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <label style="display: flex; flex-direction: column; gap: 5px;">Username <input type="text" name="username" required style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Password <input type="password" name="password" required style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                </div>
                <label style="display: flex; flex-direction: column; gap: 5px;">Peran
                    <select name="role" id="roleSelect" onchange="toggleFields()" required style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="">-- Pilih Peran --</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="lecturer">Dosen</option>
                        <option value="student">Mahasiswa</option>
                    </select>
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <label style="display: flex; flex-direction: column; gap: 5px;">Nama Depan <input type="text" name="first_name" required style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Nama Belakang <input type="text" name="last_name" required style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                </div>
                <label style="display: flex; flex-direction: column; gap: 5px;">Tanggal Lahir <input type="date" name="birth_date" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                
                <div id="lecturerFields" style="display: none; gap: 20px; flex-direction: column;">
                    <label style="display: flex; flex-direction: column; gap: 5px;">NIP <input type="text" name="nip" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Gelar <input type="text" name="degree" placeholder="Contoh: S.Kom., M.T." style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">KDK (Kelompok Dosen Keahlian) <input type="text" name="kdk" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                </div>

                <div id="studentFields" style="display: none; gap: 20px; flex-direction: column;">
                    <label style="display: flex; flex-direction: column; gap: 5px;">NIM <input type="text" name="nim" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Program Studi <input type="text" name="study_program" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Angkatan <input type="number" name="cohort" value="<?= date('Y') ?>" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                </div>

                <div id="staffFields" style="display: none; gap: 20px; flex-direction: column;">
                    <label style="display: flex; flex-direction: column; gap: 5px;">Divisi <input type="text" name="division" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                    <label style="display: flex; flex-direction: column; gap: 5px;">Jabatan <input type="text" name="position" style="padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;"></label>
                </div>

                <button type="submit" style="padding: 12px; background: #FF3B30; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Buat Akun</button>
            </form>
        </div>
    </main>
</div>
<script>
function toggleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('lecturerFields').style.display = role === 'lecturer' ? 'flex' : 'none';
    document.getElementById('studentFields').style.display = role === 'student' ? 'flex' : 'none';
    document.getElementById('staffFields').style.display = role === 'staff' ? 'flex' : 'none';
}
</script>
<?php include $base_path . "includes/footer.php"; ?>
