<?php
declare(strict_types=1);

$installLock = __DIR__ . '/../storage/installed.lock';
if (!is_file($installLock)) {
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '/' || $basePath === '.') {
        $basePath = '';
    }
    header('Location: ' . $basePath . '/install.php');
    exit;
}

$router = require __DIR__ . '/../app/bootstrap.php';
$router->dispatch();
