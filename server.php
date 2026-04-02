<?php
declare(strict_types=1);
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$public = __DIR__ . '/public';
$file = realpath($public . $uri);
if ($uri !== '/' && $file !== false && str_starts_with($file, realpath($public)) && is_file($file)) return false;
require __DIR__ . '/public/index.php';
