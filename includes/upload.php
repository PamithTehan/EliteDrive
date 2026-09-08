<?php
/**
 * File: upload.php
 * Purpose: Secure file upload handler
 */
// includes/upload.php

function handleSecureUpload(array $file, string $subfolder): string {
    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxBytes = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File too large.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Unsupported file type.');
    }

    $ext = $mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg');
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    $storageRoot = __DIR__ . '/../public/assets/uploads/' . $subfolder;
    if (!is_dir($storageRoot)) {
        mkdir($storageRoot, 0750, true);
    }

    $destination = $storageRoot . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save file.');
    }

    return $subfolder . '/' . $filename; // store this relative path in DB
}
