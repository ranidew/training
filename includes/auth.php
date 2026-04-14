<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * SCP-SQL-001: Prepared statements
     * SCP-APM-002: password_verify (bcrypt)
     * SCP-SM-003: session_regenerate_id setelah login
     */
    public function login($username, $password) {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare(
            "SELECT id, username, email, password, role, is_verified
             FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // SCP-SM-003: Regenerate session ID setelah login berhasil
        session_regenerate_id(true);

        $token = JWT::encode([
            'user_id'  => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
            'exp'      => time() + 3600,  // SCP-CM-003
        ]);

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['token']    = $token;

        return $user;
    }

    /**
     * SCP-SQL-001: Prepared statements
     * SCP-APM-001: Validasi kompleksitas password
     * SCP-APM-002: password_hash bcrypt
     */
    public function register($username, $email, $password, $role) {
        if (!$this->isPasswordStrong($password)) {
            return ['error' => 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.'];
        }

        $conn = $this->db->getConnection();

        // Cek duplikat username/email
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['error' => 'Username atau email sudah digunakan.'];
        }

        $hashed   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); // SCP-APM-002
        $token    = bin2hex(random_bytes(32));
        $exp      = date('Y-m-d H:i:s', time() + 3600); // SCP-APM-005: token 1 jam

        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, role, verification_token, token_expires_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if ($stmt->execute([$username, $email, $hashed, $role, $token, $exp])) {
            $this->sendVerificationEmail($email, $token);
            return ['token' => $token];
        }

        return ['error' => 'Registrasi gagal. Silakan coba lagi.'];
    }

    /**
     * SCP-APM-001: Validasi kompleksitas password
     */
    private function isPasswordStrong($password) {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[\W_]/', $password);
    }

    private function sendVerificationEmail($email, $token) {
        $emailService = new EmailService();
        $username     = strstr($email, '@', true);
        return $emailService->sendRegistrationEmail($email, $username, $token);
    }

    /**
     * SCP-AC-002: Fail securely – redirect & exit jika akses tidak sah
     * SCP-AC-003: RBAC konsisten
     */
    public function checkAccess($required_role = null) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->getLoginUrl());
            exit;
        }

        if ($required_role && $_SESSION['role'] !== $required_role) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1><p>Anda tidak memiliki akses ke halaman ini.</p></body></html>';
            exit;
        }

        return true;
    }

    private function getLoginUrl() {
        $depth = substr_count($_SERVER['PHP_SELF'], '/');
        $prefix = str_repeat('../', $depth - 1);
        return $prefix . 'pages/auth/login.php';
    }

    /**
     * SCP-SQL-001: Prepared statement
     * SCP-AC-004: Hanya ambil user berdasarkan session, bukan parameter eksternal
     */
    public function getUserById($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
