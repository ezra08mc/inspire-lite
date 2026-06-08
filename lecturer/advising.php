<?php
require_once "../config/db.php";
require_once "_layout.php";

$lecturerId = (int) ($_SESSION["user_id"] ?? 0);

$success = "";
$error = "";

$search = trim((string) ($_GET["q"] ?? ""));
$program = trim((string) ($_GET["program"] ?? ""));
$academicYear = trim((string) ($_GET["academic_year"] ?? ""));
$academicStatus = trim((string) ($_GET["academic_status"] ?? ""));
$advisingStatus = trim((string) ($_GET["advising_status"] ?? ""));

$selectedNim = trim((string) ($_GET["nim"] ?? ""));

function advising_safe_str($v): string
{
    return (string) $v;
}

function gpa_bucket(?float $gpa): string
{
    if ($gpa === null) {
        return "—";
    }
    if ($gpa < 2.5) {
        return "Low GPA";
    }
    if ($gpa < 3.0) {
        return "Fair GPA";
    }
    return "Good GPA";
}

function academic_status_badge(?string $s): string
{
    $s = (string) ($s ?? "");
    if ($s === "ON_TRACK") {
        return "label-badge red";
    }
    if ($s === "AT_RISK") {
        return "label-badge red";
    }
    return "";
}

// POST Handler: Unified for Chat and Formal Records
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim((string) ($_POST["action"] ?? ""));

    // Handle Chat Message (from 'theirs')
    if (isset($_POST["message"]) && $action === "") {
        $receiver_id = (int) ($_POST["receiver_id"] ?? 0);
        $message = trim($_POST["message"] ?? "");
        $nim = trim((string) ($_POST["nim"] ?? ""));

        if ($receiver_id && $message) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)",
                );
                $stmt->execute([$lecturerId, $receiver_id, $message]);
                $success = "Pesan terkirim.";
                // No redirect for chat to keep scroll position if handled by JS, but here we reload
            } catch (PDOException $e) {
                $error = "Gagal mengirim pesan.";
            }
        } else {
            $error = "Pilih mahasiswa dan ketik pesan.";
        }
    }
    // Handle Formal Advising Session Actions (from 'HEAD')
    else {
        $nim = trim((string) ($_POST["nim"] ?? ""));
        $tanggal = trim((string) ($_POST["tanggal"] ?? ""));
        $topik = trim((string) ($_POST["topik"] ?? ""));
        $catatan = trim((string) ($_POST["catatan"] ?? ""));
        $rekomendasi = trim((string) ($_POST["rekomendasi"] ?? ""));
        $followup = trim((string) ($_POST["followup"] ?? ""));
        $id = (int) ($_POST["id"] ?? 0);

        try {
            if ($action === "create") {
                if ($nim === "" || $tanggal === "") {
                    $error = "NIM dan tanggal wajib diisi.";
                } else {
                    $msg = trim($topik !== "" ? $topik : $catatan);
                    $stmt = $pdo->prepare(
                        "INSERT INTO student_notifications (title, content, category, sender, created_at) VALUES (:title, :content, :category, :sender, :created_at)",
                    );
                    $stmt->execute([
                        ":title" => "Advising Session",
                        ":content" =>
                            "NIM:" .
                            $nim .
                            " | " .
                            $msg .
                            " | " .
                            ($rekomendasi !== ""
                                ? "Rekomendasi:" . $rekomendasi
                                : "") .
                            " | " .
                            ($followup !== "" ? "Follow-up:" . $followup : ""),
                        ":category" => "BIMBINGAN",
                        ":sender" => "Dosen " . $lecturerId,
                        ":created_at" => $tanggal . " " . date("H:i:s"),
                    ]);
                    $success = "Sesi advising tersimpan.";
                    header("Location: advising.php?nim=" . urlencode($nim));
                    exit();
                }
            } elseif ($action === "delete" && $id > 0) {
                $stmt = $pdo->prepare(
                    "DELETE FROM student_notifications WHERE id = :id",
                );
                $stmt->execute([":id" => $id]);
                $success = "Rekaman advising dihapus.";
                header("Location: advising.php?nim=" . urlencode($nim));
                exit();
            } elseif ($action === "edit" && $id > 0) {
                $msg = trim($topik !== "" ? $topik : $catatan);
                $stmt = $pdo->prepare(
                    "UPDATE student_notifications SET title = :title, content = :content, category = :category, sender = :sender, created_at = :created_at WHERE id = :id",
                );
                $stmt->execute([
                    ":title" => "Advising Session",
                    ":content" =>
                        "NIM:" .
                        $nim .
                        " | " .
                        $msg .
                        " | " .
                        ($rekomendasi !== ""
                            ? "Rekomendasi:" . $rekomendasi
                            : "") .
                        " | " .
                        ($followup !== "" ? "Follow-up:" . $followup : ""),
                    ":category" => "BIMBINGAN",
                    ":sender" => "Dosen " . $lecturerId,
                    ":created_at" => $tanggal . " " . date("H:i:s"),
                    ":id" => $id,
                ]);
                $success = "Sesi advising diperbarui.";
                header("Location: advising.php?nim=" . urlencode($nim));
                exit();
            }
        } catch (PDOException $e) {
            $error = "Gagal menyimpan sesi advising.";
        }
    }
}

// Fetch filter options (from HEAD)
$programs = [];
try {
    $stmt = $pdo->query(
        "SELECT DISTINCT study_program FROM students ORDER BY study_program ASC",
    );
    $programs = $stmt->fetchAll();
} catch (PDOException $e) {
    $programs = [];
}

$academicYears = [];
try {
    $stmt = $pdo->query(
        "SELECT DISTINCT academic_year FROM students ORDER BY academic_year DESC",
    );
    $academicYears = $stmt->fetchAll();
} catch (PDOException $e) {
    $academicYears = [];
}

$defaultAcademicYear = !empty($academicYears)
    ? (string) ($academicYears[0]["academic_year"] ?? "")
    : "";
if ($academicYear === "") {
    $academicYear = $defaultAcademicYear;
}

// Fetch filtered advisees (from HEAD)
$advisees = [];
try {
    $sql =
        "SELECT nim, first_name, last_name, study_program, academic_year, gpa, academic_status FROM students";
    $filters = [];
    $params = [];

    if ($search !== "") {
        $filters[] = "(nim LIKE :q OR first_name LIKE :q OR last_name LIKE :q)";
        $params[":q"] = "%" . $search . "%";
    }
    if ($program !== "") {
        $filters[] = "study_program = :program";
        $params[":program"] = $program;
    }
    if ($academicYear !== "") {
        $filters[] = "academic_year = :academic_year";
        $params[":academic_year"] = $academicYear;
    }
    if ($academicStatus !== "") {
        $filters[] = "academic_status = :academic_status";
        $params[":academic_status"] = $academicStatus;
    }
    if ($advisingStatus === "REQUIRES_ATTENTION") {
        $filters[] = "(gpa IS NOT NULL AND gpa < 2.7)";
    } elseif ($advisingStatus === "ON_TRACK") {
        $filters[] = "(gpa IS NOT NULL AND gpa >= 2.7)";
    }

    if (!empty($filters)) {
        $sql .= " WHERE " . implode(" AND ", $filters);
    }
    $sql .= " ORDER BY academic_year DESC, last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $advisees = $stmt->fetchAll();
} catch (PDOException $e) {
    $advisees = [];
}

// Fetch selected student profile and chat info
$profile = null;
$chat_history = [];
$advisedSessions = [];
$selected_student_user_id = 0;

if ($selectedNim !== "") {
    try {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.id as student_user_id FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.nim = :nim LIMIT 1",
        );
        $stmt->execute([":nim" => $selectedNim]);
        $profile = $stmt->fetch();

        if ($profile) {
            $selected_student_user_id =
                (int) ($profile["student_user_id"] ?? 0);

            // Chat history (from 'theirs')
            if ($selected_student_user_id) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC",
                );
                $stmt->execute([
                    $lecturerId,
                    $selected_student_user_id,
                    $selected_student_user_id,
                    $lecturerId,
                ]);
                $chat_history = $stmt->fetchAll();
            }

            // Formal sessions (from HEAD)
            $stmt = $pdo->prepare(
                "SELECT id, title, content, category, sender, created_at FROM student_notifications WHERE category = 'BIMBINGAN' AND content LIKE :nim_pattern ORDER BY created_at DESC",
            );
            $stmt->execute([":nim_pattern" => "NIM:" . $selectedNim . "%"]);
            $advisedSessions = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $profile = null;
    }
}

// Global stats (from HEAD)
$adviseesStats = [
    "total" => "0",
    "active" => "0",
    "final_project" => "0",
    "graduation_candidates" => "0",
    "attention" => "0",
];
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM students");
    $adviseesStats["total"] = (string) ($stmt->fetch()["c"] ?? 0);
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS c FROM students WHERE academic_status = 'ON_TRACK'",
    );
    $adviseesStats["active"] = (string) ($stmt->fetch()["c"] ?? 0);
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS c FROM students WHERE academic_status = 'AT_RISK' AND (gpa IS NULL OR gpa < 2.7)",
    );
    $adviseesStats["attention"] = (string) ($stmt->fetch()["c"] ?? 0);
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS c FROM students WHERE academic_year >= (YEAR(CURDATE()) - 1)",
    );
    $adviseesStats["final_project"] = (string) ($stmt->fetch()["c"] ?? 0);
    $adviseesStats["graduation_candidates"] = $adviseesStats["final_project"];
} catch (PDOException $e) {
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Bimbingan Akademik</h1>
            <p>Monitoring kemajuan akademik dan komunikasi langsung dengan mahasiswa.</p>
        </div>
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Advisees</span><span class="metric-value"><?= htmlspecialchars(
                $adviseesStats["total"],
            ) ?></span></div>
            <div class="metric-card"><span class="metric-label">Active Advisees</span><span class="metric-value"><?= htmlspecialchars(
                $adviseesStats["active"],
            ) ?></span></div>
            <div class="metric-card"><span class="metric-label">Final Project</span><span class="metric-value"><?= htmlspecialchars(
                $adviseesStats["final_project"],
            ) ?></span></div>
            <div class="metric-card"><span class="metric-label">Candidates</span><span class="metric-value"><?= htmlspecialchars(
                $adviseesStats["graduation_candidates"],
            ) ?></span></div>
        </div>
    </section>

    <div class="split-grid" style="gap: 16px; align-items:start;">
        <!-- Left Column: Student List -->
        <div class="content-card" style="flex: 1;">
            <div class="card-top">
                <h3>Daftar Mahasiswa</h3>
                <span class="action-link" style="cursor:default;">Attention: <?= htmlspecialchars(
                    $adviseesStats["attention"],
                ) ?></span>
            </div>

            <div style="padding: 0 16px; margin-top: -6px;">
                <form method="get" style="display:grid; gap: 12px;">
                    <input type="text" name="q" value="<?= htmlspecialchars(
                        $search,
                    ) ?>" placeholder="Cari mahasiswa..." style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Program Studi</span>
                            <select name="program" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">Semua</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= htmlspecialchars(
                                        (string) $p["study_program"],
                                    ) ?>" <?= $program ===
(string) $p["study_program"]
    ? "selected"
    : "" ?>><?= htmlspecialchars((string) $p["study_program"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label style="display:block;">
                            <span style="display:block; font-size:0.78rem; font-weight:700; color:var(--charcoal);">Angkatan</span>
                            <select name="academic_year" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;">
                                <option value="">Semua</option>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= htmlspecialchars(
                                        (string) $ay["academic_year"],
                                    ) ?>" <?= $academicYear ===
(string) $ay["academic_year"]
    ? "selected"
    : "" ?>><?= htmlspecialchars((string) $ay["academic_year"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <button type="submit" class="trigger-btn btn-blue" style="border:none;">Terapkan</button>
                    <a href="advising.php" class="trigger-btn" style="background-color: var(--primary); color:white; text-decoration:none; text-align:center;">Reset</a>
                </form>
            </div>

            <div class="agenda-table-wrapper" style="margin: 14px 16px 16px;">
                <div class="agenda-table-header" style="grid-template-columns: 140px 1fr 100px;">
                    <span class="col-head">MAHASISWA</span>
                    <span class="col-head">PRODI</span>
                    <span class="col-head">STATUS</span>
                </div>

                <div class="agenda-rows-stack">
                    <?php if (empty($advisees)): ?>
                        <div class="empty-fallback-text border-box-pad">Tidak ada mahasiswa ditemukan.</div>
                    <?php else: ?>
                        <?php foreach ($advisees as $st): ?>
                            <?php
                            $gpaVal = $st["gpa"] ?? null;
                            $isAttention =
                                $gpaVal !== null && (float) $gpaVal < 2.7;
                            ?>
                            <div class="agenda-table-row" style="grid-template-columns: 140px 1fr 100px; <?= $selectedNim ===
                            $st["nim"]
                                ? "background: #f3f4f6;"
                                : "" ?>">
                                <div class="col-cell cell-mid-desc">
                                    <span class="item-main-headline"><?= htmlspecialchars(
                                        $st["first_name"] .
                                            " " .
                                            $st["last_name"],
                                    ) ?></span>
                                    <span class="item-sub-clock">NIM <?= htmlspecialchars(
                                        $st["nim"],
                                    ) ?></span>
                                    <a href="advising.php?nim=<?= urlencode(
                                        (string) $st["nim"],
                                    ) ?>" class="action-link" style="display:inline-block; margin-top: 6px;">Lihat Profil</a>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="item-main-headline" style="font-size:0.75rem;"><?= htmlspecialchars(
                                        $st["study_program"],
                                    ) ?></span>
                                    <span class="item-sub-clock"><?= htmlspecialchars(
                                        $st["academic_year"],
                                    ) ?></span>
                                </div>
                                <div class="col-cell cell-room-loc">
                                    <span class="label-badge red" style="display:inline-flex; margin-top: 6px; opacity: <?= $isAttention
                                        ? "1"
                                        : "0.4" ?>;"><?= $isAttention
    ? "Attention"
    : "OK" ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Chat and Advising -->
        <div style="flex: 2; display: flex; flex-direction: column; gap: 16px;">

            <!-- Student Profile Card (from HEAD) -->
            <?php if ($profile): ?>
                <div class="content-card">
                    <div class="card-top">
                        <h3>Profil Mahasiswa</h3>
                        <span class="action-link" style="cursor:default;">NIM: <?= htmlspecialchars(
                            $profile["nim"],
                        ) ?></span>
                    </div>
                    <div class="announcements-feed" style="padding: 16px;">
                        <div class="annc-node" style="margin-bottom:0;">
                            <div class="annc-top-line">
                                <div class="annc-icon-frame blue">
                                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="annc-title-area">
                                    <h4><?= htmlspecialchars(
                                        $profile["first_name"] .
                                            " " .
                                            $profile["last_name"],
                                    ) ?></h4>
                                    <p class="annc-body-text" style="margin:6px 0 0 0;"><?= htmlspecialchars(
                                        $profile["study_program"],
                                    ) ?> · Angkatan <?= htmlspecialchars(
     $profile["academic_year"],
 ) ?></p>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px;">
                                <div>
                                    <span style="font-size:0.75rem; color:#6b7280; display:block;">IPK Kumulatif</span>
                                    <span style="font-size:1.25rem; font-weight:700; color:var(--primary);"><?= htmlspecialchars(
                                        (string) ($profile["gpa"] ?? "—"),
                                    ) ?></span>
                                </div>
                                <div>
                                    <span style="font-size:0.75rem; color:#6b7280; display:block;">Status Akademik</span>
                                    <span class="label-badge <?= $profile[
                                        "academic_status"
                                    ] === "AT_RISK"
                                        ? "red"
                                        : "" ?>" style="display:inline-block; margin-top:4px;"><?= htmlspecialchars(
    (string) ($profile["academic_status"] ?? "NORMAL"),
) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Room (from 'theirs') -->
                <div class="content-card" style="display: flex; flex-direction: column; height: 500px;">
                    <div class="card-top">
                        <h3>Ruang Konsultasi</h3>
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <?php if (empty($chat_history)): ?>
                            <div style="text-align: center; color: #6b7280; margin-top: 20px;">Belum ada pesan. Mulai percakapan sekarang.</div>
                        <?php else: ?>
                            <?php foreach ($chat_history as $msg): ?>
                                <?php $is_me =
                                    $msg["sender_id"] == $lecturerId; ?>
                                <div style="max-width: 75%; padding: 10px 14px; border-radius: 12px; <?= $is_me
                                    ? "align-self: flex-end; background: #D2232A; color: white;"
                                    : "align-self: flex-start; background: white; border: 1px solid #e5e7eb; color: #1F2937;" ?>">
                                    <div style="margin-bottom: 4px;"><?= nl2br(
                                        htmlspecialchars($msg["message"]),
                                    ) ?></div>
                                    <div style="font-size: 0.65rem; text-align: right; <?= $is_me
                                        ? "color: #ffcccc;"
                                        : "color: #9ca3af;" ?>">
                                        <?= htmlspecialchars(
                                            new DateTime(
                                                $msg["created_at"],
                                            )->format("d M H:i"),
                                        ) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 16px;">
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="receiver_id" value="<?= $selected_student_user_id ?>">
                            <input type="hidden" name="nim" value="<?= htmlspecialchars(
                                $profile["nim"],
                            ) ?>">
                            <input type="text" name="message" required placeholder="Ketik pesan bimbingan..." style="flex: 1; padding: 10px; border-radius: 20px; border: 1px solid #e5e7eb; outline: none;">
                            <button type="submit" class="trigger-btn btn-blue" style="border: none; border-radius: 20px; padding: 10px 20px;">Kirim</button>
                        </form>
                    </div>
                </div>

                <!-- Formal Advising Record Form (from HEAD) -->
                <div class="content-card">
                    <div class="card-top">
                        <h3>Catatan Sesi Bimbingan (Formal)</h3>
                    </div>
                    <form method="POST" style="padding: 16px;">
                        <input type="hidden" name="action" value="create" />
                        <input type="hidden" name="nim" value="<?= htmlspecialchars(
                            (string) $profile["nim"],
                        ) ?>" />

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display:block;">
                                <span style="font-size:0.75rem; font-weight:700;">Tanggal Sesi</span>
                                <input required type="date" name="tanggal" value="<?= date(
                                    "Y-m-d",
                                ) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                            <label style="display:block;">
                                <span style="font-size:0.75rem; font-weight:700;">Topik</span>
                                <input type="text" name="topik" placeholder="Misal: Progres Skripsi" style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;" />
                            </label>
                        </div>

                        <div style="margin-top: 12px;">
                            <label style="display:block;">
                                <span style="font-size:0.75rem; font-weight:700;">Catatan / Rekomendasi</span>
                                <textarea name="catatan" rows="3" placeholder="Tulis catatan penting sesi bimbingan di sini..." style="width:100%; padding:10px; border-radius:10px; border:1px solid #e5e7eb;"></textarea>
                            </label>
                        </div>

                        <div style="margin-top: 16px;">
                            <button type="submit" class="trigger-btn btn-blue" style="border:none; width:100%;">Simpan Catatan Formal</button>
                        </div>
                    </form>
                </div>

                <!-- History (from HEAD) -->
                <div class="content-card">
                    <div class="card-top">
                        <h3>Riwayat Bimbingan Formal</h3>
                    </div>
                    <div class="tasks-vertical-stack" style="padding: 0 16px 16px;">
                        <?php if (empty($advisedSessions)): ?>
                            <div class="empty-fallback-text border-box-pad">Belum ada rekaman bimbingan formal.</div>
                        <?php else: ?>
                            <?php foreach ($advisedSessions as $s): ?>
                                <?php
                                $content = (string) ($s["content"] ?? "");
                                $created = (string) ($s["created_at"] ?? "");
                                $rawParts = explode("|", $content);
                                $topicDisplay = $s["title"] ?? "Bimbingan";
                                $bodyDisplay = "";
                                foreach ($rawParts as $p) {
                                    $p = trim($p);
                                    if (stripos($p, "NIM:") === 0) {
                                        continue;
                                    }
                                    if ($bodyDisplay === "") {
                                        $bodyDisplay = $p;
                                    } else {
                                        $bodyDisplay .= " | " . $p;
                                    }
                                }
                                ?>
                                <div class="task-node" style="padding: 12px; border-bottom: 1px solid #f3f4f6;">
                                    <div class="task-node-details">
                                        <span class="task-node-title" style="font-weight:700;"><?= htmlspecialchars(
                                            $topicDisplay,
                                        ) ?></span>
                                        <p style="font-size:0.85rem; color:#4b5563; margin: 4px 0;"><?= htmlspecialchars(
                                            $bodyDisplay,
                                        ) ?></p>
                                        <span class="task-node-time"><?= htmlspecialchars(
                                            $created,
                                        ) ?></span>
                                    </div>
                                    <form method="POST" style="margin-left:auto;">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="id" value="<?= (int) ($s[
                                            "id"
                                        ] ?? 0) ?>" />
                                        <input type="hidden" name="nim" value="<?= htmlspecialchars(
                                            $selectedNim,
                                        ) ?>" />
                                        <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.75rem;">Hapus</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- No Student Selected -->
                <div class="content-card">
                    <div style="padding: 40px; text-align: center; color: #6b7280;">
                        <svg viewBox="0 0 24 24" style="width: 48px; height: 48px; fill: #d1d5db; margin-bottom: 16px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        <p>Silakan pilih mahasiswa dari daftar di sebelah kiri untuk melihat profil dan memulai bimbingan.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px; border-radius: 8px;"><?= htmlspecialchars(
                    $success,
                ) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 8px;"><?= htmlspecialchars(
                    $error,
                ) ?></div>
            <?php endif; ?>

        </div>
    </div>
</main>

</div>
</body>
</html>
