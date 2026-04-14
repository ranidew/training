<?php
require_once __DIR__ . '/../config/env.php';

/**
 * FileUpload – OWASP 2025
 * SCP-FU-001: Whitelist MIME type
 * SCP-FU-002: Batas ukuran file
 * SCP-FU-003: Simpan di luar web root (private_uploads/)
 * SCP-FU-004: Operasi delete via POST + CSRF (dipanggil dari controller)
 * SCP-FU-005: Rename dengan UUID
 */
class FileUpload {

    /**
     * @param array  $file       $_FILES['...']
     * @param string $subfolder  'profiles' | 'cvs'
     * @param array  $allowed    MIME types yang diizinkan
     * @return string|false      path relatif terhadap UPLOAD_PATH, atau false
     */
    public static function uploadFile(array $file, string $subfolder, array $allowed): string|false {
        // SCP-FU-002: Validasi ukuran
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            return false;
        }

        // SCP-FU-001: Validasi MIME type via finfo (bukan ekstensi)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $allowed, true)) {
            return false;
        }

        // SCP-FU-003: Direktori di luar web root
        $target_dir = UPLOAD_PATH . ($subfolder ? $subfolder . '/' : '');
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0750, true);
        }

        // SCP-FU-005: UUID sebagai nama file + ekstensi yang aman dari MIME
        $ext      = self::mimeToExt($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $target   = $target_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $subfolder . '/' . $filename;
        }

        return false;
    }

    /**
     * Hapus file dari private storage (panggil setelah validasi CSRF & ownership)
     */
    public static function deleteFile(string $filepath): bool {
        // Path traversal protection
        $safe = basename($filepath);
        if ($safe !== $filepath && strpos($filepath, '/') !== false) {
            // Hanya izinkan subfolder/filename
            $parts = explode('/', $filepath, 2);
            if (count($parts) === 2) {
                $safe = $parts[0] . '/' . basename($parts[1]);
            }
        }

        $full_path = UPLOAD_PATH . $safe;
        // Pastikan path tetap di dalam UPLOAD_PATH
        $real_upload = realpath(UPLOAD_PATH);
        $real_file   = realpath(dirname($full_path)) . '/' . basename($full_path);
        if ($real_upload && strpos($real_file, $real_upload) !== 0) {
            return false;
        }

        if (file_exists($full_path)) {
            return unlink($full_path);
        }
        return false;
    }

    /**
     * Mapping MIME → ekstensi aman
     */
    private static function mimeToExt(string $mime): string {
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        return $map[$mime] ?? 'bin';
    }
}
?>
