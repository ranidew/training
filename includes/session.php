<?php
/**
 * Session Management – OWASP 2025
 * SCP-SM-001: HttpOnly = 1
 * SCP-SM-002: Secure  = 1 (aktifkan di production HTTPS)
 * SCP-SM-004: Session timeout 30 menit
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly',  1);          // SCP-SM-001
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure',    0);          // SCP-SM-002: set 1 jika HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime',   1800);       // SCP-SM-004: 30 menit

    session_start();
}

// SCP-SM-004: Paksa logout jika sudah lebih dari 30 menit idle
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
?>
