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
$position = "Administrator";

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

$total_users = 0;
$stats = [
    'total_students' => 0,
    'total_lecturers' => 0,
    'total_staff' => 0,
    'total_admins' => 0,
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    $total_users = $result['count'];
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $result = $stmt->fetch();
    $stats['total_students'] = $result['count'];
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lecturers");
    $result = $stmt->fetch();
    $stats['total_lecturers'] = $result['count'];
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
    $result = $stmt->fetch();
    $stats['total_staff'] = $result['count'];
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    $stats['total_admins'] = $result['count'];
} catch (PDOException $e) {
}

$users = [];
try {
    $query = "SELECT u.id, u.username, u.role,
        COALESCE(CONCAT(a.first_name, ' ', a.last_name), CONCAT(s.first_name, ' ', s.last_name), CONCAT(l.first_name, ' ', l.last_name), CONCAT(st.first_name, ' ', st.last_name), u.username) AS full_name,
        CASE
            WHEN u.role = 'admin' THEN a.admin_id
            WHEN u.role = 'staff' THEN s.staff_id
            WHEN u.role = 'lecturer' THEN l.nip
            WHEN u.role = 'student' THEN st.nim
            ELSE ''
        END AS external_id,
        CASE
            WHEN u.role = 'student' THEN CONCAT('Prodi: ', st.study_program, ' - ', st.cohort)
            WHEN u.role = 'lecturer' THEN CONCAT('Gelar: ', l.degree, ' - ', l.expertise)
            WHEN u.role = 'staff' THEN CONCAT('Divisi: ', s.division, ' - ', s.position)
            WHEN u.role = 'admin' THEN 'Administrator'
            ELSE ''
        END AS detail
        FROM users u
        LEFT JOIN admins a ON a.user_id = u.id
        LEFT JOIN staff s ON s.user_id = u.id
        LEFT JOIN lecturers l ON l.user_id = u.id
        LEFT JOIN students st ON st.user_id = u.id
        ORDER BY
            CASE u.role
                WHEN 'admin' THEN 1
                WHEN 'staff' THEN 2
                WHEN 'lecturer' THEN 3
                WHEN 'student' THEN 4
                ELSE 5
            END,
            u.username ASC";
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
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

$initials = "AD";
if (!empty($admin_name)) {
    $name_parts = explode(' ', $admin_name);
    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>INSPIRE LITE - Kelola Pengguna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .action-btn {
            background: transparent;
            border: 1px solid rgba(55, 65, 81, 0.16);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.78rem;
            color: #1f2937;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .action-btn:hover {
            background-color: rgba(15, 23, 42, 0.05);
        }
        .edit-btn {
            border-color: #22c55e;
            color: #15803d;
        }
        .edit-btn:hover {
            background-color: rgba(16, 185, 129, 0.12);
        }
        .delete-btn {
            border-color: #ef4444;
            color: #b91c1c;
        }
        .delete-btn:hover {
            background-color: rgba(239, 68, 68, 0.12);
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        .modal-panel {
            width: min(560px, 100%);
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #111827;
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            color: #6b7280;
            cursor: pointer;
        }
        .modal-body {
            display: grid;
            gap: 14px;
            padding: 20px 24px;
        }
        .modal-body label {
            display: grid;
            gap: 8px;
            font-size: 0.85rem;
            color: #374151;
        }
        .modal-body input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 10px 12px;
            color: #111827;
            background: #f8fafc;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px 24px;
        }
        .cancel-btn {
            border-color: #9ca3af;
            color: #4b5563;
            background: #f3f4f6;
        }
        .save-btn {
            border-color: #2563eb;
            color: #1d4ed8;
            background: #eff6ff;
        }
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
    <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('userSearchInput');
                const counter = document.getElementById('userListCount');
                const noResults = document.getElementById('userSearchNoResults');
                const getRows = () => Array.from(document.querySelectorAll('.agenda-table-wrapper .agenda-table-row')).filter(row => row !== noResults);
                const editModal = document.getElementById('userEditModal');
                const editForm = document.getElementById('userEditForm');
                const closeEditModal = document.getElementById('closeEditModal');
                const cancelEdit = document.getElementById('cancelEdit');
                const editUsername = document.getElementById('editUsername');
                const editRole = document.getElementById('editRole');
                const editFullName = document.getElementById('editFullName');
                const editExternalId = document.getElementById('editExternalId');
                const editDetail = document.getElementById('editDetail');
                let activeRow = null;

                function updateCount() {
                    const visibleRows = getRows().filter(row => row.style.display !== 'none');
                    if (counter) {
                        counter.textContent = 'Menampilkan ' + visibleRows.length + ' akun';
                    }
                    if (noResults) {
                        noResults.style.display = visibleRows.length === 0 ? 'grid' : 'none';
                    }
                }

                function openEditModal(row) {
                    activeRow = row;
                    const data = row.dataset;
                    editUsername.value = data.username || '';
                    editRole.value = data.role || '';
                    editFullName.value = data.fullName || '';
                    editExternalId.value = data.externalId || '';
                    editDetail.value = data.detail || '';
                    if (editModal) {
                        editModal.style.display = 'flex';
                    }
                }

                function closeModal() {
                    if (editModal) {
                        editModal.style.display = 'none';
                    }
                    activeRow = null;
                }

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const query = this.value.trim().toLowerCase();
                        let visibleCount = 0;

const rows = getRows();
                    rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            const visible = query === '' || text.includes(query);
                            row.style.display = visible ? 'grid' : 'none';
                            if (visible) {
                                visibleCount += 1;
                            }
                        });

                        if (noResults) {
                            noResults.style.display = visibleCount === 0 ? 'grid' : 'none';
                        }
                        if (counter) {
                            counter.textContent = 'Menampilkan ' + visibleCount + ' akun';
                        }
                    });
                }

                document.querySelectorAll('.agenda-table-wrapper .action-btn').forEach(button => {
                    button.addEventListener('click', function(event) {
                        event.stopPropagation();
                        const row = this.closest('.agenda-table-row');
                        if (!row) return;
                        const action = this.dataset.action;
                        if (action === 'edit') {
                            openEditModal(row);
                        }
                        if (action === 'delete') {
                            if (confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')) {
                                row.remove();
                                updateCount();
                            }
                        }
                    });
                });

                if (closeEditModal) {
                    closeEditModal.addEventListener('click', closeModal);
                }
                if (cancelEdit) {
                    cancelEdit.addEventListener('click', closeModal);
                }
                if (editModal) {
                    editModal.addEventListener('click', function(event) {
                        if (event.target === editModal) {
                            closeModal();
                        }
                    });
                }
                if (editForm) {
                    editForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        if (!activeRow) return;
                        activeRow.dataset.username = editUsername.value;
                        activeRow.dataset.role = editRole.value;
                        activeRow.dataset.fullName = editFullName.value;
                        activeRow.dataset.externalId = editExternalId.value;
                        activeRow.dataset.detail = editDetail.value;

                        const cells = activeRow.querySelectorAll('.col-cell');
                        if (cells.length >= 4) {
                            cells[0].textContent = editUsername.value;
                            cells[1].textContent = editRole.value.toUpperCase();
                            cells[2].textContent = editFullName.value;
                            const external = editExternalId.value.trim() || '-';
                            const detail = editDetail.value.trim() ? ' - ' + editDetail.value.trim() : '';
                            cells[3].textContent = external + detail;
                        }
                        closeModal();
                    });
                }
            });
        </script>
</head>
<body class="dashboard-page">
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <svg class="sidebar-logo-svg" viewBox="0 0 24 24"><path d="M12 2L1 7l11 5 9-4.5V14h2V7L12 2zM5 11.18v3L12 18l7-3.82v-3L12 14l-7-2.82z"/></svg>
            <h2>INSPIRE LITE</h2>
        </div>
        <nav class="sidebar-menu">
            <div class="menu-category">MAIN MENU</div>
            <a href="dashboard.php" class="menu-item">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg> BERANDA
            </a>
            <div class="menu-category-toggle expanded" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <span>PENGELOLAAN PENGGUNA</span>
                <svg class="arrow down" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: flex;">
                <a href="manage.php" class="sub-menu-link active-sub">
                    <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2c1.66 0 3-1.34 3-3S7.66 4 6 4 3 5.34 3 7s1.34 3 3 3zm0 4c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm9 0c-.29 0-.62.02-.97.05 1.16.89 1.97 2.48 1.97 4.21v3h6v-3c0-2.66-4.05-4-4-4z"/></svg> Kelola Pengguna
                </a>
                <a href="provisions.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg> Penyediaan Akun <span class="nav-badge">0</span>
                </a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                <span>INFORMASI & KONTEN</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="announcements.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1.5 9c-.83 0-1.5-.67-1.5-1.5S17.67 8 18.5 8s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg> Pengumuman <span class="nav-badge"><?= $stats['total_announcements'] ?? 0 ?></span>
                </a>
                <a href="calendar.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Kalender Akademik
                </a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                <span>AKADEMIK</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="academic/enrollment.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> Data Pendaftaran
                </a>
                <a href="academic/grades.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1 2 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg> Nilai Akademik
                </a>
                <a href="academic/subjects.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg> Mata Kuliah
                </a>
            </div>
            <div class="menu-category-toggle" onclick="toggleSubmenu(this)">
                <svg viewBox="0 0 24 24"><path d="M12 1C5.92 1 1 5.92 1 12s4.92 11 11 11 11-4.92 11-11S18.08 1 12 1zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                <span>SISTEM</span>
                <svg class="arrow" viewBox="0 0 24 24"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            </div>
            <div class="submenu-items" style="display: none;">
                <a href="infrastructure/backup.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-6-11l-4 4h3v4h2v-4h3l-4-4z"/></svg> Backup Data
                </a>
                <a href="security/logs.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M12 1C5.92 1 1 5.92 1 12s4.92 11 11 11 11-4.92 11-11S18.08 1 12 1zm1 16h-2v2h2v-2zm0-14h-2v12h2V3z"/></svg> Log Sistem
                </a>
                <a href="settings/calendar.php" class="sub-menu-link">
                    <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Pengaturan
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
                <input type="text" placeholder="Cari pengguna, username, peran...">
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
                        <a href="../settings/profile.php" class="account-drop-link">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Profil Saya
                        </a>
                        <a href="../settings/preferences.php" class="account-drop-link">
                            <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Preferensi
                        </a>
                        <div class="divider"></div>
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
                    <h1>Kelola Pengguna</h1>
                    <p>Atur semua akun admin, staf, dosen, dan mahasiswa dari panel ini.</p>
                </div>
                <div class="metrics-row">
                    <div class="metric-card">
                        <span class="metric-label">TOTAL PENGGUNA</span>
                        <span class="metric-value"><?= $total_users ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">MAHASISWA</span>
                        <span class="metric-value"><?= $stats['total_students'] ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">DOSEN</span>
                        <span class="metric-value"><?= $stats['total_lecturers'] ?></span>
                    </div>
                    <div class="metric-card">
                        <span class="metric-label">STAF</span>
                        <span class="metric-value"><?= $stats['total_staff'] ?></span>
                    </div>
                </div>
            </section>

            <div class="content-card">
                <div class="card-top">
                    <div>
                        <h3>Daftar Akun</h3>
                        <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">Kelola akses dan lihat profil akun di sini.</p>
                    </div>
                    <a href="provisions.php" class="action-link">Tambah Pengguna ?</a>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
                    <div class="search-container" style="flex: 1; max-width: 420px;">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input id="userSearchInput" type="text" placeholder="Cari pengguna" style="width:100%; border:1px solid var(--border); border-radius: 0.75rem; padding: 10px 12px; font-size:0.92rem; background:#ffffff; color:#111827;">
                    </div>
                    <div id="userListCount" style="color: #4b5563; font-size: 0.88rem;">Menampilkan <?= count($users) ?> akun</div>
                </div>
                <div class="agenda-table-wrapper">
                    <div class="agenda-table-header" style="grid-template-columns: 160px 220px 1fr 1fr 150px;">
                        <span class="col-head">USERNAME</span>
                        <span class="col-head">PERAN</span>
                        <span class="col-head">NAMA</span>
                        <span class="col-head">DETAIL</span>
                        <span class="col-head">AKSI</span>
                    </div>
                    <?php if (empty($users)): ?>
                        <div class="agenda-table-row" style="grid-template-columns: 1fr;">
                            <div class="col-cell" style="padding: 18px 12px; color: #6b7280; text-align: center;">Tidak ada akun pengguna yang ditemukan.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <div class="agenda-table-row" style="grid-template-columns: 160px 220px 1fr 1fr 150px;" data-user-id="<?= htmlspecialchars($user['id']) ?>" data-username="<?= htmlspecialchars($user['username']) ?>" data-role="<?= htmlspecialchars($user['role']) ?>" data-full-name="<?= htmlspecialchars($user['full_name']) ?>" data-external-id="<?= htmlspecialchars($user['external_id']) ?>" data-detail="<?= htmlspecialchars($user['detail']) ?>">
                                <div class="col-cell" style="font-weight: 700; color: #111827;"><?= htmlspecialchars($user['username']) ?></div>
                                <div class="col-cell" style="text-transform: uppercase; letter-spacing: 0.04em; color: #374151; font-size: 0.82rem;"><?= htmlspecialchars($user['role']) ?></div>
                                <div class="col-cell" style="color: #111827;"><?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="col-cell" style="color: #4b5563; font-size: 0.92rem;"><?= htmlspecialchars($user['external_id'] ?: '-') ?><?= $user['detail'] ? ' - ' . htmlspecialchars($user['detail']) : '' ?></div>
                                <div class="col-cell" style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                    <button type="button" class="action-btn edit-btn" data-action="edit">Edit</button>
                                    <button type="button" class="action-btn delete-btn" data-action="delete">Hapus</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div id="userSearchNoResults" class="agenda-table-row" style="display:none; grid-template-columns: 1fr;">
                            <div class="col-cell" style="padding: 18px 12px; color: #6b7280; text-align: center;">Tidak ada akun yang cocok ditemukan.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="userEditModal" class="modal-overlay" style="display:none;">
                <div class="modal-panel">
                    <div class="modal-header">
                        <h3>Edit Akun</h3>
                        <button type="button" class="modal-close-btn" id="closeEditModal">×</button>
                    </div>
                    <form id="userEditForm">
                        <div class="modal-body">
                            <label>Username
                                <input id="editUsername" name="username" type="text" required>
                            </label>
                            <label>Peran
                                <input id="editRole" name="role" type="text" readonly>
                            </label>
                            <label>Nama Lengkap
                                <input id="editFullName" name="full_name" type="text" required>
                            </label>
                            <label>ID Eksternal
                                <input id="editExternalId" name="external_id" type="text">
                            </label>
                            <label>Detail
                                <input id="editDetail" name="detail" type="text">
                            </label>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="action-btn cancel-btn" id="cancelEdit">Batal</button>
                            <button type="submit" class="action-btn save-btn">Simpan</button>
                        </div>
                    </form>
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
            <a href="../settings/profile.php" class="account-drop-link">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg> Profil Saya
            </a>
            <a href="../settings/preferences.php" class="account-drop-link">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg> Preferensi
            </a>
            <div class="divider"></div>
            <a href="../../logout.php" class="account-drop-link logout">
                <svg viewBox="0 0 24 24" class="drop-link-icon"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg> Keluar
            </a>
        </div>

        <nav class="mobile-bottom-navigation-dock-bar">
            <a href="#" class="mobile-nav-tab active">
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
</body>
</html>
