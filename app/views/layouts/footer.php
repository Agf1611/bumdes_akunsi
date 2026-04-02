<?php declare(strict_types=1); ?>
<footer class="app-footer">
    <div class="container-fluid app-footer__inner">
        <div>
            <div class="app-footer__brand"><?= e(app_config('name')) ?></div>
            <div class="app-footer__meta">Antarmuka modern untuk operasional harian dan pelaporan keuangan BUMDes.</div>
        </div>
        <div class="app-footer__meta text-md-end">
            <div><?= date('Y') ?> &middot; Produksi</div>
            <div>Bootstrap 5 · PHP Native · MariaDB/MySQL</div>
        </div>
    </div>
</footer>
