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

$allowed_statuses = ['pending', 'reviewed', 'accepted', 'rejected'];

// SCP-CSRF-002, SCP-AC-001: Update status – verifikasi CSRF & ownership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    CSRF::verify();

    $application_id = (int)($_POST['application_id'] ?? 0);
    $status         = trim($_POST['status'] ?? '');

    if (!in_array($status, $allowed_statuses, true) || $application_id <= 0) {
        http_response_code(400);
        exit('Invalid input');
    }

    // SCP-AC-001: Pastikan application ini milik job yang dimiliki perusahaan ini
    $stmt = $conn->prepare(
        "SELECT ja.id FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         WHERE ja.id = ? AND j.company_id = ? LIMIT 1"
    );
    $stmt->execute([$application_id, $user_id]);

    if ($stmt->fetch()) {
        // SCP-SQL-001: Prepared statement
        $upd = $conn->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $upd->execute([$status, $application_id]);
    }
    // SCP-AC-002: Fail securely – tidak ada output jika tidak diizinkan

    header('Location: applicants.php');
    exit;
}

// SCP-SQL-001: Hanya ambil pelamar dari job milik perusahaan ini
$stmt = $conn->prepare(
    "SELECT ja.id, ja.applied_at, ja.status,
            j.title AS job_title,
            u.username, u.email,
            mp.full_name, mp.phone, mp.profile_photo
     FROM job_applications ja
     JOIN jobs j ON ja.job_id = j.id
     JOIN users u ON ja.user_id = u.id
     LEFT JOIN member_profiles mp ON u.id = mp.user_id
     WHERE j.company_id = ?
     ORDER BY ja.applied_at DESC"
);
$stmt->execute([$user_id]);
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Manage Jobs</a></li>
                    <li class="nav-item"><a class="nav-link active" href="applicants.php">Applicants</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <h2>Job Applicants</h2>

                <div class="card">
                    <div class="card-body">
                        <?php if ($applicants): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th><th>Job</th>
                                            <th>Applied Date</th><th>Status</th><th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applicants as $applicant): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-secondary rounded-circle me-2"
                                                             style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                        <div>
                                                            <!-- SCP-OE-001 -->
                                                            <strong><?php echo htmlspecialchars($applicant['full_name'] ?: $applicant['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($applicant['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($applicant['job_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($applicant['applied_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    $badge = match($applicant['status']) {
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        'reviewed' => 'info',
                                                        default    => 'warning',
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($applicant['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="applicant-detail.php?id=<?php echo (int)$applicant['id']; ?>"
                                                       class="btn btn-sm btn-primary">View Details</a>

                                                    <!-- SCP-CSRF-001: CSRF token pada setiap status update -->
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle"
                                                                data-bs-toggle="dropdown">Update Status</button>
                                                        <ul class="dropdown-menu">
                                                            <?php foreach (['reviewed' => 'Mark as Reviewed', 'accepted' => 'Accept', 'rejected' => 'Reject'] as $val => $label): ?>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <?php echo CSRF::input(); ?>
                                                                    <input type="hidden" name="application_id" value="<?php echo (int)$applicant['id']; ?>">
                                                                    <input type="hidden" name="status" value="<?php echo $val; ?>">
                                                                    <button type="submit" name="update_status"
                                                                            class="dropdown-item <?php echo $val === 'rejected' ? 'text-danger' : ($val === 'accepted' ? 'text-success' : ''); ?>">
                                                                        <?php echo $label; ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>Belum Ada Pelamar</h5>
                                <p class="text-muted">Pelamar akan muncul di sini setelah ada yang mendaftar.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
