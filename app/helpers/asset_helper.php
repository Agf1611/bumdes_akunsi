<?php

declare(strict_types=1);

function asset_groups(): array
{
    return [
        'FIXED' => 'Aset Tetap',
        'BIOLOGICAL' => 'Aset Biologis',
        'OTHER' => 'Lainnya',
    ];
}

function asset_depreciation_methods(): array
{
    return [
        'STRAIGHT_LINE' => 'Garis Lurus',
    ];
}

function asset_entry_modes(): array
{
    return [
        'OPENING' => 'Saldo Awal / Aset Lama',
        'ACQUISITION' => 'Perolehan Baru',
    ];
}

function asset_accounting_event_types(): array
{
    return [
        'OPENING' => 'Saldo Awal',
        'ACQUISITION' => 'Perolehan',
        'DEPRECIATION' => 'Penyusutan',
        'DISPOSAL' => 'Pelepasan',
        'SALE' => 'Penjualan',
        'ADJUSTMENT' => 'Penyesuaian',
    ];
}

function asset_event_statuses(): array
{
    return [
        'DRAFT' => 'Draft',
        'POSTED' => 'Posted',
        'CANCELED' => 'Dibatalkan',
    ];
}

function asset_conditions(): array
{
    return [
        'EXCELLENT' => 'Sangat Baik',
        'GOOD' => 'Baik',
        'FAIR' => 'Cukup',
        'POOR' => 'Kurang',
        'DAMAGED' => 'Rusak',
    ];
}

function asset_statuses(): array
{
    return [
        'ACTIVE' => 'Aktif',
        'IDLE' => 'Tidak Digunakan',
        'MAINTENANCE' => 'Perawatan',
        'NONACTIVE' => 'Nonaktif',
        'SOLD' => 'Dijual',
        'DAMAGED' => 'Rusak Berat',
        'DISPOSED' => 'Dilepas',
    ];
}

function asset_mutation_types(): array
{
    return [
        'ACQUISITION' => 'Perolehan',
        'UPDATE' => 'Perubahan Data',
        'STATUS_CHANGE' => 'Perubahan Status',
        'TRANSFER_UNIT' => 'Pindah Unit Usaha',
        'TRANSFER_LOCATION' => 'Pindah Lokasi',
        'MAINTENANCE' => 'Perawatan',
        'SELL' => 'Penjualan',
        'DAMAGE' => 'Rusak Berat',
        'DISPOSE' => 'Pelepasan Aset',
    ];
}

function asset_funding_sources(): array
{
    return [
        'DANA_DESA' => 'Dana Desa',
        'HASIL_USAHA' => 'Hasil Usaha',
        'HIBAH_BANTUAN' => 'Hibah / Bantuan',
        'PENYERTAAN_MODAL' => 'Penyertaan Modal',
        'PINJAMAN' => 'Pinjaman',
        'SWADAYA' => 'Swadaya',
        'LAINNYA' => 'Lainnya',
    ];
}

function asset_group_label(string $code): string
{
    $map = asset_groups();
    return $map[$code] ?? $code;
}

function asset_entry_mode_label(string $code): string
{
    $map = asset_entry_modes();
    return $map[$code] ?? $code;
}

function asset_event_type_label(string $code): string
{
    $map = asset_accounting_event_types();
    return $map[$code] ?? $code;
}

function asset_event_status_label(string $code): string
{
    $map = asset_event_statuses();
    return $map[$code] ?? $code;
}

function asset_condition_label(string $code): string
{
    $map = asset_conditions();
    return $map[$code] ?? $code;
}

function asset_status_label(string $code): string
{
    $map = asset_statuses();
    return $map[$code] ?? $code;
}

function asset_mutation_label(string $code): string
{
    $map = asset_mutation_types();
    return $map[$code] ?? $code;
}

function asset_method_label(string $code): string
{
    $map = asset_depreciation_methods();
    return $map[$code] ?? $code;
}

function asset_funding_label(string $code): string
{
    $map = asset_funding_sources();
    return $map[$code] ?? $code;
}

function asset_badge_class(string $status): string
{
    return match ($status) {
        'ACTIVE' => 'text-bg-success',
        'IDLE' => 'text-bg-warning',
        'MAINTENANCE' => 'text-bg-info',
        'NONACTIVE' => 'text-bg-secondary',
        'SOLD', 'DAMAGED', 'DISPOSED' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}

function asset_condition_badge_class(string $condition): string
{
    return match ($condition) {
        'EXCELLENT', 'GOOD' => 'text-bg-success',
        'FAIR' => 'text-bg-warning',
        'POOR' => 'text-bg-secondary',
        'DAMAGED' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}

function asset_yes_no_label(bool $value): string
{
    return $value ? 'Ya' : 'Tidak';
}

function asset_currency(float|int|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function asset_quantity(float|int|string $quantity): string
{
    return number_format((float) $quantity, 0, ',', '.');
}

function asset_integer_input(float|int|string $value): string
{
    return number_format((float) $value, 0, '.', '');
}

function asset_months_label(?int $months): string
{
    $months = (int) $months;
    if ($months <= 0) {
        return '-';
    }

    $years = intdiv($months, 12);
    $remainingMonths = $months % 12;
    $parts = [];
    if ($years > 0) {
        $parts[] = $years . ' tahun';
    }
    if ($remainingMonths > 0) {
        $parts[] = $remainingMonths . ' bulan';
    }
    return implode(' ', $parts) ?: ($months . ' bulan');
}

function asset_safe_date(?string $date): string
{
    return format_id_date($date);
}

function asset_comparison_date(string $asOfDate): string
{
    try {
        return (new DateTimeImmutable($asOfDate))->modify('-1 year')->format('Y-m-d');
    } catch (Throwable) {
        return date('Y-m-d', strtotime('-1 year'));
    }
}

function asset_filter_query(array $filters, array $extra = []): string
{
    $base = [
        'search' => (string) ($filters['search'] ?? ''),
        'unit_id' => (string) ($filters['unit_id'] ?? ''),
        'category_id' => (string) ($filters['category_id'] ?? ''),
        'status' => (string) ($filters['status'] ?? ''),
        'condition' => (string) ($filters['condition'] ?? ''),
        'active' => (string) ($filters['active'] ?? ''),
        'date_from' => (string) ($filters['date_from'] ?? ''),
        'date_to' => (string) ($filters['date_to'] ?? ''),
        'as_of_date' => (string) ($filters['as_of_date'] ?? ''),
        'comparison_date' => (string) ($filters['comparison_date'] ?? ''),
        'group' => (string) ($filters['group'] ?? ''),
        'funding_source' => (string) ($filters['funding_source'] ?? ''),
    ];

    $query = array_merge($base, $extra);
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        if ($value === '0' && !in_array($key, ['active'], true)) {
            unset($query[$key]);
        }
    }
    return http_build_query($query);
}

function asset_example_notes(): array
{
    return [
        'WIFI' => [
            'Router utama untuk distribusi bandwidth ke pelanggan.',
            'Access point outdoor area dusun / RT.',
            'Kabel FO backbone dan perangkat ODP.',
            'UPS, mini server, laptop admin, alat instalasi.',
        ],
        'TERNAK' => [
            'Kandang ternak permanen / semi permanen.',
            'Mesin pencacah pakan dan timbangan ternak.',
            'Peralatan pakan, minum, kebersihan kandang.',
            'Indukan / ternak produktif dicatat sebagai aset biologis.',
        ],
    ];
}

function asset_sync_statuses(): array
{
    return [
        'NONE' => 'Belum ditautkan',
        'READY' => 'Siap sinkron',
        'POSTED' => 'Sudah sinkron',
    ];
}

function asset_sync_status_label(string $code): string
{
    $map = asset_sync_statuses();
    return $map[$code] ?? $code;
}

function asset_sync_badge_class(string $status): string
{
    return match ($status) {
        'POSTED' => 'text-bg-success',
        'READY' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

function asset_template_headers(): array
{
    return [
        'asset_code',
        'asset_name',
        'entry_mode',
        'category_code',
        'subcategory_name',
        'business_unit_code',
        'quantity',
        'unit_name',
        'acquisition_date',
        'acquisition_cost',
        'opening_as_of_date',
        'opening_accumulated_depreciation',
        'residual_value',
        'useful_life_months',
        'depreciation_method',
        'depreciation_start_date',
        'depreciation_allowed',
        'location',
        'supplier_name',
        'source_of_funds',
        'funding_source_detail',
        'reference_no',
        'offset_account_code',
        'condition_status',
        'asset_status',
        'description',
        'notes',
    ];
}

function asset_template_rows(): array
{
    return [
        [
            'WIFI-ROUTER-001',
            'Router Mikrotik Core',
            'ACQUISITION',
            'NETWORK',
            'Router Core',
            'WIFI',
            '1',
            'unit',
            date('Y-m-d'),
            '3500000',
            '',
            '0',
            '250000',
            '36',
            'STRAIGHT_LINE',
            date('Y-m-d'),
            '1',
            'Pos Jaringan Utama',
            'CV Jaringan Nusantara',
            'DANA_DESA',
            'Dana Desa Tahap 2 Tahun 2026',
            'INV-WIFI-001',
            '1.101',
            'GOOD',
            'ACTIVE',
            'Router utama distribusi bandwidth.',
            'Aset baru setelah sistem berjalan.',
        ],
        [
            'DOMBA-KANDANG-001',
            'Kandang Utama',
            'OPENING',
            'BUILDING',
            'Kandang Produksi',
            'TERNAK',
            '1',
            'unit',
            '2024-07-01',
            '25000000',
            date('Y-m-d'),
            '2083333.33',
            '1000000',
            '240',
            'STRAIGHT_LINE',
            '2024-07-01',
            '1',
            'Lahan Kandang',
            'Pembangunan Swakelola',
            'HASIL_USAHA',
            'Saldo awal aset sebelum aplikasi dipakai',
            'BA-KDG-001',
            '',
            'GOOD',
            'ACTIVE',
            'Kandang utama ternak domba.',
            'Aset historis / saldo awal.',
        ],
    ];
}
function asset_import_validate_upload(array $file, int $maxBytes = 3145728): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [false, 'Silakan pilih file template aset (.xlsx atau .csv) terlebih dahulu.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'File gagal diunggah. Silakan coba lagi dengan file yang valid.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return [false, 'Ukuran file maksimal 3 MB agar aman untuk shared hosting.'];
    }

    $name = strtolower(trim((string) ($file['name'] ?? '')));
    if ($name === '' || (!str_ends_with($name, '.xlsx') && !str_ends_with($name, '.csv'))) {
        return [false, 'Format file harus .xlsx atau .csv.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [false, 'File upload tidak valid. Silakan unggah ulang file Anda.'];
    }

    $mime = (string) ((new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '');
    $allowedMimes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
        'text/plain',
        'text/csv',
        'application/csv',
        'application/vnd.ms-excel',
    ];

    if (!in_array($mime, $allowedMimes, true)) {
        return [false, 'Tipe file tidak dikenali sebagai template aset yang valid.'];
    }

    return [true, 'File valid.'];
}

function asset_import_store_temp_file(array $file, string $prefix): string
{
    $dir = ROOT_PATH . '/storage/imports';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder import tidak dapat dibuat di server. Pastikan folder storage/imports dapat ditulis.');
    }

    $originalName = strtolower((string) ($file['name'] ?? ''));
    $extension = str_ends_with($originalName, '.csv') ? 'csv' : 'xlsx';
    $path = $dir . '/' . $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('File import aset gagal dipindahkan ke folder import server.');
    }

    return $path;
}

function asset_import_cleanup_temp_file(?string $path): void
{
    if ($path && is_file($path)) {
        @unlink($path);
    }
}
