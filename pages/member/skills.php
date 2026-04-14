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

$allowed_levels = ['beginner', 'intermediate', 'advanced', 'expert'];

// SCP-FU-004 / SCP-AC-004: Delete via POST + CSRF, validasi ownership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_skill') {
    CSRF::verify();
    $skill_id = (int)($_POST['skill_id'] ?? 0);
    // SCP-AC-004: WHERE menyertakan user_id – IDOR prevention
    $stmt = $conn->prepare("DELETE FROM skills WHERE id = ? AND user_id = ?");
    $stmt->execute([$skill_id, $user_id]);
    header('Location: skills.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill_name'])) {
    // SCP-CSRF-002
    CSRF::verify();

    // SCP-IV-001, SCP-IV-003
    $skill_name = trim($_POST['skill_name'] ?? '');
    $level      = trim($_POST['level']      ?? '');

    if ($skill_name === '' || !in_array($level, $allowed_levels, true)) {
        $error = 'Input tidak valid.';
    } elseif (strlen($skill_name) > 100) {
        $error = 'Nama skill terlalu panjang (maks. 100 karakter).';
    } else {
        // SCP-SQL-001: Prepared statement
        $stmt = $conn->prepare("INSERT INTO skills (user_id, skill_name, level) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $skill_name, $level])) {
            $message = 'Skill berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan skill.';
        }
    }
}

// SCP-SQL-001
$stmt   = $conn->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="nav-item"><a class="nav-link active" href="skills.php">Skills</a></li>
                    <li class="nav-item"><a class="nav-link" href="education.php">Education</a></li>
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="main-content">
                <h2>My Skills</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5>Add New Skill</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>
                                    <div class="mb-3">
                                        <label for="skill_name" class="form-label">Skill Name</label>
                                        <input type="text" class="form-control" id="skill_name" name="skill_name"
                                               maxlength="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="level" class="form-label">Level</label>
                                        <select class="form-control" id="level" name="level" required>
                                            <option value="beginner">Beginner</option>
                                            <option value="intermediate">Intermediate</option>
                                            <option value="advanced">Advanced</option>
                                            <option value="expert">Expert</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Skill</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h5>My Skills</h5></div>
                            <div class="card-body">
                                <?php if ($skills): ?>
                                    <?php foreach ($skills as $skill): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <!-- SCP-OE-001: htmlspecialchars output encoding -->
                                                <strong><?php echo htmlspecialchars($skill['skill_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(ucfirst($skill['level']), ENT_QUOTES, 'UTF-8'); ?></small>
                                            </div>
                                            <!-- SCP-FU-004: Delete via POST -->
                                            <form method="POST" onsubmit="return confirm('Hapus skill ini?')">
                                                <?php echo CSRF::input(); ?>
                                                <input type="hidden" name="action"   value="delete_skill">
                                                <input type="hidden" name="skill_id" value="<?php echo (int)$skill['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Belum ada skill yang ditambahkan.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
