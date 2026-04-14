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
// SCP-AC-004: user_id dari session
$user_id = (int)$_SESSION['user_id'];

$message = '';
$error   = '';

// SCP-FU-004 / SCP-AC-004: Delete via POST + CSRF + ownership check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_education') {
    CSRF::verify();
    $edu_id = (int)($_POST['education_id'] ?? 0);
    // SCP-AC-004: WHERE user_id – IDOR prevention
    $stmt = $conn->prepare("DELETE FROM education WHERE id = ? AND user_id = ?");
    $stmt->execute([$edu_id, $user_id]);
    header('Location: education.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['institution'])) {
    CSRF::verify();

    // SCP-IV-001, SCP-IV-003: Validasi server-side
    $institution    = trim($_POST['institution']    ?? '');
    $degree         = trim($_POST['degree']         ?? '');
    $field_of_study = trim($_POST['field_of_study'] ?? '');
    $start_date     = trim($_POST['start_date']     ?? '');
    $end_date       = trim($_POST['end_date']       ?? '') ?: null;

    if ($institution === '' || $degree === '' || $field_of_study === '' || $start_date === '') {
        $error = 'Semua field wajib diisi kecuali tanggal selesai.';
    } elseif (!strtotime($start_date) || ($end_date && !strtotime($end_date))) {
        $error = 'Format tanggal tidak valid.';
    } else {
        // SCP-SQL-001: Prepared statement
        $stmt = $conn->prepare(
            "INSERT INTO education (user_id, institution, degree, field_of_study, start_date, end_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if ($stmt->execute([$user_id, $institution, $degree, $field_of_study, $start_date, $end_date])) {
            $message = 'Pendidikan berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan pendidikan.';
        }
    }
}

// SCP-SQL-001
$stmt = $conn->prepare("SELECT * FROM education WHERE user_id = ? ORDER BY start_date DESC");
$stmt->execute([$user_id]);
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="nav-item"><a class="nav-link active" href="education.php">Education</a></li>
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <h2>Education</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h5>Add Education</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="institution" class="form-label">Institution</label>
                                        <input type="text" class="form-control" id="institution" name="institution"
                                               maxlength="200" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="degree" class="form-label">Degree</label>
                                        <input type="text" class="form-control" id="degree" name="degree"
                                               maxlength="100" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="field_of_study" class="form-label">Field of Study</label>
                                <input type="text" class="form-control" id="field_of_study" name="field_of_study"
                                       maxlength="100" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Education</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>My Education</h5></div>
                    <div class="card-body">
                        <?php if ($educations): ?>
                            <?php foreach ($educations as $edu): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <!-- SCP-OE-001 -->
                                            <h6><?php echo htmlspecialchars($edu['degree'], ENT_QUOTES, 'UTF-8'); ?> in <?php echo htmlspecialchars($edu['field_of_study'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <p class="mb-1"><strong><?php echo htmlspecialchars($edu['institution'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                            <small class="text-muted">
                                                <?php echo date('M Y', strtotime($edu['start_date'])); ?> –
                                                <?php echo $edu['end_date'] ? date('M Y', strtotime($edu['end_date'])) : 'Present'; ?>
                                            </small>
                                        </div>
                                        <!-- SCP-AC-004: Delete via POST -->
                                        <form method="POST" onsubmit="return confirm('Hapus data pendidikan ini?')">
                                            <?php echo CSRF::input(); ?>
                                            <input type="hidden" name="action"       value="delete_education">
                                            <input type="hidden" name="education_id" value="<?php echo (int)$edu['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Belum ada data pendidikan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
