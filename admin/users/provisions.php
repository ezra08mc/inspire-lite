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
} catch (PDOException $e) {
}

$initials = "AD";
if (!empty($admin_name)) {
    $name_parts = explode(' ', $admin_name);
    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
}

$notification_count = 0;
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT text_content, time_ago, is_read, icon_symbol FROM student_notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 5");
    $stmt->execute([':user_id' => $user_id]);
    $notifications = $stmt->fetchAll();
    $notification_count = count(array_filter($notifications, function ($item) {
        return !$item['is_read'];
    }));
} catch (PDOException $e) {
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    if (empty($username) || empty($password) || empty($role) || empty($first_name) || empty($last_name)) {
        $message = 'Semua field wajib diisi.';
        $message_type = 'error';
    } else if (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter.';
        $message_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                throw new Exception('Username sudah terdaftar.');
            }

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at) VALUES (:username, :password, :role, NOW())");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password,
                ':role' => $role
            ]);

            $new_user_id = $pdo->lastInsertId();

            if ($role === 'admin') {
                $admin_id_new = 'ADM-' . str_pad($new_user_id, 2, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO admins (admin_id, user_id, first_name, last_name) VALUES (:admin_id, :user_id, :first_name, :last_name)");
                $stmt->execute([
                    ':admin_id' => $admin_id_new,
                    ':user_id' => $new_user_id,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name
                ]);
            } elseif ($role === 'staff') {
                $staff_id_new = 'STF-' . str_pad($new_user_id, 2, '0', STR_PAD_LEFT);
                $division = $_POST['division'] ?? 'Umum';
                $position = $_POST['position'] ?? 'Staff';
                $birth_date = $_POST['birth_date'] ?? '2000-01-01';

                $stmt = $pdo->prepare("INSERT INTO staff (staff_id, user_id, first_name, last_name, birth_date, division, position) VALUES (:staff_id, :user_id, :first_name, :last_name, :birth_date, :division, :position)");
                $stmt->execute([
                    ':staff_id' => $staff_id_new,
                    ':user_id' => $new_user_id,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':birth_date' => $birth_date,
                    ':division' => $division,
                    ':position' => $position
                ]);
            } elseif ($role === 'lecturer') {
                $nip_new = 'NIP-' . str_pad($new_user_id, 4, '0', STR_PAD_LEFT);
                $degree = $_POST['degree'] ?? 'S1';
                $expertise = $_POST['expertise'] ?? 'Umum';
                $birth_date = $_POST['birth_date'] ?? '2000-01-01';

                $stmt = $pdo->prepare("INSERT INTO lecturers (nip, user_id, first_name, last_name, birth_date, degree, expertise) VALUES (:nip, :user_id, :first_name, :last_name, :birth_date, :degree, :expertise)");
                $stmt->execute([
                    ':nip' => $nip_new,
                    ':user_id' => $new_user_id,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':birth_date' => $birth_date,
                    ':degree' => $degree,
                    ':expertise' => $expertise
                ]);
            } elseif ($role === 'student') {
                $nim_new = trim($_POST['nim'] ?? '');
                if (empty($nim_new)) {
                    throw new Exception('NIM wajib diisi untuk mahasiswa.');
                }
                $study_program = $_POST['study_program'] ?? 'Teknik Informatika';
                $cohort = $_POST['cohort'] ?? '2026';
                $birth_date = $_POST['birth_date'] ?? '2006-01-01';

                $stmt = $pdo->prepare("INSERT INTO students (nim, user_id, first_name, last_name, birth_date, study_program, cohort) VALUES (:nim, :user_id, :first_name, :last_name, :birth_date, :study_program, :cohort)");
                $stmt->execute([
                    ':nim' => $nim_new,
                    ':user_id' => $new_user_id,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':birth_date' => $birth_date,
                    ':study_program' => $study_program,
                    ':cohort' => $cohort
                ]);
            }

            $pdo->commit();
            $message = 'Akun berhasil dibuat. Username: ' . htmlspecialchars($username);
            $message_type = 'success';
            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>INSPIRE LITE - Penyediaan Akun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #374151; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 0.75rem; font-family: inherit; font-size: 0.92rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row.full { grid-template-columns: 1fr; }
        .role-fields { margin-top: 16px; padding: 16px; background: #f9fafb; border-radius: 0.75rem; border: 1px solid var(--border); display: none; }
        .role-fields.active { display: block; }
        .btn-submit { padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.15s ease; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
        .alert { padding: 12px 16px; border-radius: 0.75rem; margin-bottom: 16px; font-size: 0.92rem; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
    <script>
        (function() {
            const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            if (width <= 768) {
                document.documentElement.classList.add('preload-collapsed');
            } else {
                document.documentElement.classList.add('preload-expanded');
            }
        })();
    </script>
    <script src="../../assets/js/main.js" defer></script>
</head>
<body class="dashboard-page">
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
            <h2>INSPIRE LITE</h2>
        </div>
        <nav class="sidebar-menu">
            <div class="menu-category">MAIN MENU</div>
            <a href="../dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            <div class="menu-category-toggle expanded" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <span>PENGELOLAAN PENGGUNA</span>
                <svg class="arrow down" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: flex;">
                <a href="manage.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2c1.66 0 3-1.34 3-3S7.66 4 6 4 3 5.34 3 7s1.34 3 3 3zm0 4c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm9 0c-.29 0-.62.02-.97.05 1.16.89 1.97 2.48 1.97 4.21v3h6v-3c0-2.66-4.05-4-4-4z"/></svg> Kelola Pengguna
                </a>
                <a href="provisions.php" class="sub-menu-link active-sub">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Penyediaan Akun <span class="nav-badge">0</span>
                </a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                <span>INFORMASI & KONTEN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="../announcements.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg> Pengumuman
                </a>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="navbar">
            <button class="menu-toggle-hamburger" id="hamburgerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            <div class="search-container">
                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" placeholder="Cari...">
            </div>
            <div class="user-panel">
                <div class="notification-wrapper">
                    <button class="notification-bell" id="notifBellBtn">
                        <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                        <?php if ($notification_count > 0): ?>
                            <span class="bell-badge"><?= $notification_count ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-panel-notif" id="notifDropdown">
                        <div class="dropdown-header">Notifikasi</div>
                        <div class="dropdown-list-container">
                            <?php if (empty($notifications)): ?>
                                <div class="empty-fallback-text">Tidak ada notifikasi aktif.</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $note): ?>
                                    <div class="dropdown-item-node <?= $note['is_read'] ? '' : 'unread' ?>">
                                        <div class="node-icon"><?= htmlspecialchars($note['icon_symbol']) ?></div>
                                        <div class="node-body">
                                            <p><?= htmlspecialchars($note['text_content']) ?></p>
                                            <span><?= htmlspecialchars($note['time_ago']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="account-interaction-wrapper pc-only-wrapper">
                    <div class="profile-clickable-zone" id="profileMenuBtn">
                        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                        <div class="user-meta">
                            <span class="user-name-text"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></span>
                            <span class="user-id-text"><?= htmlspecialchars($admin_id) ?></span>
                        </div>
                    </div>
                    <div class="dropdown-panel-account" id="accountDropdown">
                        <div class="dropdown-account-header">
                            <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                            <div>
                                <p class="head-title"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></p>
                                <p class="head-sub"><?= htmlspecialchars($admin_id) ?></p>
                            </div>
                        </div>
                        <a href="../../logout.php" class="account-drop-link logout">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-viewport">
            <section class="hero-banner">
                <div class="hero-title">
                    <h1>Penyediaan Akun Pengguna</h1>
                    <p>Buat akun baru untuk admin, staf, dosen, atau mahasiswa.</p>
                </div>
            </section>

            <div class="split-grid flex-equal-align">
                <div class="content-card">
                    <?php if (!empty($message)): ?>
                        <div class="alert <?= $message_type ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-top">
                        <h3>Form Pembuatan Akun</h3>
                    </div>

                    <form method="POST" style="margin-top: 16px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required minlength="6">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">Nama Depan *</label>
                                <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Nama Belakang *</label>
                                <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role">Tipe Pengguna *</label>
                            <select id="role" name="role" required onchange="toggleRoleFields()">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                <option value="staff" <?= ($_POST['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staf</option>
                                <option value="lecturer" <?= ($_POST['role'] ?? '') === 'lecturer' ? 'selected' : '' ?>>Dosen</option>
                                <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Mahasiswa</option>
                            </select>
                        </div>

                        <div id="staff-fields" class="role-fields <?= ($_POST['role'] ?? '') === 'staff' ? 'active' : '' ?>">
                            <h4 style="margin-top: 0; font-size: 0.95rem; color: #374151;">Data Staf</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="staff_birth_date">Tanggal Lahir</label>
                                    <input type="date" id="staff_birth_date" name="birth_date" value="<?= htmlspecialchars($_POST['birth_date'] ?? '2000-01-01') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="staff_division">Divisi</label>
                                    <input type="text" id="staff_division" name="division" value="<?= htmlspecialchars($_POST['division'] ?? 'Umum') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="staff_position">Posisi</label>
                                <input type="text" id="staff_position" name="position" value="<?= htmlspecialchars($_POST['position'] ?? 'Staff') ?>">
                            </div>
                        </div>

                        <div id="lecturer-fields" class="role-fields <?= ($_POST['role'] ?? '') === 'lecturer' ? 'active' : '' ?>">
                            <h4 style="margin-top: 0; font-size: 0.95rem; color: #374151;">Data Dosen</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="lecturer_birth_date">Tanggal Lahir</label>
                                    <input type="date" id="lecturer_birth_date" name="birth_date" value="<?= htmlspecialchars($_POST['birth_date'] ?? '2000-01-01') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="lecturer_degree">Gelar</label>
                                    <input type="text" id="lecturer_degree" name="degree" value="<?= htmlspecialchars($_POST['degree'] ?? 'S1') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="lecturer_expertise">Keahlian</label>
                                <input type="text" id="lecturer_expertise" name="expertise" value="<?= htmlspecialchars($_POST['expertise'] ?? 'Umum') ?>">
                            </div>
                        </div>

                        <div id="student-fields" class="role-fields <?= ($_POST['role'] ?? '') === 'student' ? 'active' : '' ?>">
                            <h4 style="margin-top: 0; font-size: 0.95rem; color: #374151;">Data Mahasiswa</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="student_nim">NIM *</label>
                                    <input type="text" id="student_nim" name="nim" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="student_birth_date">Tanggal Lahir *</label>
                                    <input type="date" id="student_birth_date" name="birth_date" value="<?= htmlspecialchars($_POST['birth_date'] ?? '2006-01-01') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="student_program">Program Studi</label>
                                    <input type="text" id="student_program" name="study_program" value="<?= htmlspecialchars($_POST['study_program'] ?? 'Teknik Informatika') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="student_cohort">Tahun Angkatan</label>
                                    <input type="text" id="student_cohort" name="cohort" value="<?= htmlspecialchars($_POST['cohort'] ?? '2026') ?>">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; display: flex; gap: 12px;">
                            <button type="submit" class="btn-submit" style="flex: 1;">Buat Akun</button>
                            <a href="manage.php" class="btn-submit" style="flex: 1; text-align: center; text-decoration: none; background: #6b7280;">Batal</a>
                        </div>
                    </form>
                </div>

                <div class="content-card">
                    <div class="card-top">
                        <h3>Panduan Pembuatan Akun</h3>
                    </div>
                    <div style="font-size: 0.92rem; line-height: 1.6; color: #4b5563;">
                        <h4 style="margin: 16px 0 8px; color: #111827;">Administrator</h4>
                        <p style="margin: 0 0 12px;">Akun admin dengan akses penuh ke sistem. Hanya memerlukan nama dan username.</p>

                        <h4 style="margin: 16px 0 8px; color: #111827;">Staf</h4>
                        <p style="margin: 0 0 12px;">Karyawan institusi. Memerlukan divisi, posisi, dan tanggal lahir.</p>

                        <h4 style="margin: 16px 0 8px; color: #111827;">Dosen</h4>
                        <p style="margin: 0 0 12px;">Pengajar. Memerlukan gelar, keahlian, dan tanggal lahir.</p>

                        <h4 style="margin: 16px 0 8px; color: #111827;">Mahasiswa</h4>
                        <p style="margin: 0 0 12px;">Peserta didik. Memerlukan NIM, tanggal lahir, program studi, dan tahun angkatan.</p>

                        <div style="background: #eff6ff; border-left: 4px solid #007bec; padding: 12px; margin-top: 16px; border-radius: 4px;">
                            <strong style="color: #007bec;">💡 Tips:</strong>
                            <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 0.88rem;">
                                <li>Password minimal 6 karakter</li>
                                <li>Username harus unik dalam sistem</li>
                                <li>Semua field bertanda * wajib diisi</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div class="mobile-exclusive-profile-flyout" id="mobileProfileFlyout">
            <div class="dropdown-account-header">
                <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <p class="head-title"><?= htmlspecialchars($admin_name ?: 'Administrator') ?></p>
                    <p class="head-sub"><?= htmlspecialchars($admin_id) ?></p>
                </div>
            </div>
            <div class="divider"></div>
            <a href="../../logout.php" class="account-drop-link logout">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
            </a>
        </div>

        <nav class="mobile-bottom-navigation-dock-bar">
            <a href="../dashboard.php" class="mobile-nav-tab">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                <span>Beranda</span>
            </a>
            <button class="mobile-nav-tab" id="mobileProfileTabBtn">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                <span>Profil</span>
            </button>
            <button class="mobile-nav-tab" id="mobileMenuTriggerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                <span>Menu</span>
            </button>
        </nav>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('staff-fields').classList.toggle('active', role === 'staff');
            document.getElementById('lecturer-fields').classList.toggle('active', role === 'lecturer');
            document.getElementById('student-fields').classList.toggle('active', role === 'student');
        }
    </script>
</body>
</html>
