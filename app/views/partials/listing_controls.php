<?php declare(strict_types=1);
$listing = is_array($listing ?? null) ? $listing : ['total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 1, 'from' => 0, 'to' => 0, 'has_prev' => false, 'has_next' => false, 'prev_page' => 1, 'next_page' => 1];
$listingPath = (string) ($listingPath ?? '');
$listingPerPageOptions = listing_per_page_options();
$currentPage = max(1, (int) ($listing['page'] ?? 1));
$totalPages = max(1, (int) ($listing['total_pages'] ?? 1));
?>
<div class="listing-controls-shell d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 px-3 py-3 border-top">
    <div class="small text-secondary">
        Menampilkan <strong><?= e((string) ($listing['from'] ?? 0)) ?></strong> - <strong><?= e((string) ($listing['to'] ?? 0)) ?></strong> dari <strong><?= e((string) ($listing['total'] ?? 0)) ?></strong> data
    </div>
    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 ms-xl-auto">
        <form method="get" action="<?= e(base_url($listingPath)) ?>" class="d-flex align-items-center gap-2 m-0 flex-wrap">
            <?php foreach ($_GET as $key => $value): ?>
                <?php if (in_array((string) $key, ['page', 'per_page'], true)) { continue; } ?>
                <?php if (is_array($value)) { continue; } ?>
                <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
            <?php endforeach; ?>
            <label for="listing_per_page" class="small text-secondary mb-0">Baris</label>
            <select id="listing_per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($listingPerPageOptions as $option): ?>
                    <option value="<?= e((string) $option) ?>" <?= (int) ($listing['per_page'] ?? 10) === $option ? 'selected' : '' ?>><?= e((string) $option) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <form method="get" action="<?= e(base_url($listingPath)) ?>" class="d-flex align-items-center gap-2 m-0 flex-wrap">
            <?php foreach ($_GET as $key => $value): ?>
                <?php if (in_array((string) $key, ['page'], true)) { continue; } ?>
                <?php if (is_array($value)) { continue; } ?>
                <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
            <?php endforeach; ?>
            <label for="listing_page" class="small text-secondary mb-0">Halaman</label>
            <select id="listing_page" name="page" class="form-select form-select-sm" onchange="this.form.submit()" <?= $totalPages <= 1 ? 'disabled' : '' ?>>
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <option value="<?= e((string) $pageNumber) ?>" <?= $currentPage === $pageNumber ? 'selected' : '' ?>><?= e((string) $pageNumber) ?> / <?= e((string) $totalPages) ?></option>
                <?php endfor; ?>
            </select>
        </form>

        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
            <?php if (!empty($listing['has_prev'])): ?>
                <a href="<?= e(base_url($listingPath . '?' . listing_query_string(['page' => (string) ($listing['prev_page'] ?? 1), 'per_page' => (string) ($listing['per_page'] ?? 10)]))) ?>" class="btn btn-sm btn-outline-light">Sebelumnya</a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-light disabled">Sebelumnya</span>
            <?php endif; ?>
            <span class="small text-secondary px-1">Halaman <strong><?= e((string) $currentPage) ?></strong> / <?= e((string) $totalPages) ?></span>
            <?php if (!empty($listing['has_next'])): ?>
                <a href="<?= e(base_url($listingPath . '?' . listing_query_string(['page' => (string) ($listing['next_page'] ?? 1), 'per_page' => (string) ($listing['per_page'] ?? 10)]))) ?>" class="btn btn-sm btn-outline-light">Berikutnya</a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-light disabled">Berikutnya</span>
            <?php endif; ?>
        </div>
    </div>
</div>
