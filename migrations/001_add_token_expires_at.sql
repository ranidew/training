-- Migration: Tambah kolom token_expires_at ke tabel users
-- SCP-APM-005: Token reset password harus memiliki waktu kadaluarsa

ALTER TABLE `users`
    ADD COLUMN `token_expires_at` DATETIME DEFAULT NULL
        AFTER `verification_token`;
