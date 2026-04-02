<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
require __DIR__ . '/app/bootstrap.php';

ob_start();
render_view('periods/views/checklist', [
    'title' => 'Checklist Tutup Buku',
    'checklist' => [
        'period' => [
            'id' => 1,
            'period_code' => '2026-03',
            'period_name' => 'Maret 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => 'OPEN',
            'is_active' => 1,
        ],
        'summary' => [
            'journal_count' => 12,
            'latest_backup' => [
                'exists' => true,
                'name' => 'backup-demo.sql',
                'modified_label' => '15/03/2026 09:00',
                'size_bytes' => 2048,
            ],
        ],
        'checks' => [
            ['label' => 'Semua jurnal sudah seimbang', 'status' => 'pass', 'message' => 'Tidak ditemukan jurnal tidak seimbang.'],
            ['label' => 'Rekonsiliasi bank', 'status' => 'warning', 'message' => 'Masih ada 1 sesi yang perlu dicek.'],
        ],
        'is_ready_to_close' => true,
        'critical_failures' => 0,
        'warnings' => 1,
    ],
], 'main');
$html = ob_get_clean();
if (strpos($html, 'Checklist Tutup Buku') === false || strpos($html, 'Tutup Periode Ini') === false) {
    fwrite(STDERR, "checklist render failed\n");
    exit(1);
}

echo "SMOKE_OK\n";
