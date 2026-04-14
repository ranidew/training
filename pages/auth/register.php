<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/auth.php';
require_once '../../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002
    CSRF::verify();

    // SCP-IV-001, SCP-IV-002: Validasi server-side
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email']    ?? '');
    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role             = $_POST['role']             ?? '';

    if ($username === '' || $email === '' || $password === '' || $role === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!in_array($role, ['member', 'company'], true)) {
        $error = 'Role tidak valid.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok.';
    } else {
        $auth   = new Auth();
        $result = $auth->register($username, $email, $password, $role);

        if (isset($result['token'])) {
            header('Location: registration-success.php?token=' . urlencode($result['token']));
            exit;
        } else {
            // SCP-EH-003: Pesan dari auth sudah generik
            $error = $result['error'] ?? 'Registrasi gagal. Silakan coba lagi.';
        }
    }
}

require_once '../../templates/header.php';
require_once '../../templates/nav.php';

$default_role = in_array($_GET['role'] ?? '', ['member', 'company']) ? $_GET['role'] : 'member';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Register</h4>
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
                                   maxlength="50" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   maxlength="100" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required autocomplete="new-password">
                            <div class="form-text">
                                Min. 8 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="member"  <?php echo $default_role === 'member'  ? 'selected' : ''; ?>>Job Seeker</option>
                                <option value="company" <?php echo $default_role === 'company' ? 'selected' : ''; ?>>Company</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
