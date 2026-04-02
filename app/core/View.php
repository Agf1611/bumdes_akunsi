<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = APP_PATH . '/modules/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new HttpException('Tampilan yang diminta tidak ditemukan.', 500);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            throw new HttpException('Layout aplikasi tidak ditemukan.', 500);
        }

        require $layoutFile;
    }
}
