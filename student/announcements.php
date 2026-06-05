<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$student_name = null;
$nim = $_SESSION["username"] ?? "";
$data_errors = [];

// Fetch student profile data
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
    error_log($e->getMessage());
}

// Fetch all announcements
$announcements = [];
try {
    $stmt = $pdo->query(
        "SELECT * FROM announcements ORDER BY id DESC"
    );
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $data_errors[] = "Gagal memuat pengumuman.";
    error_log($e->getMessage());
}

// Helper for initials and names
$display_name = $student_name ?: $nim;
$initials = "";
if (!empty($student_name)) {
    $name_parts = preg_split("/\s+/", trim($student_name));
    $initials = strtoupper(
        substr($name_parts[0] ?? "", 0, 1) . substr($name_parts[1] ?? "", 0, 1),
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
    <title>Pengumuman - INSPIRE Lite</title>
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
            <a href="profile.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg> PROFIL
            </a>

            <div class="menu-category-toggle expanded" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                <span>PUSAT INFORMASI</span>
                <svg class="arrow down" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: flex;">
                <a href="announcements.php" class="sub-menu-link active">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg> Pengumuman
                </a>
                <a href="news.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg> Berita
                </a>
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
                <h1>Pusat Pengumuman</h1>
            </div>
            <div class="user-panel">
                <div class="account-interaction-wrapper">
                    <div class="profile-clickable-zone">
                        <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
                        <div class="user-info-text pc-only">
                            <p class="user-name"><?= htmlspecialchars($display_name) ?></p>
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

            <div class="announcements-stack">
                <?php if (empty($announcements)): ?>
                    <div class="content-card text-center py-5">
                        <p class="text-muted">Tidak ada pengumuman saat ini.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="content-card announcement-card">
                            <div class="announcement-header">
                                <span class="badge-type <?= htmlspecialchars(strtolower($a["badge_class"])) ?>">
                                    <?= htmlspecialchars($a["type"]) ?>
                                </span>
                                <span class="announcement-date"><?= htmlspecialchars($a["date_text"]) ?></span>
                            </div>
                            <h2 class="announcement-title"><?= htmlspecialchars($a["title"]) ?></h2>
                            <div class="announcement-content">
                                <?= nl2br(htmlspecialchars($a["content"])) ?>
                            </div>
                            <div class="announcement-footer">
                                <span class="author-label">Diterbitkan oleh:</span>
                                <span class="author-name"><?= htmlspecialchars($a["author"]) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .announcements-stack {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .announcement-card {
            padding: 24px;
            transition: transform 0.2s;
        }
        .announcement-card:hover {
            transform: translateY(-2px);
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .badge-type {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-type.red { background-color: #fee2e2; color: #ef4444; }
        .badge-type.blue { background-color: #dbeafe; color: #3b82f6; }
        .badge-type.green { background-color: #d1fae5; color: #10b981; }

        .announcement-date {
            font-size: 0.8rem;
            color: var(--muted-foreground);
        }
        .announcement-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #111827;
        }
        .announcement-content {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 20px;
        }
        .announcement-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
        }
        .author-label { color: var(--muted-foreground); }
        .author-name { font-weight: 600; color: #1f2937; }
    </style>
</body>
</html>
