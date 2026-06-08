<?php
session_start();
$base_path = "./";
$page_title = "404 - Halaman Tidak Ditemukan";
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 60vh;">
        <h1 style="font-size: 5rem; color: #FF3B30;">404</h1>
        <h2 style="font-size: 2rem; color: #1F2937;">Halaman Tidak Ditemukan</h2>
        <p style="color: #6b7280; margin: 20px 0;">Maaf, halaman yang Anda cari tidak tersedia.</p>
        <a href="index.php" style="padding: 12px 24px; background: #FF3B30; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Kembali ke Beranda</a>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
