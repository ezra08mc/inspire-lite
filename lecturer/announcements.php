<?php
require_once "../config/db.php";
require_once "_lib.php";
require_once "_layout.php";

lecturer_require_role();

$success = '';
$error = '';

$search = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'newest'));

$categories = [
    'Academic' => 'Academic',
    'Examination' => 'Examination',
    'Assignment' => 'Assignment',
    'Schedule Changes' => 'Schedule Changes',
    'General Information' => 'General Information',
    'Other' => 'Other'
];

$audienceTypes = ['All Students', 'Students', 'Staff', 'Other'];

function compute_announcement_status(?string $type, ?string $date_text): string {
    $type = (string)($type ?? '');
    if ($type !== '') {
        $t = strtoupper($type);
        if ($t === 'DRAFT') return 'draft';
        if ($t === 'SCHEDULED') return 'scheduled';
        if ($t === 'PUBLISHED') return 'published';
        if ($t === 'EXPIRED') return 'expired';
    }
    return 'published';
}

function escape_str(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$all = [];
try {
    $sql = "SELECT id, type, badge_class, date_text, title, content, author FROM announcements";
    $filters = [];
    $params = [];

    if ($search !== '') {
        $filters[] = "title LIKE :q OR content LIKE :q";
        $params[':q'] = '%' . $search . '%';
    }

    if ($category !== '') {
        $filters[] = "type = :cat";
        $params[':cat'] = $category;
    }

    if ($status !== '') {
        $filters[] = "type = :status";
        $params[':status'] = strtoupper($status);
    }

    if (!empty($filters)) {
        $sql .= " WHERE " . implode(' AND ', $filters);
    }

    if ($sort === 'oldest') {
        $sql .= " ORDER BY id ASC";
    } else {
        $sql .= " ORDER BY id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all = $stmt->fetchAll();
} catch (PDOException $e) {
    $all = [];
}

$totalAnnouncements = count($all);
$activeCount = 0;
$scheduledCount = 0;
$expiredCount = 0;
foreach ($all as $it) {
    $st = compute_announcement_status($it['type'] ?? null, $it['date_text'] ?? null);
    if ($st === 'published') $activeCount++;
    if ($st === 'scheduled') $scheduledCount++;
    if ($st === 'expired') $expiredCount++;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'create'));

    $id = (int)($_POST['id'] ?? 0);
    $type = trim((string)($_POST['type'] ?? ''));
    $badge_class = trim((string)($_POST['badge_class'] ?? 'blue'));
    $date_text = trim((string)($_POST['publish_date'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));

    if ($action === 'delete') {
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $success = 'Announcement deleted.';
            } catch (PDOException $e) {
                $error = 'Gagal menghapus pengumuman.';
            }
        }
        header('Location: announcements.php');
        exit();
    }

    $audience = trim((string)($_POST['audience'] ?? 'All Students'));
    $expires = trim((string)($_POST['expires_at'] ?? ''));

    if ($type === '' || $date_text === '' || $title === '' || $content === '' || $author === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        try {
            $metaAudience = 'Audience:' . $audience;
            $metaExpire = $expires !== '' ? (' | Expires:' . $expires) : '';
            $contentFinal = $content . ' ' . $metaAudience . $metaExpire;

            if ($action === 'edit' && $id > 0) {
                $stmt = $pdo->prepare("UPDATE announcements SET type = :type, badge_class = :badge_class, date_text = :date_text, title = :title, content = :content, author = :author WHERE id = :id");
                $stmt->execute([
                    ':type' => $type,
                    ':badge_class' => $badge_class,
                    ':date_text' => $date_text,
                    ':title' => $title,
                    ':content' => $contentFinal,
                    ':author' => $author,
                    ':id' => $id,
                ]);
                $success = 'Announcement updated.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO announcements (type, badge_class, date_text, title, content, author) VALUES (:type, :badge_class, :date_text, :title, :content, :author)");
                $stmt->execute([
                    ':type' => $type,
                    ':badge_class' => $badge_class,
                    ':date_text' => $date_text,
                    ':title' => $title,
                    ':content' => $contentFinal,
                    ':author' => $author,
                ]);
                $success = 'Announcement created.';
            }
            header('Location: announcements.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan pengumuman.';
        }
    }
}

$editing = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, type, badge_class, date_text, title, content, author FROM announcements WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $editId]);
        $editing = $stmt->fetch();
    } catch (PDOException $e) {
        $editing = null;
    }
}

$recent = [];
try {
    $stmt = $pdo->query("SELECT id, type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC LIMIT 6");
    $recent = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent = [];
}

?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Announcements</h1>
            <p>Manage academic announcements and lecturer communications.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Announcements</span><span class="metric-value"><?= htmlspecialchars((string)$totalAnnouncements) ?></span></div>
            <div class="metric-card"><span class="metric-label">Active</span><span class="metric-value"><?= htmlspecialchars((string)$activeCount) ?></span></div>
            <div class="metric-card"><span class="metric-label">Scheduled</span><span class="metric-value"><?= htmlspecialchars((string)$scheduledCount) ?></span></div>
            <div class="metric-card"><span class="metric-label">Expired</span><span class="metric-value"><?= htmlspecialchars((string)$expiredCount) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Create Announcement</h3>
                <a href="dashboard.php" class="action-link">Kembali</a>
            </div>

            <?php if ($success !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px;"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="padding: 16px;">
                <input type="hidden" name="action" value="create" />

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Category</span>
                        <select required name="type" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                            <option value="Academic">Academic</option>
                            <option value="Examination">Examination</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Schedule Changes">Schedule Changes</option>
                            <option value="General Information">General Information</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>
                    <label style="display:block;">
                        <span>Badge Class</span>
                        <select name="badge_class" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                            <option value="blue" selected>blue</option>
                            <option value="red">red</option>
                            <option value="coral">coral</option>
                            <option value="purple">purple</option>
                        </select>
                    </label>
                </div>

                <div style="margin-top:12px; display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Publish Date</span>
                        <input required name="publish_date" type="text" value="<?= e(date('d M Y')) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                    <label style="display:block;">
                        <span>Audience</span>
                        <select name="audience" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
                            <?php foreach ($audienceTypes as $a): ?>
                                <option value="<?= e($a) ?>" <?= $a === 'All Students' ? 'selected' : '' ?>><?= e($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div style="margin-top:12px; display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display:block;">
                        <span>Expiration Date</span>
                        <input name="expires_at" type="text" placeholder="(optional)" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                    <label style="display:block;">
                        <span>Author</span>
                        <input required name="author" type="text" value="<?= e($lecturer_name ?: 'Dosen') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block;">
                        <span>Title</span>
                        <input required name="title" type="text" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                    </label>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:block;">
                        <span>Content</span>
                        <textarea required name="content" rows="6" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;"></textarea>
                    </label>
                </div>

                <div style="margin-top:16px; display:flex; gap: 10px; flex-wrap:wrap;">
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Publish</button>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-size:0.75rem; color:#6b7280;">Draft/scheduling uses announcement type values in this build.</div>
                </div>
            </form>

            <?php if (!empty($editing)): ?>
                <div style="margin-top: 10px; padding: 0 16px 16px;">
                    <form method="POST" style="padding:0;" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit" />
                        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
                        <input type="hidden" name="badge_class" value="<?= e($editing['badge_class'] ?? 'blue') ?>" />
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Announcements Table</h3>
                <span class="action-link" style="cursor:default;">Total: <?= (int)count($all) ?></span>
            </div>

            <div style="padding: 0 16px 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <input name="q" value="<?= e($search) ?>" placeholder="Search title" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" type="text" />
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <select name="status" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Status</option>
                            <option value="DRAFT" <?= $status === 'DRAFT' ? 'selected' : '' ?>>Draft</option>
                            <option value="SCHEDULED" <?= $status === 'SCHEDULED' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="PUBLISHED" <?= $status === 'PUBLISHED' ? 'selected' : '' ?>>Published</option>
                            <option value="EXPIRED" <?= $status === 'EXPIRED' ? 'selected' : '' ?>>Expired</option>
                        </select>
                        <select name="category" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $k => $label): ?>
                                <option value="<?= e($label) ?>" <?= $category === $label ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <select name="sort" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by newest</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Sort by oldest</option>
                    </select>
                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Apply</button>
                    <a href="announcements.php" class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none;">Reset</a>
                </form>
            </div>

            <div class="agenda-table-wrapper" style="margin: 0 16px 16px;">
                <div class="agenda-table-header" style="grid-template-columns: 1.2fr 0.7fr 0.8fr 0.8fr 0.6fr 0.8fr 0.7fr;">
                    <span class="col-head">Title</span>
                    <span class="col-head">Category</span>
                    <span class="col-head">Publish Date</span>
                    <span class="col-head">Expiration</span>
                    <span class="col-head">Status</span>
                    <span class="col-head">Audience</span>
                    <span class="col-head">Actions</span>
                </div>
                <div class="agenda-rows-stack">
                    <?php if (empty($all)): ?>
                        <div class="empty-fallback-text border-box-pad">No announcements found.</div>
                    <?php else: ?>
                        <?php foreach ($all as $it): ?>
                            <?php
                                $content = (string)($it['content'] ?? '');
                                $aud = 'All Students';
                                if (preg_match('/Audience:([^|]+)/', $content, $mm)) $aud = trim((string)$mm[1]);
                                $exp = '';
                                if (preg_match('/Expires:([^\s]+)/', $content, $mm2)) $exp = trim((string)$mm2[1]);
                                $st = compute_announcement_status($it['type'] ?? null, $it['date_text'] ?? null);
                                $statusLabel = $st === 'draft' ? 'Draft' : ($st === 'scheduled' ? 'Scheduled' : ($st === 'expired' ? 'Expired' : 'Published'));
                            ?>
                            <div class="agenda-table-row" style="grid-template-columns: 1.2fr 0.7fr 0.8fr 0.8fr 0.6fr 0.8fr 0.7fr;">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= e((string)($it['title'] ?? '')) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e((string)($it['type'] ?? '')) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e((string)($it['date_text'] ?? '')) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($exp) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="label-badge red" style="opacity: <?= $st === 'expired' ? '1' : '0.75' ?>;"><?= e($statusLabel) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline"><?= e($aud) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                                        <button type="button" class="trigger-btn btn-blue" style="border:none; padding:8px 12px;" onclick="alert('View not connected to this build.')">View</button>
                                        <a class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none; padding:8px 12px;" href="announcements.php?edit=<?= (int)$it['id'] ?>">Edit</a>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="delete" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <button type="submit" class="trigger-btn btn-red" style="border:none; padding:8px 12px;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-top" style="margin: 0 16px 10px;">
                <h3>Recent Announcements</h3>
                <a href="announcements.php" class="action-link">Refresh</a>
            </div>

            <div class="announcements-feed" style="padding: 0 16px 16px;">
                <?php foreach ($recent as $r): ?>
                    <div class="annc-node">
                        <div class="annc-top-line">
                            <div class="annc-icon-frame <?= e($r['badge_class'] ?? 'blue') ?>">
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            </div>
                            <div class="annc-title-area">
                                <div class="annc-meta">
                                    <span class="annc-date"><?= e((string)$r['date_text']) ?></span>
                                    <span class="label-badge red"><?= e((string)($r['type'] ?? '')) ?></span>
                                </div>
                                <h4><?= e((string)($r['title'] ?? '')) ?></h4>
                            </div>
                        </div>
                        <p class="annc-body-text"><?= e(mb_strimwidth((string)($r['content'] ?? ''), 0, 140, '…')) ?></p>
                        <span class="annc-dept"><?= e((string)($r['author'] ?? '')) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>


