<?php

declare(strict_types=1);

final class JournalAttachmentService
{
    public const MAX_FILE_SIZE = 5242880;

    public static function directory(): string
    {
        return ROOT_PATH . '/storage/journal_attachments';
    }

    public static function ensureDirectory(): void
    {
        $dir = self::directory();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder storage/journal_attachments tidak dapat dibuat.');
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Folder storage/journal_attachments tidak writable. Ubah permission folder menjadi 775 atau sesuaikan owner web server.');
        }
    }

    public static function safeStoredName(string $storedName): ?string
    {
        $storedName = basename(trim($storedName));
        if ($storedName === '') {
            return null;
        }

        if (!preg_match('/\A[a-zA-Z0-9._-]+\z/', $storedName)) {
            return null;
        }

        return $storedName;
    }

    public static function filePath(string $storedName): ?string
    {
        $safe = self::safeStoredName($storedName);
        if ($safe === null) {
            return null;
        }

        $path = self::directory() . '/' . $safe;
        return is_file($path) ? $path : null;
    }

    public static function deleteStoredFile(?string $storedName): void
    {
        $path = $storedName !== null ? self::filePath($storedName) : null;
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public static function normalizeOriginalName(string $name): string
    {
        $name = basename(trim($name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $name) ?: 'lampiran';
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            $name = 'lampiran';
        }
        if (strlen($name) > 200) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $base = substr($base, 0, 180);
            $name = $extension !== '' ? ($base . '.' . $extension) : $base;
        }

        return $name;
    }

    public static function downloadFileName(array $attachment): string
    {
        $original = self::normalizeOriginalName((string) ($attachment['original_name'] ?? 'lampiran'));
        $ext = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        $expectedExt = strtolower((string) ($attachment['file_ext'] ?? ''));
        if ($expectedExt !== '' && $ext !== $expectedExt) {
            $base = pathinfo($original, PATHINFO_FILENAME);
            $original = $base . '.' . $expectedExt;
        }

        return $original;
    }

    public static function contentType(array $attachment): string
    {
        $mime = trim((string) ($attachment['mime_type'] ?? ''));
        if ($mime !== '') {
            return $mime;
        }

        return match (strtolower((string) ($attachment['file_ext'] ?? ''))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    public function storeUploadedFile(array $file): array
    {
        self::ensureDirectory();

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Silakan pilih file lampiran terlebih dahulu.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file lampiran gagal. Silakan pilih file yang valid.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Ukuran file lampiran maksimal 5 MB.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('File lampiran tidak valid. Silakan unggah ulang file Anda.');
        }

        $originalName = self::normalizeOriginalName((string) ($file['name'] ?? 'lampiran'));
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Format lampiran hanya boleh PDF, JPG, PNG, atau WEBP.');
        }

        $resolvedExtension = $allowed[$mime];
        if ($extension !== '' && in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            $resolvedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        }

        $storedName = 'journal-attachment-' . date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.' . $resolvedExtension;
        $target = self::directory() . '/' . $storedName;
        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('Lampiran gagal disimpan ke server. Pastikan folder storage/journal_attachments writable.');
        }

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'stored_file_path' => $storedName,
            'mime_type' => $mime,
            'file_ext' => $resolvedExtension,
            'file_size' => (int) (@filesize($target) ?: $size),
        ];
    }
}
