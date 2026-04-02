<?php

declare(strict_types=1);

function import_validate_upload(array $file, int $maxBytes = 2097152): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [false, 'Silakan pilih file Excel (.xlsx) terlebih dahulu.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'File gagal diunggah. Silakan coba lagi dengan file yang valid.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return [false, 'Ukuran file maksimal 2 MB agar aman untuk shared hosting.'];
    }

    $name = strtolower((string) ($file['name'] ?? ''));
    if (!str_ends_with($name, '.xlsx')) {
        return [false, 'Format file harus .xlsx.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [false, 'File upload tidak valid. Silakan unggah ulang file Anda.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $allowedMimes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
    ];
    if (!in_array((string) $mime, $allowedMimes, true)) {
        return [false, 'Tipe file tidak dikenali sebagai Excel .xlsx.'];
    }

    return [true, 'File valid.'];
}

function import_store_temp_file(array $file, string $prefix): string
{
    $dir = ROOT_PATH . '/storage/imports';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder import tidak dapat dibuat di server. Pastikan folder storage/imports dapat ditulis.');
    }

    $path = $dir . '/' . $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.xlsx';
    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('File Excel gagal dipindahkan ke folder import server.');
    }

    return $path;
}

function import_cleanup_temp_file(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

function import_strip_invisible(string $value): string
{
    $value = str_replace(["\xC2\xA0", 'Â '], ' ', $value);
    return preg_replace('/[\x{FEFF}\x{200B}-\x{200D}\x{2060}]/u', '', $value) ?? $value;
}

function import_normalize_headers(array $row): array
{
    return array_map(static function ($value): string {
        $normalized = trim(import_strip_invisible((string) $value));
        return strtolower($normalized);
    }, $row);
}

function import_bool_flag(string $value): ?int
{
    $normalized = strtolower(trim($value));
    if (in_array($normalized, ['1', 'ya', 'yes', 'true', 'aktif', 'header'], true)) {
        return 1;
    }
    if (in_array($normalized, ['0', 'tidak', 'no', 'false', 'nonaktif', 'detail'], true)) {
        return 0;
    }
    return null;
}

function import_decimal(mixed $value): ?string
{
    if (is_int($value) || is_float($value)) {
        return number_format((float) $value, 2, '.', '');
    }

    $value = trim(import_strip_invisible((string) $value));
    if ($value === '') {
        return '0.00';
    }

    $value = str_replace(['Rp', 'rp', 'IDR', 'idr'], '', $value);
    $value = preg_replace('/\s+/', '', $value) ?? '';
    $value = preg_replace('/[^0-9,.-]/', '', $value) ?? '';
    if ($value === '' || $value === '-' || $value === ',' || $value === '.') {
        return null;
    }

    $negative = false;
    if (str_starts_with($value, '-')) {
        $negative = true;
        $value = substr($value, 1);
    }

    $lastDot = strrpos($value, '.');
    $lastComma = strrpos($value, ',');

    if ($lastDot !== false && $lastComma !== false) {
        if ($lastDot > $lastComma) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
    } elseif ($lastComma !== false) {
        if (preg_match('/,\d{1,2}$/', $value) === 1) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($lastDot !== false && preg_match('/\.\d{3}(?:\.\d{3})+$/', $value) === 1) {
        $value = str_replace('.', '', $value);
    }

    if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
        return null;
    }

    $decimal = number_format((float) $value, 2, '.', '');
    return $negative ? '-' . $decimal : $decimal;
}

function import_normalize_date_value(mixed $value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (is_int($value) || is_float($value)) {
        return import_excel_serial_to_date((float) $value);
    }

    $value = trim(import_strip_invisible((string) $value));
    if ($value === '') {
        return '';
    }

    if (is_numeric($value) && preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1) {
        $serialDate = import_excel_serial_to_date((float) $value);
        if ($serialDate !== '') {
            return $serialDate;
        }
    }

    foreach (['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'm/d/Y'] as $format) {
        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value);
        if ($parsed instanceof DateTimeImmutable && $parsed->format($format) === $value) {
            return $parsed->format('Y-m-d');
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return '';
}

function import_excel_serial_to_date(float $serial): string
{
    if ($serial <= 0) {
        return '';
    }

    $wholeDays = (int) floor($serial);
    if ($wholeDays < 1 || $wholeDays > 60000) {
        return '';
    }

    $base = new DateTimeImmutable('1899-12-30');
    return $base->modify('+' . $wholeDays . ' days')->format('Y-m-d');
}
