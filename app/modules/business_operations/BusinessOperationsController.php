<?php

declare(strict_types=1);

final class BusinessOperationsController extends Controller
{
    private const PAGES = [
        'employees' => [
            'title' => 'Manajemen Karyawan',
            'eyebrow' => 'Kelola Usaha',
            'description' => 'Pantau pengurus, karyawan, tugas, dan status kerja per unit usaha.',
            'icon' => 'bi-people',
            'active' => 'Karyawan aktif',
            'next_action' => 'Tambah karyawan',
            'items' => [
                'Data pegawai dan pengurus per unit usaha',
                'Jabatan, nomor kontak, dan status aktif',
                'Catatan tugas operasional yang berhubungan dengan unit',
            ],
        ],
        'business' => [
            'title' => 'Manajemen Bisnis',
            'eyebrow' => 'Kelola Usaha',
            'description' => 'Ringkas aktivitas bisnis, layanan, target, dan catatan operasional setiap unit.',
            'icon' => 'bi-building',
            'active' => 'Unit aktif',
            'next_action' => 'Tambah aktivitas',
            'items' => [
                'Profil layanan dan kegiatan utama tiap unit',
                'Target operasional dan catatan perkembangan',
                'Ringkasan kondisi unit untuk pimpinan',
            ],
        ],
        'budgets' => [
            'title' => 'Anggaran',
            'eyebrow' => 'Kelola Usaha',
            'description' => 'Siapkan pagu, alokasi, dan rencana belanja untuk unit usaha.',
            'icon' => 'bi-wallet2',
            'active' => 'Pagu aktif',
            'next_action' => 'Tambah anggaran',
            'items' => [
                'Anggaran pendapatan dan belanja per unit',
                'Kategori alokasi biaya operasional',
                'Dasar pembanding realisasi dari jurnal',
            ],
        ],
        'budget_plans' => [
            'title' => 'Rencana Anggaran',
            'eyebrow' => 'Kelola Usaha',
            'description' => 'Susun RAB kegiatan atau kebutuhan pembelian sebelum direalisasikan.',
            'icon' => 'bi-clipboard2-check',
            'active' => 'RAB berjalan',
            'next_action' => 'Tambah RAB',
            'items' => [
                'Rincian kebutuhan barang/jasa per kegiatan',
                'Estimasi harga, qty, dan total rencana',
                'Status rencana sebelum masuk jurnal realisasi',
            ],
        ],
        'budget_reports' => [
            'title' => 'Laporan Rencana Anggaran',
            'eyebrow' => 'Kelola Usaha',
            'description' => 'Bandingkan rencana anggaran dengan realisasi jurnal agar selisih mudah dibaca.',
            'icon' => 'bi-bar-chart-line',
            'active' => 'Laporan siap',
            'next_action' => 'Lihat laporan',
            'items' => [
                'Rencana vs realisasi per unit usaha',
                'Sisa anggaran dan selisih belanja',
                'Output laporan yang bisa disiapkan untuk LPJ',
            ],
        ],
    ];

    public function employees(): void
    {
        $this->renderPage('employees');
    }

    public function business(): void
    {
        $this->renderPage('business');
    }

    public function budgets(): void
    {
        $this->renderPage('budgets');
    }

    public function budgetPlans(): void
    {
        $this->renderPage('budget_plans');
    }

    public function budgetReports(): void
    {
        $this->renderPage('budget_reports');
    }

    private function renderPage(string $key): void
    {
        $page = self::PAGES[$key] ?? self::PAGES['business'];
        $units = business_unit_options(true);

        $this->view('business_operations/views/index', [
            'title' => $page['title'],
            'page' => $page,
            'units' => $units,
            'activeUnitLabel' => current_business_unit_label(),
        ]);
    }
}
