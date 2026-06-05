<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_data = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

// Fetch detailed student profile
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM students WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $student_data = $stmt->fetch();
    if ($student_data) {
        $nim = $student_data["nim"] ?? $nim;
    }
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat profil mahasiswa.";
    error_log($e->getMessage());
}

// Fetch academic stats
$stats = null;
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM student_academic_stats WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Helper for initials and names
$student_name = $student_data ? ($student_data["first_name"] . " " . $student_data["last_name"]) : $nim;
$initials = "";
if ($student_data) {
    $initials = strtoupper(
        substr($student_data["first_name"] ?? "", 0, 1) . substr($student_data["last_name"] ?? "", 0, 1),
    );
} elseif (!empty($nim)) {
    $initials = strtoupper(substr($nim, 0, 2));
}

$unread_count = 0; // Placeholder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil Mahasiswa - INSPIRE Lite</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="dashboard-page">

    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
            <h2>INSPIRE LITE</h2>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-category">MAIN MENU</div>
            <a href="index.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            <a href="profile.php" class="menu-item active">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg> PROFIL
            </a>

            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>PUSAT INFORMASI</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="announcements.php" class="sub-menu-link"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg> Pengumuman</a>
            </div>

            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                <span>PERKULIAHAN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="jadwal.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Jadwal Kuliah
                </a>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="navbar">
            <button class="menu-toggle-hamburger" id="hamburgerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            <div class="page-title-header">
                <h1>Profil Mahasiswa</h1>
            </div>
            <div class="user-panel">
                <div class="account-interaction-wrapper">
                    <div class="profile-clickable-zone">
                        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                        <div class="user-info-text pc-only">
                            <p class="user-name"><?= htmlspecialchars($student_name) ?></p>
                            <p class="user-role">Mahasiswa</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="content-body">
            <?php if (!empty($data_errors)): ?>
                <?php foreach ($data_errors as $error): ?>
                    <div class="error-banner" style="margin-bottom: 20px;">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="profile-grid">
                <div class="content-card profile-main-card">
                    <div class="profile-header-strip">
                        <div class="profile-avatar-large"><?= htmlspecialchars($initials) ?></div>
                        <div class="profile-title-group">
                            <h2><?= htmlspecialchars($student_name) ?></h2>
                            <p class="profile-subtitle"><?= htmlspecialchars($nim) ?> · <?= htmlspecialchars($student_data["study_program"] ?? "-") ?></p>
                        </div>
                    </div>

                    <div class="info-sections-container">
                        <div class="info-section">
                            <h4>BIODATA DIRI</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>NAMA LENGKAP</label>
                                    <p><?= htmlspecialchars($student_name) ?></p>
                                </div>
                                <div class="info-item">
                                    <label>NOMOR INDUK MAHASISWA (NIM)</label>
                                    <p><?= htmlspecialchars($nim) ?></p>
                                </div>
                                <div class="info-item">
                                    <label>PROGRAM STUDI</label>
                                    <p><?= htmlspecialchars($student_data["study_program"] ?? "-") ?></p>
                                </div>
                                <div class="info-item">
                                    <label>ANGKATAN</label>
                                    <p><?= htmlspecialchars($student_data["cohort"] ?? "-") ?></p>
                                </div>
                                <div class="info-item">
                                    <label>TANGGAL LAHIR</label>
                                    <p><?= htmlspecialchars($student_data["birth_date"] ?? "-") ?></p>
                                </div>
                                <div class="info-item">
                                    <label>STATUS MAHASISWA</label>
                                    <p><span class="badge-active">Aktif</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card stats-summary-card">
                    <h3>Ringkasan Akademik</h3>
                    <div class="stats-list">
                        <div class="stat-node">
                            <span class="stat-label">IPK KUMULATIF</span>
                            <span class="stat-value"><?= htmlspecialchars($stats["ipk_kumulatif"] ?? "0.00") ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="stat-label">SKS DITEMPUH</span>
                            <span class="stat-value"><?= htmlspecialchars($stats["sks_ditempuh"] ?? "0") ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="stat-label">IP SEMESTER</span>
                            <span class="stat-value"><?= htmlspecialchars($stats["ip_semester"] ?? "0.00") ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="stat-label">SKS SEMESTER</span>
                            <span class="stat-value"><?= htmlspecialchars($stats["sks_semester"] ?? "0") ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 24px;
        }
        .profile-header-strip {
            display: flex;
            align-items: center;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
        }
        .profile-title-group h2 {
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .profile-subtitle {
            color: var(--muted-foreground);
            font-size: 0.9rem;
        }
        .info-section h4 {
            font-size: 0.75rem;
            color: var(--primary);
            letter-spacing: 0.1em;
            margin-bottom: 16px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--muted-foreground);
            margin-bottom: 4px;
        }
        .info-item p {
            font-weight: 600;
            color: #1f2937;
        }
        .badge-active {
            background-color: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 20px;
        }
        .stat-node {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .stat-node:last-child {
            border-bottom: none;
        }
        .stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--muted-foreground);
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }
        @media (max-width: 992px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</body>
</html>
