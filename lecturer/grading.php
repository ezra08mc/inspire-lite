<?php
require_once "../config/db.php";
require_once "_layout.php";

// database.sql: academic_grades table exists
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim'] ?? '');
    $course_code = trim($_POST['course_code'] ?? '');
    $grade_point = trim($_POST['grade_point'] ?? '');
    $grade_letter = trim($_POST['grade_letter'] ?? '');

    if ($nim === '' || $course_code === '' || $grade_point === '' || $grade_letter === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO academic_grades (nim, course_code, grade_point, grade_letter) VALUES (:nim, :course_code, :grade_point, :grade_letter)");
            $stmt->execute([
                ':nim' => $nim,
                ':course_code' => $course_code,
                ':grade_point' => $grade_point,
                ':grade_letter' => $grade_letter,
            ]);
            $success = 'Nilai berhasil disimpan.';
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan nilai. Pastikan NIM dan course_code valid.';
        }
    }
}

$grades = [];
try {
    $stmt = $pdo->query("SELECT g.id, g.nim, g.course_code, s.first_name, s.last_name, subj.course_name, g.grade_point, g.grade_letter FROM academic_grades g JOIN students s ON s.nim = g.nim JOIN subjects subj ON subj.course_code = g.course_code ORDER BY g.id DESC");
    $grades = $stmt->fetchAll();
} catch (PDOException $e) {}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Penilaian</h1>
            <p>Masukkan dan lihat nilai akademik dari tabel <code>academic_grades</code>.</p>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Simpan Nilai</h3>
                <a href="dashboard.php" class="action-link">Kembali</a>
            </div>

            <?php if ($success): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="padding: 16px;">
                <label style="display:block; margin-bottom: 10px;">
                    <span>NIM</span>
                    <input required name="nim" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
                <label style="display:block; margin-bottom: 10px;">
                    <span>Kode MK</span>
                    <input required name="course_code" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                </label>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Nilai Angka (grade_point)</span>
                        <input required name="grade_point" type="number" step="0.01" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                    <label style="display:block;">
                        <span>Huruf (grade_letter)</span>
                        <select required name="grade_letter" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </label>
                </div>

                <div style="margin-top: 16px; display:flex; gap: 10px;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Simpan</button>
                </div>
            </form>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Daftar Nilai</h3>
                <span class="action-link" style="cursor:default;">Total: <?= count($grades) ?></span>
            </div>

            <div class="agenda-table-wrapper" style="margin-top: 10px;">
                <div class="agenda-table-header">
                    <span class="col-head">MAHASISWA</span>
                    <span class="col-head">MK</span>
                    <span class="col-head">NILAI</span>
                </div>

                <div class="agenda-rows-stack">
                    <?php if (empty($grades)): ?>
                        <div class="empty-fallback-text border-box-pad">Belum ada nilai.</div>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <div class="agenda-table-row">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['first_name'].' '.$g['last_name']) ?></span>
                                    <span class="item-sub-clock">NIM <?= htmlspecialchars($g['nim']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['course_code']) ?></span>
                                    <span class="item-sub-clock"><?= htmlspecialchars($g['course_name']) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= htmlspecialchars($g['grade_letter']) ?></span>
                                    <span class="item-sub-clock">Point <?= htmlspecialchars($g['grade_point']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>

