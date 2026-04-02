<?php
/** @var array<int,array<string,mixed>> $yearCards */
$selectedYear = (int) ($selectedYear ?? current_working_year());
?>
<div class="content-wrapper"><section class="content py-4"><div class="container-fluid"><div class="row justify-content-center"><div class="col-xl-8 col-lg-9">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="mb-4">
                <div class="text-uppercase text-muted small fw-bold mb-1">Periode Kerja</div>
                <h2 class="h3 mb-2">Pilih Tahun Akuntansi</h2>
                <p class="text-muted mb-0">Pilih tahun kerja sebelum masuk ke dashboard. Semua jurnal, laporan, dan ringkasan akan membaca tahun akuntansi yang Anda pilih di sesi ini.</p>
            </div>

            <form method="post" action="<?= e(base_url('/periods/switch-working')) ?>">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div class="row g-3 mb-4">
                    <?php foreach ($yearCards as $card): ?>
                        <?php $year = (int) ($card['year'] ?? 0); $defaultPeriod = $card['default_period'] ?? null; ?>
                        <div class="col-md-6 col-xl-4">
                            <label class="card h-100 border-2 rounded-4 p-3 period-year-card" style="cursor:pointer;">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="working_year" value="<?= e((string) $year) ?>" <?= $selectedYear === $year ? 'checked' : '' ?>>
                                    </div>
                                    <?php if ($selectedYear === $year): ?><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Aktif</span><?php endif; ?>
                                </div>
                                <div class="fw-bold fs-5 mb-2"><?= e((string) $year) ?></div>
                                <?php if (is_array($defaultPeriod)): ?>
                                    <div class="small text-muted mb-1">Periode default: <?= e((string) ($defaultPeriod['period_name'] ?? $defaultPeriod['period_code'] ?? '-')) ?></div>
                                <?php else: ?>
                                    <div class="small text-danger mb-1">Belum ada periode untuk tahun ini</div>
                                <?php endif; ?>
                                <div class="small text-muted">Jumlah periode: <?= e((string) ($card['period_count'] ?? 0)) ?></div>
                                <div class="small text-muted">Periode buka: <?= e((string) ($card['open_count'] ?? 0)) ?></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-info rounded-4 small">
                    Jika Anda memilih tahun 2025, maka dashboard, jurnal, dan laporan akan membaca data tahun 2025 saja. Data tahun lain seperti 2026 tidak akan ikut tampil pada sesi ini.
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="<?= e(base_url('/dashboard')) ?>" class="btn btn-outline-secondary rounded-pill px-4">Lewati dan ke Dashboard</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Masuk ke Dashboard</button>
                </div>
            </form>
        </div>
    </div>
</div></div></div></section></div>
