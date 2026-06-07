<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int)($_SESSION['user_id'] ?? 0);

// database.sql: jadwal table exists but lecturer-course mapping is not defined.
// We'll show all jadwal entries.
$schedule = [];
try {
    $stmt = $pdo->query("SELECT kode_mk, nama_mata_kuliah, sks, kelas, dosen_pengampu, hari, tanggal, jam_mulai, jam_selesai, ruangan FROM jadwal ORDER BY tanggal ASC, jam_mulai ASC");
    $schedule = $stmt->fetchAll();
} catch (PDOException $e) {
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Jadwal Mengajar</h1>
            <p>Menampilkan jadwal perkuliahan dari tabel <code>jadwal</code>.</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 1100px; margin: 0 auto;">
        <div class="card-top">
            <h3>Daftar Jadwal</h3>
            <a href="dashboard.php" class="action-link">Kembali</a>
        </div>

        <div class="agenda-table-wrapper" style="margin-top: 10px;">
            <div class="agenda-table-header">
                <span class="col-head">MATA KULIAH</span>
                <span class="col-head">WAKTU</span>
                <span class="col-head">KELAS/RUANG</span>
                <span class="col-head">DOSEN</span>
            </div>

            <div class="agenda-rows-stack">
                <?php if (empty($schedule)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada jadwal.</div>
                <?php else: ?>
                    <?php foreach ($schedule as $row): ?>
                        <div class="agenda-table-row">
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline"><?= htmlspecialchars($row['nama_mata_kuliah']) ?></span>
                                <span class="item-sub-clock">Kode: <?= htmlspecialchars($row['kode_mk']) ?> · SKS <?= htmlspecialchars($row['sks']) ?></span>
                            </div>
                            <div class="col-cell cell-room-loc">
                                <span class="item-main-headline"><?= htmlspecialchars($row['hari']) ?></span>
                                <span class="item-sub-clock"><?= htmlspecialchars($row['tanggal']) ?> · <?= htmlspecialchars($row['jam_mulai']) ?>–<?= htmlspecialchars($row['jam_selesai']) ?></span>
                            </div>
                            <div class="col-cell cell-room-loc">
                                <span class="item-main-headline">Kelas <?= htmlspecialchars($row['kelas']) ?></span>
                                <span class="item-sub-clock"><?= htmlspecialchars($row['ruangan']) ?></span>
                            </div>
                            <div class="col-cell cell-room-loc">
                                <span class="item-main-headline"><?= htmlspecialchars($row['dosen_pengampu']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>

