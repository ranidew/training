<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/file_upload.php';
require_once '../../includes/csrf.php';

$auth = new Auth();
$auth->checkAccess('member'); // SCP-AC-002, SCP-AC-003

$message = '';
$error   = '';
// SCP-AC-004: user_id dari session, bukan dari input
$user_id = (int)$_SESSION['user_id'];

require_once '../../config/database.php';
$db   = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002
    CSRF::verify();

    // SCP-IV-001, SCP-IV-003: Validasi & sanitasi input
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $address   = trim($_POST['address']   ?? '');

    if (strlen($full_name) > 100 || strlen($phone) > 20 || strlen($address) > 500) {
        $error = 'Input melebihi batas panjang yang diizinkan.';
    } elseif ($phone !== '' && !preg_match('/^[\d\+\-\(\) ]+$/', $phone)) {
        $error = 'Format nomor telepon tidak valid.';
    } else {
        $photo_path = null;

        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            // SCP-FU-001, SCP-FU-002, SCP-FU-003, SCP-FU-005
            $uploaded = FileUpload::uploadFile(
                $_FILES['profile_photo'],
                'profiles',
                UPLOAD_ALLOWED_PHOTO_TYPES
            );
            if ($uploaded === false) {
                $error = 'File foto tidak valid. Hanya JPG, PNG, GIF maks 5 MB.';
            } else {
                $photo_path = $uploaded;
            }
        }

        if (!$error) {
            // SCP-SQL-001: Prepared statement
            if ($photo_path !== null) {
                $stmt = $conn->prepare(
                    "INSERT INTO member_profiles (user_id, full_name, phone, address, profile_photo)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                     full_name = VALUES(full_name),
                     phone = VALUES(phone),
                     address = VALUES(address),
                     profile_photo = VALUES(profile_photo)"
                );
                $stmt->execute([$user_id, $full_name, $phone, $address, $photo_path]);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO member_profiles (user_id, full_name, phone, address)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                     full_name = VALUES(full_name),
                     phone = VALUES(phone),
                     address = VALUES(address)"
                );
                $stmt->execute([$user_id, $full_name, $phone, $address]);
            }
            header('Location: profile.php?msg=success');
            exit;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $message = 'Profil berhasil diperbarui!';
}

// SCP-SQL-001
$stmt = $conn->prepare("SELECT * FROM member_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../../templates/header.php';
require_once '../../templates/nav.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar p-3">
                <h5>Member Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="cv.php">CV</a></li>
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <h2>My Profile</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                               maxlength="100"
                                               value="<?php echo htmlspecialchars($profile['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                               maxlength="20"
                                               value="<?php echo htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" maxlength="500"><?php echo htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo"
                                       accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">JPG, PNG, GIF – maks. 5 MB.</div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
