<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../config/database.php';
require_once '../../includes/csrf.php';

$message = '';
$error   = '';
$token   = trim($_GET['token'] ?? '');

// SCP-IV-001: Validasi token format (hex 64 karakter)
if ($token !== '' && !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $token = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002
    CSRF::verify();

    $token            = trim($_POST['token'] ?? '');
    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $error = 'Token tidak valid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok.';
    } else {
        $db   = new Database();
        $conn = $db->getConnection();

        // SCP-SQL-001: Prepared statement + SCP-APM-005: cek expiry
        $stmt = $conn->prepare(
            "SELECT id FROM users
             WHERE verification_token = ? AND token_expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // SCP-APM-001: Validasi kompleksitas
            if (strlen($password) < 8
                || !preg_match('/[A-Z]/', $password)
                || !preg_match('/[a-z]/', $password)
                || !preg_match('/[0-9]/', $password)
                || !preg_match('/[\W_]/', $password)) {
                $error = 'Password harus minimal 8 karakter, huruf besar, huruf kecil, angka, dan simbol.';
            } else {
                // SCP-APM-002: Hash bcrypt
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // SCP-APM-005: Invalidasi token setelah digunakan
                $upd = $conn->prepare(
                    "UPDATE users
                     SET password = ?, verification_token = NULL, token_expires_at = NULL
                     WHERE id = ?"
                );
                if ($upd->execute([$hashed, $user['id']])) {
                    $message = 'Password berhasil direset. Silakan login.';
                } else {
                    $error = 'Gagal mereset password.';
                }
            }
        } else {
            $error = 'Token tidak valid atau sudah kadaluarsa.';
        }
    }
}

require_once '../../templates/header.php';
require_once '../../templates/nav.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4>Reset Password</h4></div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <a href="login.php" class="btn btn-primary">Login Sekarang</a>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($token): ?>
                        <form method="POST">
                            <?php echo CSRF::input(); ?>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password" name="password"
                                       required autocomplete="new-password">
                                <div class="form-text">Min. 8 karakter, huruf besar, huruf kecil, angka, dan simbol.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       required autocomplete="new-password">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-warning">Token tidak valid. Silakan <a href="forgot-password.php">minta link baru</a>.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
