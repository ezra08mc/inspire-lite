<?php
require_once "../config/db.php";
require_once "_layout.php";

// Form handler (update lecturer expertise/degree/birth_date if supported in schema)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = $_POST['nip'] ?? '';
    $expertise = trim($_POST['expertise'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';

    // Role guard is already handled by _layout.php
    try {
        $stmt = $pdo->prepare("UPDATE lecturers SET expertise = :expertise, degree = :degree, birth_date = :birth_date WHERE nip = :nip");
        $stmt->execute([
            ':expertise' => $expertise,
            ':degree' => $degree,
            ':birth_date' => $birth_date,
            ':nip' => $nip,
        ]);
        $success = 'Profil berhasil diperbarui.';
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui profil.';
    }
}

// Load current lecturer info
$user_id = (int)($_SESSION['user_id'] ?? 0);
$lecturer = null;
try {
    $stmt = $pdo->prepare("SELECT nip, first_name, last_name, birth_date, degree, expertise FROM lecturers WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);
    $lecturer = $stmt->fetch();
} catch (PDOException $e) {}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Profil Dosen</h1>
            <p>Kelola data identitas dan keahlian Anda.</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 920px; margin: 0 auto;">
        <div class="card-top">
            <h3>Data Profil</h3>
        </div>

        <?php if (!empty($success)): ?>
            <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="padding: 16px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display:block;">
                    <span>NIP</span>
                    <input type="text" name="nip" value="<?= htmlspecialchars($lecturer['nip'] ?? '') ?>" disabled style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                </label>

                <label style="display:block;">
                    <span>Gelar</span>
                    <input type="text" name="degree" value="<?= htmlspecialchars($lecturer['degree'] ?? '') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                </label>

                <label style="display:block;">
                    <span>Keahlian</span>
                    <input type="text" name="expertise" value="<?= htmlspecialchars($lecturer['expertise'] ?? '') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                </label>

                <label style="display:block;">
                    <span>Tanggal Lahir</span>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($lecturer['birth_date'] ?? '') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                </label>
            </div>

            <div style="display:flex; gap: 10px; margin-top: 16px;">
                <button type="submit" class="trigger-btn btn-blue" style="border:none;">Simpan Perubahan</button>
                <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Kembali</a>
            </div>
        </form>
    </div>
</main>

</div>
</body>
</html>

