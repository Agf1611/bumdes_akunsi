<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($periods ?? []); $periods = $listing['items']; $listingPath = '/periods'; ?>
<?php require APP_PATH . '/views/partials/table_action_menu.php'; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Periode Akuntansi</h1>
        <p class="text-secondary mb-0">Kelola periode yang boleh dipakai transaksi. Hanya satu periode aktif yang diizinkan pada satu waktu.</p>
    </div>
    <?php if (Auth::hasRole('admin')): ?>
        <div>
            <a href="<?= e(base_url('/periods/create')) ?>" class="btn btn-primary">Tambah Periode</a>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive coa-table-wrapper">
            <table class="table table-dark table-hover align-middle mb-0 coa-table">
                <thead>
                <tr>
                    <th style="min-width: 130px;">Kode</th>
                    <th style="min-width: 200px;">Nama Periode</th>
                    <th style="min-width: 220px;">Rentang Tanggal</th>
                    <th style="min-width: 120px;">Status</th>
                    <th style="min-width: 120px;">Aktif</th>
                    <th style="min-width: 160px;">Diperbarui</th>
                    <th class="text-end table-action-col" style="min-width: 92px;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($periods === []): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">Belum ada periode akuntansi. Tambahkan periode pertama untuk mulai mengatur transaksi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periods as $period): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($period['period_code']) ?></td>
                            <td><?= e($period['period_name']) ?></td>
                            <td class="text-secondary"><?= e(active_period_label((string) $period['start_date'], (string) $period['end_date'])) ?></td>
                            <td>
                                <span class="badge <?= (string) $period['status'] === 'OPEN' ? 'text-bg-success' : 'text-bg-danger' ?>">
                                    <?= e($statuses[(string) $period['status']] ?? (string) $period['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((int) $period['is_active'] === 1): ?>
                                    <span class="badge text-bg-primary">Aktif</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small"><?= e((string) ($period['updated_by_name'] ?: '-')) ?></td>
                            <td class="text-end table-action-col">
                                <?php if (Auth::hasRole('admin')): ?>
                                    <div class="table-action-menu">
                                        <button type="button" class="btn btn-sm btn-outline-primary table-action-trigger" aria-haspopup="true" aria-expanded="false">Aksi</button>
                                        <div class="table-action-panel">
                                            <a href="<?= e(base_url('/periods/edit?id=' . (int) $period['id'])) ?>">Edit periode</a>
                                            <a href="<?= e(base_url('/periods/checklist?id=' . (int) $period['id'])) ?>">Checklist tutup buku</a>
                                            <?php if ((int) $period['is_active'] === 1 && (string) $period['status'] === 'OPEN' && substr((string) $period['end_date'], 5, 5) === '12-31'): ?>
                                                <a href="<?= e(base_url('/periods/year-end-close?id=' . (int) $period['id'])) ?>">Tutup tahun buku</a>
                                            <?php endif; ?>
                                            <form method="post" action="<?= e(base_url('/periods/toggle-status?id=' . (int) $period['id'])) ?>" class="m-0">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit"><?= (string) $period['status'] === 'OPEN' ? 'Tutup periode' : 'Buka periode' ?></button>
                                            </form>
                                            <?php if ((int) $period['is_active'] !== 1): ?>
                                                <form method="post" action="<?= e(base_url('/periods/set-active?id=' . (int) $period['id'])) ?>" class="m-0">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" <?= (string) $period['status'] !== 'OPEN' ? 'disabled' : '' ?>>Set periode aktif</button>
                                                </form>
                                            <?php else: ?>
                                                <div class="table-action-note">Sedang aktif</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary small">Hanya admin yang dapat mengelola periode</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
