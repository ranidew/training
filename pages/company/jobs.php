<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/csrf.php';

$auth = new Auth();
$auth->checkAccess('company'); // SCP-AC-002, SCP-AC-003

require_once '../../config/database.php';
$db      = new Database();
$conn    = $db->getConnection();
// SCP-AC-004: user_id dari session
$user_id = (int)$_SESSION['user_id'];

$message = '';
$error   = '';

$allowed_job_types = ['full-time', 'part-time', 'contract', 'internship'];

// ── Delete via POST + CSRF ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    CSRF::verify();
    $job_id = (int)($_POST['job_id'] ?? 0);

    // SCP-AC-004: Pastikan job milik perusahaan ini (IDOR prevention)
    $stmt = $conn->prepare("SELECT company_id FROM jobs WHERE id = ? LIMIT 1");
    $stmt->execute([$job_id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($owner && (int)$owner['company_id'] === $user_id) {
        $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND company_id = ?");
        $stmt->execute([$job_id, $user_id]);
        header('Location: jobs.php?msg=deleted');
    } else {
        header('Location: jobs.php?err=unauthorized');
    }
    exit;
}

// ── Create / Update ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    CSRF::verify();

    // SCP-IV-001, SCP-IV-003
    $title        = trim($_POST['title']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $salary_min   = (int)($_POST['salary_min']  ?? 0);
    $salary_max   = (int)($_POST['salary_max']  ?? 0);
    $location     = trim($_POST['location']     ?? '');
    $job_type     = trim($_POST['job_type']     ?? '');
    $job_id       = (int)($_POST['job_id']      ?? 0);

    if ($title === '' || $location === '' || !in_array($job_type, $allowed_job_types, true)) {
        $error = 'Input tidak valid.';
    } else {
        if ($job_id > 0) {
            // SCP-AC-004: Verifikasi ownership sebelum update (IDOR prevention)
            $stmt = $conn->prepare("SELECT company_id FROM jobs WHERE id = ? LIMIT 1");
            $stmt->execute([$job_id]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($owner && (int)$owner['company_id'] === $user_id) {
                // SCP-SQL-001: Prepared statement
                $stmt = $conn->prepare(
                    "UPDATE jobs SET title=?, description=?, requirements=?,
                     salary_min=?, salary_max=?, location=?, job_type=?
                     WHERE id=? AND company_id=?"
                );
                $stmt->execute([$title, $description, $requirements,
                                $salary_min, $salary_max, $location, $job_type,
                                $job_id, $user_id]);
                $message = 'Lowongan berhasil diperbarui!';
            } else {
                $error = 'Tidak diizinkan: Anda hanya bisa mengedit lowongan Anda sendiri.';
            }
        } else {
            // SCP-SQL-001
            $stmt = $conn->prepare(
                "INSERT INTO jobs (company_id, title, description, requirements,
                 salary_min, salary_max, location, job_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$user_id, $title, $description, $requirements,
                            $salary_min, $salary_max, $location, $job_type]);
            $message = 'Lowongan berhasil dibuat!';
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $message = 'Lowongan berhasil dihapus!';
if (isset($_GET['err']) && $_GET['err'] === 'unauthorized') $error = 'Tidak diizinkan.';

// SCP-SQL-001
$stmt = $conn->prepare("SELECT * FROM jobs WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit_job = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND company_id = ?");
    $stmt->execute([$edit_id, $user_id]);
    $edit_job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_job) {
        $error = 'Tidak diizinkan: Anda hanya bisa mengedit lowongan Anda sendiri.';
    }
}

require_once '../../templates/header.php';
require_once '../../templates/nav.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="sidebar p-3">
                <h5>Company Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="jobs.php">Manage Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="applicants.php">Applicants</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Jobs</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">
                        <i class="fas fa-plus"></i> Post New Job
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if ($jobs): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th><th>Location</th><th>Type</th>
                                            <th>Status</th><th>Posted</th><th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <!-- SCP-OE-001 -->
                                                <td><?php echo htmlspecialchars($job['title'],    ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(ucfirst($job['job_type']), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $job['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($job['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo (int)$job['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                    <a href="job-applicants.php?job_id=<?php echo (int)$job['id']; ?>" class="btn btn-sm btn-info">Applicants</a>
                                                    <!-- SCP-FU-004: Delete via POST -->
                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirm('Hapus lowongan ini?')">
                                                        <?php echo CSRF::input(); ?>
                                                        <input type="hidden" name="action" value="delete_job">
                                                        <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5>Belum Ada Lowongan</h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">
                                    Buat Lowongan Pertama
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $edit_job ? 'Edit Job' : 'Post New Job'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>
                <div class="modal-body">
                    <?php if ($edit_job): ?>
                        <input type="hidden" name="job_id" value="<?php echo (int)$edit_job['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="title" name="title" maxlength="200"
                               value="<?php echo htmlspecialchars($edit_job['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($edit_job['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?php echo htmlspecialchars($edit_job['requirements'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Minimum Salary</label>
                                <input type="number" class="form-control" name="salary_min" min="0"
                                       value="<?php echo (int)($edit_job['salary_min'] ?? 0); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum Salary</label>
                                <input type="number" class="form-control" name="salary_max" min="0"
                                       value="<?php echo (int)($edit_job['salary_max'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" maxlength="100"
                                       value="<?php echo htmlspecialchars($edit_job['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Job Type</label>
                                <select class="form-control" name="job_type" required>
                                    <?php foreach ($allowed_job_types as $jt): ?>
                                        <option value="<?php echo $jt; ?>"
                                            <?php echo ($edit_job['job_type'] ?? '') === $jt ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(str_replace('-', ' ', $jt)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_job ? 'Update Job' : 'Post Job'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($edit_job): ?>
<script>
    var jobModal = new bootstrap.Modal(document.getElementById('jobModal'));
    jobModal.show();
</script>
<?php endif; ?>

<?php require_once '../../templates/footer.php'; ?>
