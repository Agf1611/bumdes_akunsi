<?php declare(strict_types=1);
$user = Auth::user();
$profile = app_profile();
$logoPath = (string) ($profile['logo_path'] ?? '');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$active = static function (array $needles) use ($currentPath): string {
    foreach ($needles as $needle) {
        if ($needle === '/' && ($currentPath === '/' || str_contains($currentPath, '/dashboard'))) {
            return ' is-active';
        }
        if ($needle !== '/' && str_contains($currentPath, $needle)) {
            return ' is-active';
        }
    }
    return '';
};

$icon = static function (string $name): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="8" rx="2"></rect><rect x="14" y="3" width="7" height="5" rx="2"></rect><rect x="14" y="12" width="7" height="9" rx="2"></rect><rect x="3" y="15" width="7" height="6" rx="2"></rect></svg>',
        'units' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l7-4 7 4v14"></path><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M9 14h.01"></path><path d="M15 14h.01"></path></svg>',
        'coa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6h11"></path><path d="M9 12h11"></path><path d="M9 18h11"></path><path d="M4 6h.01"></path><path d="M4 12h.01"></path><path d="M4 18h.01"></path></svg>',
        'assets' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.3 7l8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
        'periods' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        'journals' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
        'ledger' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path><path d="M8 5v14"></path></svg>',
        'trial' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M4 12h10"></path><path d="M4 17h16"></path><path d="M17 10l3 2-3 2"></path></svg>',
        'profit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 7-8"></path><path d="M14 7h6v6"></path></svg>',
        'balance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"></path><path d="M5 7h5"></path><path d="M14 7h5"></path><path d="M3 7a2 2 0 0 0 4 0"></path><path d="M17 7a2 2 0 0 0 4 0"></path><path d="M5 7l-2 7"></path><path d="M19 7l2 7"></path><path d="M1 14h6"></path><path d="M17 14h6"></path></svg>',
        'cash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M7 6v12"></path><path d="M17 6v12"></path></svg>',
        'equity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"></path><path d="M7 8l5-5 5 5"></path><path d="M17 16l-5 5-5-5"></path></svg>',
        'notes' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path><path d="M8 9h3"></path></svg>',
        'lpj' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6"></path><path d="M8 12h8"></path><path d="M8 16h8"></path><path d="M8 8h3"></path></svg>',
        'import' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="M7 10l5 5 5-5"></path><path d="M5 21h14"></path></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'audit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>',
        'backup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'update' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v10"></path><path d="M8 9l4 4 4-4"></path><path d="M5 21h14"></path><path d="M5 16a7 7 0 0 0 14 0"></path></svg>',
    ];

    return $icons[$name] ?? $icons['dashboard'];
};
?>
<aside class="sidebar app-sidebar" id="appSidebar" aria-label="Sidebar navigasi">
    <div class="app-sidebar__inner">
        <div class="app-sidebar__brand">
            <div class="brand-mark">
                <?php if ($logoPath !== ''): ?>
                    <img src="<?= e(upload_url($logoPath)) ?>" alt="Logo BUMDes" class="brand-mark__image">
                <?php else: ?>
                    <span class="brand-mark__fallback">B</span>
                <?php endif; ?>
            </div>
            <div class="brand-copy min-w-0">
                <div class="brand-copy__label">Sistem Akuntansi BUMDes</div>
                <div class="brand-copy__title"><?= e($profile['bumdes_name'] ?: 'BUMDes') ?></div>
                <div class="brand-copy__meta"><?= e(current_accounting_period_label()) ?></div>
            </div>
            <button type="button" class="app-sidebar__close d-lg-none" id="sidebarClose" aria-label="Tutup menu">
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <nav class="app-nav" aria-label="Menu utama">
            <div class="app-nav__group">
                <div class="app-nav__caption">Ringkasan</div>
                <a class="app-nav__link<?= $active(['/dashboard', '/']) ?>" href="<?= e(base_url('/dashboard')) ?>">
                    <span class="app-nav__icon"><?= $icon('dashboard') ?></span>
                    <span class="app-nav__text">
                        <span class="app-nav__title">Dashboard</span>
                        <span class="app-nav__note">Ikhtisar eksekutif dan tren</span>
                    </span>
                </a>
                <a class="app-nav__link<?= $active(['/dashboard/pimpinan']) ?>" href="<?= e(base_url('/dashboard/pimpinan')) ?>">
                    <span class="app-nav__icon"><?= $icon('dashboard') ?></span>
                    <span class="app-nav__text">
                        <span class="app-nav__title">Dashboard Pimpinan</span>
                        <span class="app-nav__note">Fokus keputusan, risiko, dan closing</span>
                    </span>
                </a>
            </div>

            <?php if (Auth::hasRole(['admin', 'bendahara'])): ?>
                <div class="app-nav__group">
                    <div class="app-nav__caption">Data Master</div>

                    <?php if (Auth::hasRole('admin')): ?>
                        <a class="app-nav__link<?= $active(['/business-units']) ?>" href="<?= e(base_url('/business-units')) ?>">
                            <span class="app-nav__icon"><?= $icon('units') ?></span>
                            <span class="app-nav__text">
                                <span class="app-nav__title">Unit Usaha</span>
                                <span class="app-nav__note">Cabang dan layanan usaha</span>
                            </span>
                        </a>
                    <?php endif; ?>

                    <a class="app-nav__link<?= $active(['/coa']) ?>" href="<?= e(base_url('/coa')) ?>">
                        <span class="app-nav__icon"><?= $icon('coa') ?></span>
                        <span class="app-nav__text">
                            <span class="app-nav__title">Chart of Accounts</span>
                            <span class="app-nav__note">Struktur akun dan kategori</span>
                        </span>
                    </a>

                    <a class="app-nav__link<?= $active(['/assets']) ?>" href="<?= e(base_url('/assets')) ?>">
                        <span class="app-nav__icon"><?= $icon('assets') ?></span>
                        <span class="app-nav__text">
                            <span class="app-nav__title">Aset</span>
                            <span class="app-nav__note">Master, penyusutan, dan laporan aset</span>
                        </span>
                    </a>

                    <a class="app-nav__link<?= $active(['/periods']) ?>" href="<?= e(base_url('/periods')) ?>">
                        <span class="app-nav__icon"><?= $icon('periods') ?></span>
                        <span class="app-nav__text">
                            <span class="app-nav__title">Periode Akuntansi</span>
                            <span class="app-nav__note">Buka tutup dan periode aktif</span>
                        </span>
                    </a>


                    <?php if (class_exists('ReferenceMasterController')): ?>
                        <a class="app-nav__link<?= $active(['/reference-masters']) ?>" href="<?= e(base_url('/reference-masters')) ?>">
                            <span class="app-nav__icon"><?= $icon('coa') ?></span>
                            <span class="app-nav__text">
                                <span class="app-nav__title">Referensi Jurnal</span>
                                <span class="app-nav__note">Mitra, persediaan, dan komponen arus kas</span>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="app-nav__group">
                    <div class="app-nav__caption">Transaksi</div>
                    <a class="app-nav__link<?= $active(['/journals']) ?>" href="<?= e(base_url('/journals')) ?>">
                        <span class="app-nav__icon"><?= $icon('journals') ?></span>
                        <span class="app-nav__text">
                            <span class="app-nav__title">Jurnal Umum</span>
                            <span class="app-nav__note">Input double-entry dan cetak bukti</span>
                        </span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="app-nav__group">
                <div class="app-nav__caption">Laporan</div>
                <?php if (class_exists('ReceivableLedgerController') || class_exists('PayableLedgerController')): ?>
                    <div class="app-nav__subcaption">Buku Pembantu</div>
                    <?php if (class_exists('ReceivableLedgerController')): ?>
                        <a class="app-nav__link<?= $active(['/receivable-ledgers']) ?>" href="<?= e(base_url('/receivable-ledgers')) ?>">
                            <span class="app-nav__icon"><?= $icon('ledger') ?></span>
                            <span class="app-nav__text"><span class="app-nav__title">BP Piutang</span><span class="app-nav__note">Mutasi dan saldo per mitra</span></span>
                        </a>
                    <?php endif; ?>
                    <?php if (class_exists('PayableLedgerController')): ?>
                        <a class="app-nav__link<?= $active(['/payable-ledgers']) ?>" href="<?= e(base_url('/payable-ledgers')) ?>">
                            <span class="app-nav__icon"><?= $icon('ledger') ?></span>
                            <span class="app-nav__text"><span class="app-nav__title">BP Utang</span><span class="app-nav__note">Mutasi dan saldo per kreditur</span></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="app-nav__link<?= $active(['/ledger']) ?>" href="<?= e(base_url('/ledger')) ?>">
                    <span class="app-nav__icon"><?= $icon('ledger') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Buku Besar</span><span class="app-nav__note">Mutasi akun dan saldo berjalan</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/trial-balance']) ?>" href="<?= e(base_url('/trial-balance')) ?>">
                    <span class="app-nav__icon"><?= $icon('trial') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Neraca Saldo</span><span class="app-nav__note">Ringkasan debit dan kredit</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/profit-loss']) ?>" href="<?= e(base_url('/profit-loss')) ?>">
                    <span class="app-nav__icon"><?= $icon('profit') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Laba Rugi</span><span class="app-nav__note">Kinerja pendapatan dan beban</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/balance-sheet']) ?>" href="<?= e(base_url('/balance-sheet')) ?>">
                    <span class="app-nav__icon"><?= $icon('balance') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Neraca</span><span class="app-nav__note">Posisi aset, liabilitas, ekuitas</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/cash-flow']) ?>" href="<?= e(base_url('/cash-flow')) ?>">
                    <span class="app-nav__icon"><?= $icon('cash') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Arus Kas</span><span class="app-nav__note">Kas operasional dan non-operasional</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/equity-changes']) ?>" href="<?= e(base_url('/equity-changes')) ?>">
                    <span class="app-nav__icon"><?= $icon('equity') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Perubahan Ekuitas</span><span class="app-nav__note">Mutasi modal dan saldo akhir</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/financial-notes']) ?>" href="<?= e(base_url('/financial-notes')) ?>">
                    <span class="app-nav__icon"><?= $icon('notes') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">CaLK</span><span class="app-nav__note">Catatan atas laporan keuangan</span></span>
                </a>
                <a class="app-nav__link<?= $active(['/lpj']) ?>" href="<?= e(base_url('/lpj')) ?>">
                    <span class="app-nav__icon"><?= $icon('lpj') ?></span>
                    <span class="app-nav__text"><span class="app-nav__title">Paket LPJ</span><span class="app-nav__note">Bundel formal laporan pertanggungjawaban</span></span>
                </a>
            </div>

            <?php if (Auth::hasRole(['admin', 'bendahara'])): ?>
                <div class="app-nav__group">
                    <div class="app-nav__caption">Utilitas</div>
                    <a class="app-nav__link<?= $active(['/bank-reconciliations']) ?>" href="<?= e(base_url('/bank-reconciliations')) ?>">
                        <span class="app-nav__icon"><?= $icon('cash') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Rekonsiliasi Bank</span><span class="app-nav__note">Mutasi bank vs jurnal</span></span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (Auth::hasRole('admin')): ?>
                <div class="app-nav__group">
                    <div class="app-nav__caption">Pengaturan</div>
                    <a class="app-nav__link<?= $active(['/user-accounts']) ?>" href="<?= e(base_url('/user-accounts')) ?>">
                        <span class="app-nav__icon"><?= $icon('users') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Akun Pengguna</span><span class="app-nav__note">Role dan akses aplikasi</span></span>
                    </a>
                    <a class="app-nav__link<?= $active(['/audit-logs']) ?>" href="<?= e(base_url('/audit-logs')) ?>">
                        <span class="app-nav__icon"><?= $icon('audit') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Audit Trail</span><span class="app-nav__note">Riwayat aktivitas penting</span></span>
                    </a>
                    <a class="app-nav__link<?= $active(['/backups']) ?>" href="<?= e(base_url('/backups')) ?>">
                        <span class="app-nav__icon"><?= $icon('backup') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Backup Database</span><span class="app-nav__note">Cadangan data sebelum update</span></span>
                    </a>
                    <a class="app-nav__link<?= $active(['/updates']) ?>" href="<?= e(base_url('/updates')) ?>">
                        <span class="app-nav__icon"><?= $icon('update') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Update Aplikasi</span><span class="app-nav__note">Tarik patch file dari GitHub</span></span>
                    </a>
                    <a class="app-nav__link<?= $active(['/settings/profile']) ?>" href="<?= e(base_url('/settings/profile')) ?>">
                        <span class="app-nav__icon"><?= $icon('settings') ?></span>
                        <span class="app-nav__text"><span class="app-nav__title">Profil BUMDes</span><span class="app-nav__note">Identitas aplikasi dan tanda tangan</span></span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="app-sidebar__footer">
            <div class="sidebar-user-card">
                <div class="sidebar-user-card__eyebrow">Masuk sebagai</div>
                <div class="sidebar-user-card__name"><?= e($user['full_name'] ?? '-') ?></div>
                <div class="sidebar-user-card__meta"><?= e($user['role_name'] ?? '-') ?> &middot; <?= e($user['username'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</aside>
