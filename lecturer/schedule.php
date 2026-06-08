<?php
require_once "../config/db.php";
require_once "_layout.php";

$userId = (int)($_SESSION['user_id'] ?? 0);

$search = trim((string)($_GET['q'] ?? ''));
$semester = trim((string)($_GET['semester'] ?? ''));
$course = trim((string)($_GET['course'] ?? ''));

$semesterOptions = ['' => 'Semua Semester', '1' => 'Semester 1', '2' => 'Semester 2', '3' => 'Semester 3', '4' => 'Semester 4', '5' => 'Semester 5', '6' => 'Semester 6'];

$courses = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT nama_mata_kuliah, kode_mk FROM jadwal ORDER BY nama_mata_kuliah ASC");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(nama_mata_kuliah LIKE :q OR kode_mk LIKE :q OR hari LIKE :q OR kelas LIKE :q OR ruangan LIKE :q OR dosen_pengampu LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($course !== '') {
    $where[] = "kode_mk = :course";
    $params[':course'] = $course;
}

if ($semester !== '') {
    $where[] = "kelas LIKE :sem";
    $params[':sem'] = '%' . $semester . '%';
}

$sql = "SELECT kode_mk, nama_mata_kuliah, sks, kelas, dosen_pengampu, hari, tanggal, jam_mulai, jam_selesai, ruangan FROM jadwal";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY tanggal ASC, jam_mulai ASC";

$schedule = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedule = $stmt->fetchAll();
} catch (PDOException $e) {
    $schedule = [];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Teaching Schedule</h1>
            <p>Jadwal mengajar berdasarkan data tabel <code>jadwal</code>.</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 1100px; margin: 0 auto;">
        <div class="card-top">
            <h3>Schedule List</h3>
            <a href="dashboard.php" class="action-link">Back</a>
        </div>

        <div class="agenda-table-wrapper" style="margin-top: 10px;">
            <div class="agenda-table-header" style="grid-template-columns: 1fr 170px 170px 110px;">
                <span class="col-head">FILTER</span>
                <span class="col-head">SEMESTER</span>
                <span class="col-head">COURSE</span>
                <span class="col-head">SEARCH</span>
            </div>
            <div class="agenda-rows-stack" style="border-bottom: 1px solid var(--border);">
                <form method="get" style="display:grid; grid-template-columns: 1fr 170px 170px 110px; gap:0;">
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:600;" />
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <select name="semester" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:700;">
                            <?php foreach ($semesterOptions as $k => $label): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= (string)$semester === (string)$k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:1px solid var(--border); border-left:none; border-top:none;">
                        <select name="course" style="width:100%; border:none; outline:none; font-size:0.8rem; padding:0; background:transparent; color:var(--charcoal); font-weight:700;">
                            <option value="">Semua Mata Kuliah</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['kode_mk']) ?>" <?= (string)$course === (string)$c['kode_mk'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nama_mata_kuliah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="agenda-table-row" style="margin:0; border-bottom:none; border-right:none; border-left:none; border-top:none; display:flex; align-items:center; justify-content:center;">
                        <button type="submit" class="trigger-btn btn-blue" style="padding:10px 14px; border-radius:0.5rem; font-size:0.78rem;">Apply</button>
                    </div>
                </form>
            </div>

            <div class="agenda-table-header" style="grid-template-columns: 1.2fr 0.95fr 0.95fr 0.85fr; border-top:1px solid var(--border);">
                <span class="col-head">COURSE</span>
                <span class="col-head">TIME</span>
                <span class="col-head">CLASS/ROOM</span>
                <span class="col-head">LECTURER</span>
            </div>

            <div class="agenda-rows-stack">
                <?php if (empty($schedule)): ?>
                    <div class="empty-fallback-text border-box-pad">Tidak ada jadwal yang cocok dengan filter.</div>
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


