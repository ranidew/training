<?php
require_once '../../includes/session.php';
require_once '../../config/env.php';
require_once '../../includes/csrf.php';

$message = 'Jika email terdaftar, link reset password telah dikirimkan.'; // SCP-EH-003: pesan generik selalu sama
$show_message = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SCP-CSRF-002
    CSRF::verify();

    // SCP-IV-001, SCP-IV-002: Validasi email server-side
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        require_once '../../config/database.php';
        $db   = new Database();
        $conn = $db->getConnection();

        // SCP-SQL-001: Prepared statement
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            $expires_at  = date('Y-m-d H:i:s', time() + 3600); // SCP-APM-005: 1 jam

            // SCP-SQL-001
            $upd = $conn->prepare(
                "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?"
            );
            $upd->execute([$reset_token, $expires_at, $user['id']]);

            // SCP-APM-006: Hanya kirim link, bukan password
            $link = BASE_URL . '/pages/auth/reset-password.php?token=' . urlencode($reset_token);
            $body = "Klik link berikut untuk mereset password Anda (berlaku 1 jam):\n\n$link\n\nJika Anda tidak meminta reset password, abaikan email ini.";

            // SCP-IV-004: Tidak gunakan input user dalam header email
            mail($email, 'Reset Password – Job Portal', $body, 'From: noreply@jobportal.com');
        }

        // SCP-EH-003: Respon sama terlepas email ditemukan atau tidak (cegah enumeration)
        $show_message = true;
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
                    <h4>Lupa Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($show_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo CSRF::input(); /* SCP-CSRF-001 */ ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       maxlength="100" required autocomplete="email">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="login.php">Kembali ke Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>
