<?php
declare(strict_types=1);
final class App {
    private array $routes = [];
    public function get(string $path, array $action, array $middlewares=[]): void { $this->add('GET',$path,$action,$middlewares); }
    public function post(string $path, array $action, array $middlewares=[]): void { $this->add('POST',$path,$action,$middlewares); }
    private function add(string $method, string $path, array $action, array $middlewares): void { $this->routes[$method][$this->normalize($path)] = ['action'=>$action,'middlewares'=>$middlewares]; }
    public function dispatch(): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $this->currentPath();
        $route = $this->routes[$method][$path] ?? null;
        if (!$route) { http_response_code(404); render_error_page(404, 'Halaman yang Anda cari tidak ditemukan.'); return; }
        foreach ($route['middlewares'] as $mw) {
            if (is_array($mw)) { $obj = new $mw[0](); $obj->handle($mw[1] ?? []); }
            else { $obj = new $mw(); $obj->handle(); }
        }
        [$controllerName,$methodName] = $route['action'];
        $controller = new $controllerName();
        $controller->{$methodName}();
    }
    private function currentPath(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $appPath = parse_url(app_config('url'), PHP_URL_PATH) ?: '';
        if ($appPath !== '' && str_starts_with($uri, $appPath)) $uri = substr($uri, strlen($appPath)) ?: '/';
        return $this->normalize($uri);
    }
    private function normalize(string $path): string { $path='/' . trim($path,'/'); return $path==='/'?'/':rtrim($path,'/'); }
}
