<?php
declare(strict_types=1);
abstract class Controller {
    protected function view(string $view, array $data=[], string $layout='main'): void { render_view($view,$data,$layout); }
    protected function redirect(string $path): never { redirect($path); }
}
