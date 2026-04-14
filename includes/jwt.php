<?php
require_once __DIR__ . '/../config/env.php';

/**
 * JWT – OWASP 2025
 * SCP-CM-001: Verifikasi signature HMAC-SHA256
 * SCP-CM-002: Secret kuat (dari env, min 256-bit)
 * SCP-CM-003: Klaim exp wajib ada dan divalidasi
 * SCP-DP-002: Secret TIDAK pernah diekspos ke client
 */
class JWT {
    private static function secret(): string {
        return JWT_SECRET;  // dari env, tidak pernah dikirim ke browser
    }

    public static function encode(array $payload): string {
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + 3600;  // SCP-CM-003: default 1 jam
        }

        $header  = self::base64url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body    = self::base64url(json_encode($payload));
        $sig     = self::base64url(hash_hmac('sha256', "$header.$body", self::secret(), true));

        return "$header.$body.$sig";
    }

    /**
     * SCP-CM-001: Verifikasi signature; tolak jika tidak valid atau token expired
     */
    public static function decode(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $sig] = $parts;

        $expected = self::base64url(hash_hmac('sha256', "$header.$body", self::secret(), true));
        if (!hash_equals($expected, $sig)) {
            return null;  // signature tidak valid
        }

        $payload = json_decode(self::base64urlDecode($body), true);
        if (!is_array($payload)) {
            return null;
        }

        // SCP-CM-003: Validasi expiry
        if (!isset($payload['exp']) || time() > $payload['exp']) {
            return null;  // token expired
        }

        return $payload;
    }

    private static function base64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
?>
