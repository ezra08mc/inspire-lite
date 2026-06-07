<?php
require_once "../config/db.php";
require_once "_layout.php";

// database.sql: no advising/bimbingan table defined.
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = 'Fitur bimbingan belum memiliki tabel pada database.sql. UI hanya demo.';
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Bimbingan</h1>
            <p>Manajemen sesi bimbingan (belum tersedia tabel pada database.sql).</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 980px; margin: 0 auto;">
        <div class="card-top">
            <h3>Jadwalkan Sesi</h3>
            <a href="dashboard.php" class="action-link">Kembali</a>
        </div>

        <?php if ($msg): ?>
            <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="padding: 16px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display:block;">
                    <span>NIM Mahasiswa</span>
                    <input required name="nim" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
                <label style="display:block;">
                    <span>Tanggal</span>
                    <input required name="tanggal" type="date" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
            </div>
            <div style="margin-top:12px;">
                <label style="display:block;">
                    <span>Catatan</span>
                    <textarea name="catatan" rows="4" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;"></textarea>
                </label>
            </div>

            <div style="margin-top:16px; display:flex; gap: 10px;">
                <button type="submit" class="trigger-btn btn-blue" style="border:none;">Simpan</button>
                <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none;">Kembali</a>
            </div>
        </form>
    </div>
</main>

</div>
</body>
</html>

