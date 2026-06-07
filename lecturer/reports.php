<?php
require_once "../config/db.php";
require_once "_layout.php";

// database.sql: only student_academic_stats and subjects, enrollments
$stats = null;
$error = null;
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_students, AVG(sks_semester) AS avg_sks_semester, AVG(ipk_kumulatif) AS avg_ipk_kumulatif, AVG(ip_semester) AS avg_ip_semester FROM student_academic_stats s JOIN users u ON u.id = s.user_id");
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Gagal memuat laporan.';
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Laporan</h1>
            <p>Ringkasan statistik akademik (berdasarkan student_academic_stats).</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 980px; margin: 0 auto;">
        <div class="card-top">
            <h3>Ringkasan</h3>
            <a href="dashboard.php" class="action-link">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($stats): ?>
            <div class="metrics-row" style="padding: 16px 16px 0;">
                <div class="metric-card">
                    <span class="metric-label">TOTAL MAHASISWA</span>
                    <span class="metric-value"><?= htmlspecialchars($stats['total_students']) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">AVG SKS SEMESTER</span>
                    <span class="metric-value"><?= htmlspecialchars($stats['avg_sks_semester']) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">AVG IPK</span>
                    <span class="metric-value"><?= htmlspecialchars(number_format((float)$stats['avg_ipk_kumulatif'], 2)) ?></span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">AVG IP SEMESTER</span>
                    <span class="metric-value"><?= htmlspecialchars(number_format((float)$stats['avg_ip_semester'], 2)) ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-fallback-text border-box-pad">Tidak ada data laporan.</div>
        <?php endif; ?>
    </div>
</main>

</div>
</body>
</html>

