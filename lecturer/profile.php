<?php
require_once "../config/db.php";
require_once "_layout.php";

$userId = (int)($_SESSION['user_id'] ?? 0);

$profile = [];
try {
    $stmt = $pdo->prepare("SELECT nip, first_name, last_name, birth_date, degree, expertise, email, phone, gender, address, department, study_program, academic_position, employment_status, photo_path, user_id FROM lecturers WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $profile = (array)$stmt->fetch();
} catch (PDOException $e) {
    $profile = [];
}

$username = '';
try {
    $stmt = $pdo->prepare("SELECT username, last_login, status FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $u = $stmt->fetch();
    if ($u) {
        $username = (string)($u['username'] ?? '');
        $last_login = (string)($u['last_login'] ?? '');
        $account_status = (string)($u['status'] ?? '');
    }
} catch (PDOException $e) {
}

$last_login = $last_login ?? '';
$account_status = $account_status ?? '';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim((string)($_POST['nip'] ?? ''));
    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $last_name = trim((string)($_POST['last_name'] ?? ''));
    $degree = trim((string)($_POST['degree'] ?? ''));
    $expertise = trim((string)($_POST['expertise'] ?? ''));
    $birth_date = trim((string)($_POST['birth_date'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $gender = trim((string)($_POST['gender'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $study_program = trim((string)($_POST['study_program'] ?? ''));
    $academic_position = trim((string)($_POST['academic_position'] ?? ''));
    $employment_status = trim((string)($_POST['employment_status'] ?? ''));

    try {
        $sql = "UPDATE lecturers SET first_name = :first_name, last_name = :last_name, degree = :degree, expertise = :expertise, birth_date = :birth_date";
        $sql .= ", email = :email, phone = :phone, gender = :gender, address = :address";
        $sql .= ", department = :department, study_program = :study_program, academic_position = :academic_position, employment_status = :employment_status";
        $sql .= " WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':degree' => $degree,
            ':expertise' => $expertise,
            ':birth_date' => $birth_date,
            ':email' => $email,
            ':phone' => $phone,
            ':gender' => $gender,
            ':address' => $address,
            ':department' => $department,
            ':study_program' => $study_program,
            ':academic_position' => $academic_position,
            ':employment_status' => $employment_status,
            ':user_id' => $userId,
        ]);

        $success = 'Profil berhasil diperbarui.';
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui profil.';
    }
}

if (!empty($_FILES['photo']['name'] ?? '') && is_uploaded_file($_FILES['photo']['tmp_name'] ?? '')) {
    $photoDir = '../assets/img/';
    $tmpPath = $_FILES['photo']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $allowed, true)) {
        $fileName = 'lecturer_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $photoDir . $fileName;
        if (move_uploaded_file($tmpPath, $targetPath)) {
            try {
                $stmt = $pdo->prepare("UPDATE lecturers SET photo_path = :photo_path WHERE user_id = :user_id");
                $stmt->execute([':photo_path' => $fileName, ':user_id' => $userId]);
                $success = 'Foto profil berhasil diperbarui.';
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui foto profil.';
            }
        } else {
            $error = 'Gagal menyimpan foto profil.';
        }
    } else {
        $error = 'Format foto tidak didukung.';
    }
}

$photo = $profile['photo_path'] ?? '';
$fullPhotoPath = $photo ? ('../assets/img/' . $photo) : '';

$fullName = trim((string)($profile['first_name'] ?? '')) . ' ' . trim((string)($profile['last_name'] ?? ''));
if ($fullName === '') {
    $fullName = 'Dosen';
}

$nip = (string)($profile['nip'] ?? '');
$department = (string)($profile['department'] ?? '');
$studyProgram = (string)($profile['study_program'] ?? '');
$academicPosition = (string)($profile['academic_position'] ?? '');
$expertise = (string)($profile['expertise'] ?? '');
$employmentStatus = (string)($profile['employment_status'] ?? '');
$gender = (string)($profile['gender'] ?? '');
$birthDate = (string)($profile['birth_date'] ?? '');
$address = (string)($profile['address'] ?? '');
$email = (string)($profile['email'] ?? '');
$phone = (string)($profile['phone'] ?? '');

$initials = 'D';
if ($fullName !== '') {
    $nameArray = preg_split('/\s+/', trim($fullName));
    $initials = strtoupper(substr($nameArray[0] ?? '', 0, 1) . (substr($nameArray[1] ?? '', 0, 1)));
    if ($initials === '') {
        $initials = 'D';
    }
}
?>

<main class="dashboard-viewport">
    <section class="hero-banner">
        <div class="hero-title">
            <h1>Lecturer Profile</h1>
            <p>Kelola informasi pribadi dan akademik Anda.</p>
        </div>
    </section>

    <div class="content-card" style="max-width: 980px; margin: 0 auto;">
        <div class="card-top">
            <h3>Personal Information</h3>
        </div>

        <?php if ($success !== ''): ?>
            <div class="empty-fallback-text" style="border:1px solid #3bb273; color:#1f7a4c; margin: 12px; background:#ffffff; border-radius: var(--radius);"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="empty-fallback-text" style="border:1px solid #ef4444; color:#b91c1c; margin: 12px; background:#ffffff; border-radius: var(--radius);"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="padding: 16px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display:block;">
                    <span>Full Name</span>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                        <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" required style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                    </div>
                </label>

                <label style="display:block;">
                    <span>NIP</span>
                    <input type="text" name="nip" value="<?= htmlspecialchars($nip) ?>" disabled style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#f9fafb; color:var(--charcoal); font-weight:700; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Phone</span>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Gender</span>
                    <input type="text" name="gender" value="<?= htmlspecialchars($gender) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Date of Birth</span>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($birthDate) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block; grid-column: 1 / -1;">
                    <span>Address</span>
                    <input type="text" name="address" value="<?= htmlspecialchars($address) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>
            </div>

            <div class="card-top" style="margin-top: 18px;">
                <h3>Academic Information</h3>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display:block;">
                    <span>Department</span>
                    <input type="text" name="department" value="<?= htmlspecialchars($department) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Study Program</span>
                    <input type="text" name="study_program" value="<?= htmlspecialchars($studyProgram) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Academic Position</span>
                    <input type="text" name="academic_position" value="<?= htmlspecialchars($academicPosition) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Expertise Area</span>
                    <input type="text" name="expertise" value="<?= htmlspecialchars($expertise) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Employment Status</span>
                    <input type="text" name="employment_status" value="<?= htmlspecialchars($employmentStatus) ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>

                <label style="display:block;">
                    <span>Academic Degree</span>
                    <input type="text" name="degree" value="<?= htmlspecialchars($profile['degree'] ?? '') ?>" style="width:100%; padding:10px; border-radius:10px; border:1px solid var(--border); background:#ffffff; color:var(--charcoal); font-weight:600; outline:none;" />
                </label>
            </div>

            <div class="card-top" style="margin-top: 18px;">
                <h3>Profile Photo</h3>
            </div>

            <div style="display:flex; align-items:center; gap: 14px; padding: 6px 0 12px;">
                <div class="avatar-circle" style="width:54px; height:54px; border-radius: 14px; background-color:#fff0f0; color: var(--primary); border: none; font-size: 0.95rem;">
                    <?php if ($fullPhotoPath): ?>
                        <img src="<?= htmlspecialchars($fullPhotoPath) ?>" alt="Profile" style="width:100%; height:100%; border-radius: 14px; object-fit: cover;" />
                    <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                    <?php endif; ?>
                </div>
                <label style="display:block; flex: 1;">
                    <span>Upload/Change Photo</span>
                    <input type="file" name="photo" accept="image/*" style="width:100%; padding:10px; border-radius:10px; border:1px dashed var(--border); background:#ffffff; color:var(--charcoal); font-weight:700; outline:none;" />
                </label>
            </div>

            <div style="display:flex; gap: 10px; margin-top: 14px; flex-wrap: wrap;">
                <button type="submit" class="trigger-btn btn-blue" style="border:none;">Save Profile</button>
                <a href="dashboard.php" class="trigger-btn btn-red" style="text-decoration:none; border:none; display:inline-flex; align-items:center; justify-content:center;">Back</a>
            </div>
        </form>

        <div class="card-top" style="margin-top: 12px;">
            <h3>Account Information</h3>
        </div>

        <div class="announcements-feed" style="margin-top: 8px;">
            <div class="annc-node">
                <div class="annc-top-line">
                    <div class="annc-icon-frame red">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                    </div>
                    <div class="annc-title-area">
                        <h4>Username</h4>
                        <p class="annc-body-text" style="margin:6px 0 0 0;"><?= htmlspecialchars($username) ?></p>
                    </div>
                </div>
            </div>

            <div class="annc-node">
                <div class="annc-top-line">
                    <div class="annc-icon-frame blue">
                        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 4.5z"/></svg>
                    </div>
                    <div class="annc-title-area">
                        <h4>Last Login</h4>
                        <p class="annc-body-text" style="margin:6px 0 0 0;"><?= htmlspecialchars($last_login) ?></p>
                    </div>
                </div>
            </div>

            <div class="annc-node">
                <div class="annc-top-line">
                    <div class="annc-icon-frame red">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                    <div class="annc-title-area">
                        <h4>Account Status</h4>
                        <p class="annc-body-text" style="margin:6px 0 0 0;"><?= htmlspecialchars($account_status) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</div>
</body>
</html>


