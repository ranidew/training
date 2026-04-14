<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002: Validasi CSRF token
    CSRF::verify();

    // SCP-IV-001: Validasi server-side
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $auth = new Auth();
        $user = $auth->login($username, $password);

        if ($user) {
            if ($user['role'] === 'member') {
                header('Location: ../member/dashboard.php');
            } else {
                header('Location: ../company/dashboard.php');
            }
            exit;
        } else {
            // SCP-EH-003: Pesan generik – jangan beri tahu mana yang salah
            $error = 'Username atau password tidak valid.';
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
                <div class="card-header">
                    <h4>Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   maxlength="100" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                        <p><a href="forgot-password.php">Lupa Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
