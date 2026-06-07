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

            <!-- Hero Card -->
            <div class="profile-hero-card content-card">
                <div class="profile-hero-inner">
                    <div class="profile-avatar-xl"><?= htmlspecialchars($initials) ?></div>
                    <div class="profile-hero-info">
                        <div class="profile-hero-name"><?= htmlspecialchars($student_name) ?></div>
                        <div class="profile-hero-meta">
                            <span class="profile-meta-chip">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                                <?= htmlspecialchars($nim) ?>
                            </span>
                            <span class="profile-meta-chip">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                                <?= htmlspecialchars($student_data["study_program"] ?? "-") ?>
                            </span>
                            <span class="badge-active-hero">
                                <span class="badge-dot"></span>
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="profile-two-col">

                <!-- Left: Biodata -->
                <div class="content-card profile-biodata-card">
                    <div class="card-section-header">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8l8 5 8-5v10zm-8-7L4 6h16l-8 5z"/></svg>
                        <h3>Biodata Diri</h3>
                    </div>

                    <div class="biodata-rows">
                        <div class="biodata-row">
                            <span class="biodata-label">Nama Lengkap</span>
                            <span class="biodata-value"><?= htmlspecialchars($student_name) ?></span>
                        </div>
                        <div class="biodata-row">
                            <span class="biodata-label">NIM</span>
                            <span class="biodata-value"><?= htmlspecialchars($nim) ?></span>
                        </div>
                        <div class="biodata-row">
                            <span class="biodata-label">Program Studi</span>
                            <span class="biodata-value"><?= htmlspecialchars($student_data["study_program"] ?? "-") ?></span>
                        </div>
                        <div class="biodata-row">
                            <span class="biodata-label">Angkatan</span>
                            <span class="biodata-value"><?= htmlspecialchars($student_data["cohort"] ?? "-") ?></span>
                        </div>
                        <div class="biodata-row">
                            <span class="biodata-label">Tanggal Lahir</span>
                            <span class="biodata-value"><?= htmlspecialchars($student_data["birth_date"] ?? "-") ?></span>
                        </div>
                        <div class="biodata-row last">
                            <span class="biodata-label">Status</span>
                            <span class="biodata-value">
                                <span class="badge-active-inline">Aktif</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Academic Summary -->
                <div class="content-card profile-stats-card">
                    <div class="card-section-header">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                        <h3>Ringkasan Akademik</h3>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-tile stat-tile--primary">
                            <div class="stat-tile-value"><?= htmlspecialchars($stats["ipk_kumulatif"] ?? "0.00") ?></div>
                            <div class="stat-tile-label">IPK Kumulatif</div>
                        </div>
                        <div class="stat-tile">
                            <div class="stat-tile-value"><?= htmlspecialchars($stats["sks_ditempuh"] ?? "0") ?></div>
                            <div class="stat-tile-label">SKS Ditempuh</div>
                        </div>
                        <div class="stat-tile">
                            <div class="stat-tile-value"><?= htmlspecialchars($stats["ip_semester"] ?? "0.00") ?></div>
                            <div class="stat-tile-label">IP Semester</div>
                        </div>
                        <div class="stat-tile">
                            <div class="stat-tile-value"><?= htmlspecialchars($stats["sks_semester"] ?? "0") ?></div>
                            <div class="stat-tile-label">SKS Semester</div>
                        </div>
                    </div>
                </div>

            </div><!-- /.profile-two-col -->
        </main>
    </div>

    <style>
        /* ── Hero Card ─────────────────────────────────────── */
        .profile-hero-card {
            margin-bottom: 20px;
            padding: 28px 32px;
        }
        .profile-hero-inner {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .profile-avatar-xl {
            width: 72px;
            height: 72px;
            min-width: 72px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .profile-hero-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .profile-hero-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }
        .profile-hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .profile-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.75);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.01em;
        }
        .badge-active-hero {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.35);
            color: #34d399;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.03em;
        }
        .badge-dot {
            width: 6px;
            height: 6px;
            background: #34d399;
            border-radius: 50%;
            display: inline-block;
        }

        /* ── Two-column layout ─────────────────────────────── */
        .profile-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ── Section header inside cards ───────────────────── */
        .card-section-header {
            display: flex;
            align-items: center;
            gap: 9px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .card-section-header svg {
            fill: var(--primary);
            opacity: 0.9;
            flex-shrink: 0;
        }
        .card-section-header h3 {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.85);
        }

        /* ── Biodata rows ──────────────────────────────────── */
        .biodata-rows {
            display: flex;
            flex-direction: column;
        }
        .biodata-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
            gap: 12px;
        }
        .biodata-row.last {
            border-bottom: none;
            padding-bottom: 0;
        }
        .biodata-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.45);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .biodata-value {
            font-size: 0.88rem;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            text-align: right;
        }
        .badge-active-inline {
            display: inline-block;
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.35);
            color: #34d399;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 5px;
            letter-spacing: 0.04em;
        }

        /* ── Stats grid ────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .stat-tile {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 18px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: background 0.15s;
        }
        .stat-tile:hover {
            background: rgba(255,255,255,0.08);
        }
        .stat-tile--primary {
            background: rgba(255,59,48,0.12);
            border-color: rgba(255,59,48,0.25);
        }
        .stat-tile--primary:hover {
            background: rgba(255,59,48,0.18);
        }
        .stat-tile-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1;
        }
        .stat-tile--primary .stat-tile-value {
            color: var(--primary);
        }
        .stat-tile-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: rgba(255,255,255,0.45);
        }

        /* ── Responsive ────────────────────────────────────── */
        @media (max-width: 900px) {
            .profile-two-col {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 560px) {
            .profile-hero-card { padding: 20px; }
            .profile-avatar-xl { width: 56px; height: 56px; min-width: 56px; font-size: 1.2rem; }
            .profile-hero-name { font-size: 1.1rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</body>
</html>