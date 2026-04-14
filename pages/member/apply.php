<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/csrf.php';

$auth = new Auth();
$auth->checkAccess('member'); // SCP-AC-002, SCP-AC-003

require_once '../../config/database.php';
$db      = new Database();
$conn    = $db->getConnection();

// SCP-IV-001, SCP-IV-003: Validasi job_id dari URL
$job_id  = (int)($_GET['id'] ?? 0);
// SCP-AC-004: user_id dari session saja – tidak bisa dimanipulasi
$user_id = (int)$_SESSION['user_id'];

if ($job_id <= 0) {
    header('Location: jobs.php');
    exit;
}

$message = '';
$error   = '';

// SCP-SQL-001: Prepared statement
$stmt = $conn->prepare(
    "SELECT j.*, c.company_name FROM jobs j
     LEFT JOIN company_profiles c ON j.company_id = c.user_id
     WHERE j.id = ? AND j.status = 'active'"
);
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: jobs.php');
    exit;
}

// SCP-SQL-001: Cek apakah sudah melamar
$stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$job_id, $user_id]);
if ($stmt->fetch()) {
    header('Location: job-detail.php?id=' . $job_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002
    CSRF::verify();

    // SCP-IV-001, SCP-IV-003
    $cover_letter = trim($_POST['cover_letter'] ?? '');

    if ($cover_letter === '') {
        $error = 'Cover letter wajib diisi.';
    } elseif (strlen($cover_letter) > 5000) {
        $error = 'Cover letter terlalu panjang (maks. 5000 karakter).';
    } else {
        // SCP-SQL-001: Prepared statement
        $stmt = $conn->prepare(
            "INSERT INTO job_applications (job_id, user_id, cover_letter)
             VALUES (?, ?, ?)"
        );
        if ($stmt->execute([$job_id, $user_id, $cover_letter])) {
            $message = 'Lamaran berhasil dikirim!';
        } else {
            $error = 'Gagal mengirim lamaran.';
        }
    }
}

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
                    <li class="nav-item"><a class="nav-link" href="cv.php">CV</a></li>
                    <li class="nav-item"><a class="nav-link" href="skills.php">Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="education.php">Education</a></li>
                    <li class="nav-item"><a class="nav-link active" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="jobs.php">Jobs</a></li>
                        <li class="breadcrumb-item">
                            <a href="job-detail.php?id=<?php echo $job_id; ?>">
                                <?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Apply</li>
                    </ol>
                </nav>

                <h2>Apply for Job</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        <br>
                        <a href="job-detail.php?id=<?php echo $job_id; ?>" class="btn btn-primary mt-2">Back to Job</a>
                        <a href="history.php" class="btn btn-info mt-2">View Applications</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Job Details</h5>
                                    <!-- SCP-OE-001 -->
                                    <h6><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                    <p class="text-muted"><?php echo htmlspecialchars($job['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

                                    <form method="POST">
                                        <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>
                                        <div class="mb-3">
                                            <label for="cover_letter" class="form-label">Cover Letter</label>
                                            <textarea class="form-control" id="cover_letter" name="cover_letter"
                                                      rows="8" maxlength="5000"
                                                      placeholder="Write your cover letter here..." required></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success">Submit Application</button>
                                            <a href="job-detail.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Application Tips</h6>
                                    <ul class="small">
                                        <li>Pastikan profil Anda lengkap</li>
                                        <li>Upload CV terbaru Anda</li>
                                        <li>Tulis cover letter yang menarik</li>
                                        <li>Tonjolkan skill dan pengalaman relevan</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
