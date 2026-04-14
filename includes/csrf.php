<?php
/**
 * CSRF Helper – OWASP 2025
 * SCP-CSRF-001: Generate token per-session
 * SCP-CSRF-002: Validasi token di server
 */
class CSRF {
    private static string $key = '_csrf_token';

    /** Generate token dan simpan di session */
    public static function generate(): string {
        if (empty($_SESSION[self::$key])) {
            $_SESSION[self::$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$key];
    }

    /** Output hidden input field untuk form */
    public static function input(): string {
        $token = self::generate();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validasi token dari POST; terminate jika tidak valid
     */
    public static function verify(): void {
        $submitted = $_POST['_csrf_token'] ?? '';
        $expected  = $_SESSION[self::$key] ?? '';

        if (!$expected || !hash_equals($expected, $submitted)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1><p>Invalid CSRF token.</p></body></html>';
            exit;
        }

        // Rotate token setelah verifikasi
        unset($_SESSION[self::$key]);
    }
}
?>
