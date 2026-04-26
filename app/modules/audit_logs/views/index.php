<?php declare(strict_types=1); ?>
<?php $listing = listing_paginate($rows ?? []); $rows = $listing['items']; $listingPath = '/audit-logs'; ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Audit Trail / Log Aktivitas</h1>
        <p class="text-secondary mb-0">Riwayat aktivitas penting aplikasi. Data ini membantu pelacakan perubahan jurnal, periode, profil, dan akses pengguna.</p>
        <div class="small text-secondary mt-2">Tampilan dibatasi maksimal <?= e((string) ($maxRows ?? 100)) ?> log terbaru sesuai filter agar halaman tetap ringan.</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Total Log</div><div class="display-6 fw-bold mb-0"><?= e((string) ($summary['total'] ?? 0)) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Warning</div><div class="display-6 fw-bold text-warning mb-0"><?= e((string) ($summary['warning'] ?? 0)) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Danger</div><div class="display-6 fw-bold text-danger mb-0"><?= e((string) ($summary['danger'] ?? 0)) ?></div></div></div></div>
    <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Aktivitas Hari Ini</div><div class="display-6 fw-bold text-info mb-0"><?= e((string) ($summary['today'] ?? 0)) ?></div></div></div></div>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <form method="get" action="<?= e(base_url('/audit-logs')) ?>" class="row g-3 align-items-end">
        <div class="col-lg-2">
            <label for="module_name" class="form-label">Modul</label>
            <select name="module_name" id="module_name" class="form-select">
                <option value="">Semua Modul</option>
                <?php foreach ($moduleOptions as $option): ?>
                    <option value="<?= e($option) ?>" <?= ($filters['module_name'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2">
            <label for="action_name" class="form-label">Aksi</label>
            <select name="action_name" id="action_name" class="form-select">
                <option value="">Semua Aksi</option>
                <?php foreach ($actionOptions as $option): ?>
                    <option value="<?= e($option) ?>" <?= ($filters['action_name'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2">
            <label for="severity_level" class="form-label">Severity</label>
            <select name="severity_level" id="severity_level" class="form-select">
                <option value="">Semua</option>
                <?php foreach (['info' => 'Info', 'warning' => 'Warning', 'danger' => 'Danger'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($filters['severity_level'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2"><label for="username" class="form-label">Username</label><input type="text" class="form-control" id="username" name="username" value="<?= e((string) ($filters['username'] ?? '')) ?>"></div>
        <div class="col-lg-2"><label for="date_from" class="form-label">Dari Tanggal</label><input type="date" class="form-control" id="date_from" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>"></div>
        <div class="col-lg-2"><label for="date_to" class="form-label">Sampai</label><input type="date" class="form-control" id="date_to" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>"></div>
        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= e(base_url('/audit-logs')) ?>" class="btn btn-outline-light">Reset</a>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body p-0">
    <?php if ($rows === []): ?>
        <div class="p-5 text-center text-secondary">Belum ada log yang cocok dengan filter saat ini.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 14%;">Waktu</th>
                        <th style="width: 12%;">User</th>
                        <th style="width: 12%;">Modul</th>
                        <th style="width: 10%;">Aksi</th>
                        <th>Deskripsi</th>
                        <th style="width: 10%;">Severity</th>
                        <th style="width: 18%;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="small"><?= e(audit_datetime((string) ($row['created_at'] ?? ''))) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['full_name'] ?? '-')) ?></div>
                                <div class="small text-secondary"><?= e((string) ($row['username'] ?? '-')) ?></div>
                            </td>
                            <td><?= e((string) ($row['module_name'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['action_name'] ?? '-')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($row['description'] ?? '-')) ?></div>
                                <div class="small text-secondary">Entity: <?= e((string) (($row['entity_type'] ?? '') !== '' ? $row['entity_type'] : '-')) ?><?= (string) ($row['entity_id'] ?? '') !== '' ? ' #' . e((string) $row['entity_id']) : '' ?></div>
                                <div class="small text-secondary">IP: <?= e((string) ($row['ip_address'] ?? '-')) ?></div>
                            </td>
                            <td><span class="badge <?= e(audit_badge_class((string) ($row['severity_level'] ?? 'info'))) ?>"><?= e(ucfirst((string) ($row['severity_level'] ?? 'info'))) ?></span></td>
                            <td>
                                <details>
                                    <summary class="small text-info">Lihat payload</summary>
                                    <?php if ($row['before_payload'] !== null): ?>
                                        <div class="small text-secondary mt-2">Sebelum</div>
                                        <pre class="small bg-black border rounded p-2"><?= e(is_array($row['before_payload']) ? json_encode($row['before_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '' : (string) $row['before_payload']) ?></pre>
                                    <?php endif; ?>
                                    <?php if ($row['after_payload'] !== null): ?>
                                        <div class="small text-secondary mt-2">Sesudah</div>
                                        <pre class="small bg-black border rounded p-2"><?= e(is_array($row['after_payload']) ? json_encode($row['after_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '' : (string) $row['after_payload']) ?></pre>
                                    <?php endif; ?>
                                    <?php if ($row['context_payload'] !== null): ?>
                                        <div class="small text-secondary mt-2">Konteks</div>
                                        <pre class="small bg-black border rounded p-2"><?= e(is_array($row['context_payload']) ? json_encode($row['context_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '' : (string) $row['context_payload']) ?></pre>
                                    <?php endif; ?>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php require APP_PATH . '/views/partials/listing_controls.php'; ?>
</div></div>
