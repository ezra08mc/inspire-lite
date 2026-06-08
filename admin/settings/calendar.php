<?php
session_start();
require_once "../../config/db.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$success = null;
$error = null;

// Fetch all subjects and lecturers for the form
$subjects = [];
$lecturers = [];
try {
    $subjects = $pdo
        ->query(
            "SELECT course_code, course_name, sks FROM subjects ORDER BY course_name",
        )
        ->fetchAll();
    $lecturers = $pdo
        ->query(
            "SELECT nip, first_name, last_name FROM lecturers ORDER BY first_name",
        )
        ->fetchAll();
} catch (PDOException $e) {
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "edit") {
        $id = $_POST["id"] ?? null;
        $course_code = $_POST["course_code"] ?? "";
        $hari = $_POST["hari"] ?? "";
        $tanggal = $_POST["tanggal"] ?? "";
        $jam_mulai = $_POST["jam_mulai"] ?? "";
        $jam_selesai = $_POST["jam_selesai"] ?? "";
        $ruangan = $_POST["ruangan"] ?? "";
        $kelas = $_POST["kelas"] ?? "";
        $nip = $_POST["nip"] ?? "";

        if (
            $course_code &&
            $hari &&
            $tanggal &&
            $jam_mulai &&
            $jam_selesai &&
            $ruangan &&
            $kelas &&
            $nip
        ) {
            // Get course name and lecturer name for denormalized columns
            $course_name = "";
            $sks = 3;
            foreach ($subjects as $s) {
                if ($s["course_code"] === $course_code) {
                    $course_name = $s["course_name"];
                    $sks = $s["sks"];
                    break;
                }
            }

            $lecturer_name = "";
            foreach ($lecturers as $l) {
                if ($l["nip"] === $nip) {
                    $lecturer_name = $l["first_name"] . " " . $l["last_name"];
                    break;
                }
            }

            try {
                if ($action === "add") {
                    $stmt = $pdo->prepare(
                        "INSERT INTO jadwal (kode_mk, nama_mata_kuliah, sks, kelas, dosen_pengampu, hari, tanggal, jam_mulai, jam_selesai, ruangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    );
                    $stmt->execute([
                        $course_code,
                        $course_name,
                        $sks,
                        $kelas,
                        $lecturer_name,
                        $hari,
                        $tanggal,
                        $jam_mulai,
                        $jam_selesai,
                        $ruangan,
                    ]);
                    $success = "Jadwal berhasil ditambahkan.";
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE jadwal SET kode_mk=?, nama_mata_kuliah=?, sks=?, kelas=?, dosen_pengampu=?, hari=?, tanggal=?, jam_mulai=?, jam_selesai=?, ruangan=? WHERE id=?",
                    );
                    $stmt->execute([
                        $course_code,
                        $course_name,
                        $sks,
                        $kelas,
                        $lecturer_name,
                        $hari,
                        $tanggal,
                        $jam_mulai,
                        $jam_selesai,
                        $ruangan,
                        $id,
                    ]);
                    $success = "Jadwal berhasil diperbarui.";
                }
            } catch (PDOException $e) {
                $error = "Gagal menyimpan jadwal.";
            }
        } else {
            $error = "Semua field harus diisi.";
        }
    } elseif ($action === "delete") {
        $id = $_POST["id"] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM jadwal WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Jadwal berhasil dihapus.";
            } catch (PDOException $e) {
                $error = "Gagal menghapus jadwal.";
            }
        }
    }
}

$jadwal = [];
try {
    $jadwal = $pdo
        ->query("SELECT * FROM jadwal ORDER BY tanggal DESC, jam_mulai ASC")
        ->fetchAll();
} catch (PDOException $e) {
}

$base_path = "../../";
$page_title = "Kalender & Jadwal - Admin";
$current_page = "calendar";
include $base_path . "includes/header.php";
include $base_path . "includes/sidebar.php";
?>
<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
.modal-panel { background: white; padding: 24px; border-radius: 12px; width: 100%; max-width: 600px; }
.modal-body { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-height: 70vh; overflow-y: auto; padding-top: 10px; }
.modal-body label { font-size: 0.85rem; font-weight: 600; display: flex; flex-direction: column; gap: 5px; color: #374151; grid-column: span 1; }
.modal-body label.full-width { grid-column: span 2; }
.modal-body input, .modal-body select { padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.action-btn { padding: 6px 12px; border-radius: 6px; cursor: pointer; border: 1px solid #e5e7eb; background: white; }
.btn-save { background: #FF3B30; color: white; border: none; }
</style>
<div class="main-content">
    <?php include $base_path . "includes/topbar.php"; ?>
    <main class="dashboard-viewport" style="padding: 24px;">
        <div class="content-card">
            <div class="card-top">
                <h3>Kelola Jadwal Perkuliahan</h3>
                <button onclick="openModal()" style="padding: 8px 16px; background: #FF3B30; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">+ Tambah Jadwal</button>
            </div>

            <?php if ($success): ?>
                <div style="padding: 15px; margin: 15px 0; border-radius: 8px; background: #dcfce7; color: #15803d;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="padding: 15px; margin: 15px 0; border-radius: 8px; background: #fee2e2; color: #b91c1c;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div style="overflow-x: auto; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: #f9fafb; text-align: left;">
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">HARI, TANGGAL</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">JAM</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">MATA KULIAH</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">KELAS/RUANG</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb;">DOSEN</th>
                            <th style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jadwal as $j): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars(
                                    $j["hari"] . ", " . $j["tanggal"],
                                ) ?></td>
                                <td style="padding: 12px; color: #6b7280;"><?= htmlspecialchars(
                                    substr($j["jam_mulai"], 0, 5) .
                                        " - " .
                                        substr($j["jam_selesai"], 0, 5),
                                ) ?></td>
                                <td style="padding: 12px;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars(
                                        $j["nama_mata_kuliah"],
                                    ) ?></div>
                                    <div style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars(
                                        $j["kode_mk"],
                                    ) ?></div>
                                </td>
                                <td style="padding: 12px;"><?= htmlspecialchars(
                                    $j["kelas"] . " / " . $j["ruangan"],
                                ) ?></td>
                                <td style="padding: 12px;"><?= htmlspecialchars(
                                    $j["dosen_pengampu"],
                                ) ?></td>
                                <td style="padding: 12px; text-align: right;">
                                    <button class="action-btn" onclick="openModal(<?= htmlspecialchars(
                                        json_encode($j),
                                    ) ?>)">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus jadwal ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $j[
                                            "id"
                                        ] ?>">
                                        <button type="submit" class="action-btn" style="color: #b91c1c;">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jadwal)): ?>
                            <tr><td colspan="6" style="padding: 20px; text-align: center; color: #6b7280;">Belum ada jadwal.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="jadwalModal" class="modal-overlay">
    <div class="modal-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e5e7eb; padding-bottom:10px; margin-bottom:10px;">
            <h3 id="modalTitle">Tambah Jadwal</h3>
            <button onclick="closeModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="jadwalId">
            <div class="modal-body">
                <label class="full-width">Mata Kuliah
                    <select name="course_code" id="course_code" required>
                        <option value="">-- Pilih MK --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= htmlspecialchars(
                                $s["course_code"],
                            ) ?>"><?= htmlspecialchars(
    $s["course_code"] . " - " . $s["course_name"],
) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Dosen Pengampu
                    <select name="nip" id="nip" required>
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach ($lecturers as $l): ?>
                            <option value="<?= htmlspecialchars(
                                $l["nip"],
                            ) ?>"><?= htmlspecialchars(
    $l["first_name"] . " " . $l["last_name"],
) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Kelas
                    <input type="text" name="kelas" id="kelas" required placeholder="Contoh: A">
                </label>
                <label>Hari
                    <select name="hari" id="hari" required>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                    </select>
                </label>
                <label>Tanggal
                    <input type="date" name="tanggal" id="tanggal" required>
                </label>
                <label>Jam Mulai
                    <input type="time" name="jam_mulai" id="jam_mulai" required>
                </label>
                <label>Jam Selesai
                    <input type="time" name="jam_selesai" id="jam_selesai" required>
                </label>
                <label class="full-width">Ruangan
                    <input type="text" name="ruangan" id="ruangan" required placeholder="Contoh: Gedung A Lt 1">
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="action-btn" onclick="closeModal()">Batal</button>
                <button type="submit" class="action-btn btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(data = null) {
    document.getElementById('jadwalModal').style.display = 'flex';
    if (data) {
        document.getElementById('modalTitle').innerText = 'Edit Jadwal';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('jadwalId').value = data.id;
        document.getElementById('course_code').value = data.kode_mk;
        document.getElementById('hari').value = data.hari;
        document.getElementById('tanggal').value = data.tanggal;
        document.getElementById('jam_mulai').value = data.jam_mulai;
        document.getElementById('jam_selesai').value = data.jam_selesai;
        document.getElementById('ruangan').value = data.ruangan;
        document.getElementById('kelas').value = data.kelas;

        // Find Dosen NIP by name (hacky but works since we only have name in jadwal)
        const nipSelect = document.getElementById('nip');
        for(let i=0; i<nipSelect.options.length; i++) {
            if(nipSelect.options[i].text === data.dosen_pengampu) {
                nipSelect.selectedIndex = i;
                break;
            }
        }
    } else {
        document.getElementById('modalTitle').innerText = 'Tambah Jadwal';
        document.getElementById('formAction').value = 'add';
        document.getElementById('jadwalId').value = '';
        document.getElementById('course_code').value = '';
        document.getElementById('hari').value = 'Senin';
        document.getElementById('tanggal').value = '';
        document.getElementById('jam_mulai').value = '';
        document.getElementById('jam_selesai').value = '';
        document.getElementById('ruangan').value = '';
        document.getElementById('kelas').value = '';
        document.getElementById('nip').value = '';
    }
}

function closeModal() {
    document.getElementById('jadwalModal').style.display = 'none';
}
</script>
<?php include $base_path . "includes/footer.php"; ?>
