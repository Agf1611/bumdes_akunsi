<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e5e7eb; margin: 0; padding: 32px; }
        .box { max-width: 760px; margin: 8vh auto 0; background: #111827; border: 1px solid #334155; border-radius: 18px; padding: 28px; }
        h1 { margin-top: 0; font-size: 2rem; }
        p { line-height: 1.6; color: #cbd5e1; }
        .meta { margin-top: 1rem; font-size: .92rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="box">
        <h1>503</h1>
        <p><?= e((string) ($message ?? 'Aplikasi sedang dalam mode maintenance.')) ?></p>
        <?php $maintenance = MaintenanceMode::state(); ?>
        <?php if (($maintenance['enabled_at'] ?? '') !== ''): ?>
            <div class="meta">Maintenance dimulai: <?= e((string) $maintenance['enabled_at']) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
