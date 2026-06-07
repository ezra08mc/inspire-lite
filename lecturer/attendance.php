<?php
require_once "../config/db.php";
require_once "_layout.php";

// database.sql: no attendance table defined.
// This page will provide a placeholder CRUD UI only if table exists; for now, view-only + form does not write.

$notice = null;
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Fitur absensi belum memiliki tabel pada database.sql. UI hanya demo.';
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Absensi</h1>
            <p>Pengelolaan kehadiran (belum tersedia tabel di database.sql).</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 980px; margin: 0 auto;">
        <div class="card-top">
            <h3>Marking Absensi</h3>
            <a href="dashboard.php" class="action-link">Kembali</a>
        </div>

        <?php if ($success): ?>
            <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="padding: 16px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display:block;">
                    <span>Kode MK</span>
                    <input required name="kode_mk" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
                <label style="display:block;">
                    <span>Tanggal</span>
                    <input required type="date" name="tanggal" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
                <span style="font-weight:600;">Aksi</span>
                <button class="trigger-btn btn-blue" type="submit" style="border:none;">Simpan Absensi</button>
            </div>
        </form>
    </div>
</main>

</div>
</body>
</html>

