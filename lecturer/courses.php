<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int)($_SESSION['user_id'] ?? 0);

// Schema notes: database.sql does not include lecturer-course mapping tables.
// We'll implement a minimal courses view from subjects.
$subjects = [];
try {
    $stmt = $pdo->query("SELECT course_code, course_name, sks, semester FROM subjects ORDER BY semester ASC, course_name ASC");
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Mata Kuliah</h1>
            <p>Daftar seluruh mata kuliah (view dari tabel subjects).</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 980px; margin: 0 auto;">
        <div class="card-top">
            <h3>Daftar Mata Kuliah</h3>
        </div>

        <div class="agenda-table-wrapper" style="margin-top: 10px;">
            <div class="agenda-table-header">
                <span class="col-head">KODE</span>
                <span class="col-head">NAMA</span>
                <span class="col-head">SKS</span>
                <span class="col-head">SEMESTER</span>
            </div>
            <div class="agenda-rows-stack">
                <?php if (empty($subjects)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada data mata kuliah.</div>
                <?php else: ?>
                    <?php foreach ($subjects as $s): ?>
                        <div class="agenda-table-row">
                            <div class="col-cell cell-date"><span class="date-lbl-txt"><?= htmlspecialchars($s['course_code']) ?></span></div>
                            <div class="col-cell cell-mid-desc">
                                <span class="item-main-headline"><?= htmlspecialchars($s['course_name']) ?></span>
                            </div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($s['sks']) ?></div>
                            <div class="col-cell cell-room-loc"><?= htmlspecialchars($s['semester']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 0 16px 16px;">
            <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none;">Kembali</a>
        </div>
    </div>
</main>

</div>
</body>
</html>

