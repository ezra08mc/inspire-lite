<?php
require_once "../config/db.php";
require_once "_layout.php";

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? '');
    $badge_class = trim($_POST['badge_class'] ?? 'blue');
    $date_text = trim($_POST['date_text'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? '');

    if ($type === '' || $date_text === '' || $title === '' || $content === '' || $author === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (type, badge_class, date_text, title, content, author) VALUES (:type, :badge_class, :date_text, :title, :content, :author)");
            $stmt->execute([
                ':type' => $type,
                ':badge_class' => $badge_class,
                ':date_text' => $date_text,
                ':title' => $title,
                ':content' => $content,
                ':author' => $author,
            ]);
            $success = 'Pengumuman ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan pengumuman.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $success = 'Pengumuman dihapus.';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus pengumuman.';
    }
}

$items = [];
try {
    $stmt = $pdo->query("SELECT id, type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC");
    $items = $stmt->fetchAll();
} catch (PDOException $e) {}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Pengumuman</h1>
            <p>Buat & kelola pengumuman menggunakan tabel <code>announcements</code>.</p>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Tambah Pengumuman</h3>
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
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Type</span>
                        <input required name="type" type="text" placeholder="PENTING/UMUM/REKTORAT" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                    <label style="display:block;">
                        <span>Badge Class</span>
                        <input required name="badge_class" type="text" value="blue" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <div style="margin-top:12px; display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Tanggal</span>
                        <input required name="date_text" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" placeholder="20 Mei 2026" />
                    </label>
                    <label style="display:block;">
                        <span>Author</span>
                        <input required name="author" type="text" value="<?= htmlspecialchars($lecturer_name ?: 'Dosen') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block;">
                        <span>Judul</span>
                        <input required name="title" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block;">
                        <span>Konten</span>
                        <textarea required name="content" rows="6" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;"></textarea>
                    </label>
                </div>

                <div style="margin-top:16px; display:flex; gap: 10px;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Simpan</button>
                </div>
            </form>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Daftar Pengumuman</h3>
                <span class="action-link" style="cursor:default;">Total: <?= count($items) ?></span>
            </div>

            <div class="tasks-vertical-stack" style="padding: 0 16px 16px;">
                <?php if (empty($items)): ?>
                    <div class="empty-fallback-text border-box-pad">Belum ada pengumuman.</div>
                <?php else: ?>
                    <?php foreach ($items as $it): ?>
                        <div class="task-node">
                            <div class="task-node-details" style="flex:1;">
                                <span class="task-node-title"><?= htmlspecialchars($it['title']) ?></span>
                                <span class="task-node-time"><?= htmlspecialchars($it['date_text']) ?> · <?= htmlspecialchars($it['type']) ?></span>
                                <div style="margin-top:8px; color:#6b7280; font-size:13px; line-height:1.35;">
                                    <?= htmlspecialchars(mb_strimwidth($it['content'], 0, 120, '…')) ?>
                                </div>
                            </div>
                            <a class="trigger-btn btn-red" style="text-decoration:none; padding:8px 12px;" href="announcements.php?delete=<?= (int)$it['id'] ?>" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
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

