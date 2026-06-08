<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$admin_name = "";
try {
    $stmt = $pdo->prepare(
        "SELECT first_name, last_name FROM admins WHERE user_id = :user_id LIMIT 1",
    );
    $stmt->execute([":user_id" => $user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name =
            $admin_data["first_name"] . " " . $admin_data["last_name"];
    }
} catch (PDOException $e) {
}

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = trim($_POST["type"] ?? "");
    $badge_class = trim($_POST["badge_class"] ?? "blue");
    $date_text = trim($_POST["date_text"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $author = trim($_POST["author"] ?? "");

    if (
        $type === "" ||
        $date_text === "" ||
        $title === "" ||
        $content === "" ||
        $author === ""
    ) {
        $error = "Semua field wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (type, badge_class, date_text, title, content, author) VALUES (:type, :badge_class, :date_text, :title, :content, :author)",
            );
            $stmt->execute([
                ":type" => $type,
                ":badge_class" => $badge_class,
                ":date_text" => $date_text,
                ":title" => $title,
                ":content" => $content,
                ":author" => $author,
            ]);
            $success = "Pengumuman ditambahkan.";
        } catch (PDOException $e) {
            $error = "Gagal menambahkan pengumuman.";
        }
    }
}

if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $success = "Pengumuman dihapus.";
    } catch (PDOException $e) {
        $error = "Gagal menghapus pengumuman.";
    }
}

$items = [];
try {
    $stmt = $pdo->query(
        "SELECT id, type, badge_class, date_text, title, content, author FROM announcements ORDER BY id DESC",
    );
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
}

$base_path = "../";
$page_title = "Kelola Pengumuman - Admin";
$current_page = "announcements";

include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <section class="hero-banner">
            <div class="hero-title">
                <h1>Pengumuman</h1>
                <p>Buat & kelola pengumuman untuk seluruh sivitas akademika.</p>
            </div>
        </section>

        <div class="split-grid" style="gap: 24px; align-items:start;">
            <div class="content-card" style="flex: 1;">
                <div class="card-top">
                    <h3>Tambah Pengumuman</h3>
                </div>

                <?php if ($success): ?>
                    <div style="padding: 15px; margin: 15px; border-radius: 8px; background: #dcfce7; color: #15803d;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="padding: 15px; margin: 15px; border-radius: 8px; background: #fee2e2; color: #b91c1c;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="padding: 20px; display: grid; gap: 15px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <label style="display:flex; flex-direction:column; gap:5px;">
                            <span>Kategori</span>
                            <select name="type" required style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Umum">Umum</option>
                                <option value="Penting">Penting</option>
                                <option value="Rektorat">Rektorat</option>
                                <option value="Dekanat">Dekanat</option>
                                <option value="Jurusan">Jurusan</option>
                                <option value="Akademik">Akademik</option>
                                <option value="Keuangan">Keuangan</option>
                                <option value="UPT TIK">UPT TIK</option>
                                <option value="Perpustakaan">Perpustakaan</option>
                            </select>
                        </label>
                        <label style="display:flex; flex-direction:column; gap:5px;">
                            <span>Badge Class</span>
                            <select name="badge_class" style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;">
                                <option value="blue">Blue</option>
                                <option value="red">Red</option>
                                <option value="green">Green</option>
                            </select>
                        </label>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <label style="display:flex; flex-direction:column; gap:5px;">
                            <span>Tanggal</span>
                            <input required name="date_text" type="text" value="<?= date(
                                "d M Y",
                            ) ?>" style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;" />
                        </label>
                        <label style="display:flex; flex-direction:column; gap:5px;">
                            <span>Author</span>
                            <input required name="author" type="text" value="<?= htmlspecialchars(
                                $admin_name ?: "Administrator",
                            ) ?>" style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;" />
                        </label>
                    </div>

                    <label style="display:flex; flex-direction:column; gap:5px;">
                        <span>Judul</span>
                        <input required name="title" type="text" style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;" />
                    </label>

                    <label style="display:flex; flex-direction:column; gap:5px;">
                        <span>Konten</span>
                        <textarea required name="content" rows="4" style="padding:10px; border:1px solid #e5e7eb; border-radius:6px;"></textarea>
                    </label>

                    <button type="submit" style="padding: 12px; background: #FF3B30; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Simpan</button>
                </form>
            </div>

            <div class="content-card" style="flex: 1.5;">
                <div class="card-top">
                    <h3>Daftar Pengumuman</h3>
                </div>

                <div style="padding: 0 20px 20px;">
                    <?php if (empty($items)): ?>
                        <div style="text-align:center; padding: 40px; color: #6b7280;">Belum ada pengumuman.</div>
                    <?php else: ?>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($items as $it): ?>
                                <div style="display: flex; gap: 15px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; margin-bottom: 5px;"><?= htmlspecialchars(
                                            $it["title"],
                                        ) ?></div>
                                        <div style="font-size: 0.85rem; color: #6b7280; margin-bottom: 8px;"><?= htmlspecialchars(
                                            $it["date_text"],
                                        ) ?> · <?= htmlspecialchars(
     $it["type"],
 ) ?></div>
                                        <div style="font-size: 0.9rem; line-height: 1.4; color: #374151;">
                                            <?= htmlspecialchars(
                                                mb_strimwidth(
                                                    $it["content"],
                                                    0,
                                                    150,
                                                    "…",
                                                ),
                                            ) ?>
                                        </div>
                                    </div>
                                    <a href="announcements.php?delete=<?= $it[
                                        "id"
                                    ] ?>" onclick="return confirm('Hapus?')" style="color: #b91c1c; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Hapus</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include $base_path . "includes/footer.php"; ?>
