<?php declare(strict_types=1);

$releaseVersion = '1.1.0';
$releaseManifest = ROOT_PATH . '/release-manifest.json';
if (is_file($releaseManifest)) {
    $payload = json_decode((string) file_get_contents($releaseManifest), true);
    if (is_array($payload) && trim((string) ($payload['release_version'] ?? '')) !== '') {
        $releaseVersion = (string) $payload['release_version'];
    }
}
?>
<footer class="app-footer">
    <div class="container-fluid app-footer__inner">
        <div class="app-footer__meta">
            &copy; <?= date('Y') ?> <?= e(app_profile()['bumdes_name'] ?: app_config('name')) ?>
        </div>
        <div class="app-footer__meta">
            Versi <?= e($releaseVersion) ?>
        </div>
    </div>
</footer>
