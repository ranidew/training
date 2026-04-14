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
// SCP-AC-004: user_id dari session
$user_id = (int)$_SESSION['user_id'];

require_once '../../config/database.php';
$db   = new Database();
$conn = $db->getConnection();

// SCP-FU-004: Delete via POST + CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    CSRF::verify();

    // SCP-AC-004: Pastikan CV yang dihapus milik user ini
    $stmt = $conn->prepare("SELECT cv_file FROM member_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['cv_file']) {
        FileUpload::deleteFile($row['cv_file']);
        $upd = $conn->prepare("UPDATE member_profiles SET cv_file = NULL WHERE user_id = ?");
        $upd->execute([$user_id]);
    }
    header('Location: cv.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    CSRF::verify();

    if ($_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        // SCP-FU-001, SCP-FU-002, SCP-FU-003, SCP-FU-005
        $cv_path = FileUpload::uploadFile(
            $_FILES['cv_file'],
            'cvs',
            UPLOAD_ALLOWED_CV_TYPES
        );

        if ($cv_path === false) {
            $error = 'File tidak valid. Hanya PDF, DOC, DOCX maks 5 MB.';
        } else {
            // SCP-SQL-001: Prepared statement
            $stmt = $conn->prepare(
                "INSERT INTO member_profiles (user_id, cv_file)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE cv_file = VALUES(cv_file)"
            );
            if ($stmt->execute([$user_id, $cv_path])) {
                header('Location: cv.php?msg=success');
                exit;
            } else {
                $error = 'Gagal menyimpan CV ke database.';
            }
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $message = 'CV berhasil diunggah!';
}

// SCP-SQL-001
$stmt = $conn->prepare("SELECT cv_file FROM member_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
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
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link active" href="cv.php">CV</a></li>
                    <li class="nav-item"><a class="nav-link" href="skills.php">Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="education.php">Education</a></li>
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <h2>My CV</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <!-- Upload form -->
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo CSRF::input(); ?>
                            <div class="mb-3">
                                <label for="cv_file" class="form-label">Upload CV</label>
                                <input type="file" class="form-control" id="cv_file" name="cv_file"
                                       accept=".pdf,.doc,.docx">
                                <div class="form-text">Hanya PDF, DOC, DOCX – maks. 5 MB.</div>
                            </div>

                            <?php if (!empty($profile['cv_file'])): ?>
                                <div class="mb-3 border p-3 rounded">
                                    <i class="fas fa-file-pdf text-danger"></i>
                                    <span class="ms-2">CV saat ini sudah tersimpan.</span>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary">Upload CV</button>
                        </form>

                        <!-- SCP-FU-004: Delete via POST -->
                        <?php if (!empty($profile['cv_file'])): ?>
                        <form method="POST" class="mt-3"
                              onsubmit="return confirm('Hapus CV ini?')">
                            <?php echo CSRF::input(); ?>
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Hapus CV
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
