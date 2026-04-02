<?php

declare(strict_types=1);

function upload_bumdes_logo(array $file, ?string $oldRelativePath = null): string
{
    return upload_profile_image($file, 'logo', $oldRelativePath, 2 * 1024 * 1024);
}

function upload_director_signature(array $file, ?string $oldRelativePath = null): string
{
    return upload_profile_image($file, 'signature', $oldRelativePath, 2 * 1024 * 1024);
}

function upload_treasurer_signature(array $file, ?string $oldRelativePath = null): string
{
    return upload_profile_image($file, 'treasurer_signature', $oldRelativePath, 2 * 1024 * 1024);
}

function upload_profile_image(array $file, string $kind, ?string $oldRelativePath = null, int $maxBytes = 2097152): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return (string) $oldRelativePath;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file ' . $kind . ' gagal. Silakan pilih file yang valid.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('Ukuran file ' . $kind . ' maksimal 2 MB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('File ' . $kind . ' tidak valid. Silakan unggah ulang.');
    }

    $meta = profile_upload_image_meta($tmpName, (string) ($file['name'] ?? ''));
    if ($meta === null) {
        throw new RuntimeException('Format file ' . $kind . ' hanya boleh JPG, PNG, atau WEBP.');
    }

    $mime = $meta['mime'];
    $extension = $meta['extension'];
    $isSignature = str_contains($kind, 'signature');
    $relativeDir = $isSignature ? 'uploads/signatures' : 'uploads/profiles';
    $uploadDir = ROOT_PATH . '/public/' . $relativeDir;
    ensure_public_upload_directory($uploadDir);

    $newName = $kind . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . ($isSignature ? $extension : 'jpg');
    $targetAbsolute = $uploadDir . '/' . $newName;

    if ($isSignature) {
        $saved = persist_uploaded_file($tmpName, $targetAbsolute);
    } else {
        $saved = save_uploaded_image_as_jpeg($tmpName, $mime, $targetAbsolute);
        if (!$saved) {
            $newName = $kind . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $targetAbsolute = $uploadDir . '/' . $newName;
            $saved = persist_uploaded_file($tmpName, $targetAbsolute);
        }
    }

    if (!$saved || !is_file($targetAbsolute)) {
        throw new RuntimeException('File ' . $kind . ' gagal disimpan ke server. Pastikan folder upload memiliki izin tulis. Folder: ' . $uploadDir);
    }

    delete_previous_uploaded_file($oldRelativePath, $relativeDir);
    return $relativeDir . '/' . basename($targetAbsolute);
}

function profile_upload_image_meta(string $tmpName, string $originalName = ''): ?array
{
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
    ];

    $mime = '';
    try {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);
    } catch (Throwable) {
        $mime = '';
    }

    if ($mime !== '' && isset($allowed[$mime])) {
        return ['mime' => $mime, 'extension' => $allowed[$mime]];
    }

    $imageInfo = @getimagesize($tmpName);
    $detectedMime = is_array($imageInfo) ? (string) ($imageInfo['mime'] ?? '') : '';
    if ($detectedMime !== '' && isset($allowed[$detectedMime])) {
        return ['mime' => $detectedMime, 'extension' => $allowed[$detectedMime]];
    }

    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (isset(['jpg' => true, 'jpeg' => true, 'png' => true, 'webp' => true][$extension]) && is_array($imageInfo)) {
        return [
            'mime' => $detectedMime !== '' ? $detectedMime : ($extension === 'png' ? 'image/png' : (in_array($extension, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/webp')),
            'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
        ];
    }

    return null;
}

function persist_uploaded_file(string $tmpName, string $targetAbsolute): bool
{
    $saved = move_uploaded_file($tmpName, $targetAbsolute);
    if (!$saved) {
        $saved = @copy($tmpName, $targetAbsolute);
        if ($saved) {
            @chmod($targetAbsolute, 0644);
        }
    }

    return $saved;
}

function ensure_public_upload_directory(string $uploadDir): void
{
    $publicDir = dirname(dirname($uploadDir));
    $uploadsDir = dirname($uploadDir);

    foreach ([$publicDir, $uploadsDir, $uploadDir] as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat: ' . $dir);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
            clearstatcache(true, $dir);
        }
    }

    $htaccessPath = $uploadDir . '/.htaccess';
    if (!is_file($htaccessPath)) {
        @file_put_contents($htaccessPath, "Options -Indexes
<FilesMatch \"\.(php|phtml|php5|phar)$\">
Deny from all
</FilesMatch>
");
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException('Folder upload tidak memiliki izin tulis. Ubah permission folder menjadi 775/777 atau sesuaikan owner web server pada: ' . $uploadDir);
    }
}

function save_uploaded_image_as_jpeg(string $tmpName, string $mime, string $targetAbsolute): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $image = null;
    try {
        if ($mime === 'image/jpeg') {
            $image = @imagecreatefromjpeg($tmpName);
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($tmpName);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($tmpName);
        }

        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        $saved = imagejpeg($canvas, $targetAbsolute, 88);
        imagedestroy($canvas);
        imagedestroy($image);
        return $saved;
    } catch (Throwable) {
        if (is_resource($image) || $image instanceof GdImage) {
            imagedestroy($image);
        }
        return false;
    }
}

function delete_previous_uploaded_file(?string $oldRelativePath, string $allowedDir): void
{
    $oldRelativePath = trim((string) $oldRelativePath);
    if ($oldRelativePath === '') {
        return;
    }

    $oldAbsolute = ROOT_PATH . '/public/' . ltrim($oldRelativePath, '/');
    $allowedAbsoluteDir = realpath(ROOT_PATH . '/public/' . trim($allowedDir, '/')) ?: ROOT_PATH . '/public/' . trim($allowedDir, '/');
    $oldReal = realpath($oldAbsolute);
    if ($oldReal && str_starts_with($oldReal, $allowedAbsoluteDir) && is_file($oldReal)) {
        @unlink($oldReal);
    }
}
