<?php
require_once '../../includes/session.php';

// SCP-SM-005: Hapus semua data session di server dan cookie di client
session_unset();
session_destroy();

// Hapus cookie session di browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: ../../index.php');
exit;
?>
