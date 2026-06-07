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

$student_name = $student_data ? ($student_data["first_name"] . " " . $student_data["last_name"]) : $nim;
$initials = "";
if ($student_data) {
    $initials = strtoupper(
        substr($student_data["first_name"] ?? "", 0, 1) . substr($student_data["last_name"] ?? "", 0, 1),
    );
} elseif (!empty($nim)) {
    $initials = strtoupper(substr($nim, 0, 2));
}

$unread_count = 0;
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
        <!-- Navbar: title left, user info right -->
        <header class="navbar">
            <button class="menu-toggle-hamburger" id="hamburgerBtn">
                <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            <div class="pf-navbar-title">
                <h1>Profil Mahasiswa</h1>
            </div>
            <div class="user-panel" style="margin-left:auto;">
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
                    <div class="error-banner" style="margin-bottom:20px;">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>        
            <?php endif; ?>

            <!-- Hero Card -->
            <div class="content-card pf-hero-card">
                <div class="pf-hero-inner">
                    <div class="pf-avatar-xl"><?= htmlspecialchars($initials) ?></div>
                    <div class="pf-hero-info">
                        <div class="pf-hero-name"><?= htmlspecialchars($student_name) ?></div>
                        <div class="pf-hero-meta">
                            <span class="pf-chip">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                                <?= htmlspecialchars($nim) ?>
                            </span>
                            <span class="pf-chip">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                                <?= htmlspecialchars($student_data["study_program"] ?? "-") ?>
                            </span>
                            <span class="pf-badge-active">
                                <span class="pf-badge-dot"></span>Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-column grid -->
            <div class="pf-two-col">

                <!-- Biodata -->
                <div class="content-card">
                    <div class="pf-section-header">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M9 11.75A1.25 1.25 0 1 0 9 14.25 1.25 1.25 0 0 0 9 11.75zM20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/></svg>
                        <span>Biodata Diri</span>
                    </div>
                    <div class="pf-biodata">
                        <div class="pf-row">
                            <span class="pf-row-label">Nama Lengkap</span>
                            <span class="pf-row-value"><?= htmlspecialchars($student_name) ?></span>
                        </div>
                        <div class="pf-row">
                            <span class="pf-row-label">NIM</span>
                            <span class="pf-row-value"><?= htmlspecialchars($nim) ?></span>
                        </div>
                        <div class="pf-row">
                            <span class="pf-row-label">Program Studi</span>
                            <span class="pf-row-value"><?= htmlspecialchars($student_data["study_program"] ?? "-") ?></span>
                        </div>
                        <div class="pf-row">
                            <span class="pf-row-label">Angkatan</span>
                            <span class="pf-row-value"><?= htmlspecialchars($student_data["cohort"] ?? "-") ?></span>
                        </div>
                        <div class="pf-row">
                            <span class="pf-row-label">Tanggal Lahir</span>
                            <span class="pf-row-value"><?= htmlspecialchars($student_data["birth_date"] ?? "-") ?></span>
                        </div>
                        <div class="pf-row pf-row--last">
                            <span class="pf-row-label">Status</span>
                            <span class="pf-row-value">
                                <span class="pf-status-badge">Aktif</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Academic Stats -->
                <div class="content-card">
                    <div class="pf-section-header">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                        <span>Ringkasan Akademik</span>
                    </div>
                    <div class="pf-stats-grid">
                        <div class="pf-stat-tile pf-stat-tile--accent">
                            <div class="pf-stat-value"><?= htmlspecialchars($stats["ipk_kumulatif"] ?? "0.00") ?></div>
                            <div class="pf-stat-label">IPK Kumulatif</div>
                        </div>
                        <div class="pf-stat-tile">
                            <div class="pf-stat-value"><?= htmlspecialchars($stats["sks_ditempuh"] ?? "0") ?></div>
                            <div class="pf-stat-label">SKS Ditempuh</div>
                        </div>
                        <div class="pf-stat-tile">
                            <div class="pf-stat-value"><?= htmlspecialchars($stats["ip_semester"] ?? "0.00") ?></div>
                            <div class="pf-stat-label">IP Semester</div>
                        </div>
                        <div class="pf-stat-tile">
                            <div class="pf-stat-value"><?= htmlspecialchars($stats["sks_semester"] ?? "0") ?></div>
                            <div class="pf-stat-label">SKS Semester</div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <style>
        /* ── Navbar title ──────────────────────────────────── */
        .pf-navbar-title {
            flex: 1;
        }
        .pf-navbar-title h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
        }
        .user-name { color: #ffffff; font-size: 0.85rem; font-weight: 700; }
        .user-role { color: rgba(255,255,255,0.7); font-size: 0.72rem; }

        /* ── Hero card ─────────────────────────────────────── */
        .pf-hero-card {
            margin-bottom: 20px;
            padding: 24px 28px;
        }
        .pf-hero-inner {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .pf-avatar-xl {
            width: 64px;
            height: 64px;
            min-width: 64px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .pf-hero-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pf-hero-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--charcoal);
            letter-spacing: -0.01em;
        }
        .pf-hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
        }
        .pf-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .pf-badge-active {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #059669;
            font-size: 0.73rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .pf-badge-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
        }

        /* ── Two-column ────────────────────────────────────── */
        .pf-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ── Section header ────────────────────────────────── */
        .pf-section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 4px;
        }
        .pf-section-header svg { fill: var(--primary); flex-shrink: 0; }
        .pf-section-header span {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--charcoal);
        }

        /* ── Biodata rows ──────────────────────────────────── */
        .pf-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            gap: 16px;
        }
        .pf-row--last { border-bottom: none; }
        .pf-row-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #9ca3af;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .pf-row-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--charcoal);
            text-align: right;
        }
        .pf-status-badge {
            display: inline-block;
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #059669;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 5px;
        }

        /* ── Stats grid ────────────────────────────────────── */
        .pf-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 4px;
        }
        .pf-stat-tile {
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .pf-stat-tile--accent {
            background: #fff0f0;
            border-color: #fecaca;
        }
        .pf-stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--charcoal);
            letter-spacing: -0.02em;
            line-height: 1;
        }
        .pf-stat-tile--accent .pf-stat-value { color: var(--primary); }
        .pf-stat-label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #9ca3af;
        }

        /* ── Responsive ────────────────────────────────────── */
        @media (max-width: 900px) {
            .pf-two-col { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .pf-hero-card { padding: 18px; }
            .pf-avatar-xl { width: 52px; height: 52px; min-width: 52px; font-size: 1.1rem; }
            .pf-hero-name { font-size: 1rem; }
        }
    </style>
</body>
</html>