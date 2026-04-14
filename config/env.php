<?php
// SCP-DP-001: Credentials dibaca dari environment variable, bukan hardcode
define('DB_HOST', $_ENV['DB_HOST'] ?? 'job_seeker');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'db_job_seeker');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'root');

// SCP-CM-002: JWT secret kuat – baca dari env; fallback hanya untuk dev lokal
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)));

// Mailtrap – baca dari env
define('MAILTRAP_HOST',     $_ENV['MAILTRAP_HOST']     ?? 'sandbox.smtp.mailtrap.io');
define('MAILTRAP_PORT',     $_ENV['MAILTRAP_PORT']     ?? 2525);
define('MAILTRAP_USERNAME', $_ENV['MAILTRAP_USERNAME'] ?? '');
define('MAILTRAP_PASSWORD', $_ENV['MAILTRAP_PASSWORD'] ?? '');

// SCP-FU-001, SCP-FU-002: Whitelist tipe file yang aman; batas ukuran 5 MB
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_ALLOWED_CV_TYPES',    ['application/pdf',
                                      'application/msword',
                                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
// SCP-FU-003: Simpan di luar web root
define('UPLOAD_PATH', dirname(__DIR__) . '/private_uploads/');

// Base URL
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost:8004');
?>
