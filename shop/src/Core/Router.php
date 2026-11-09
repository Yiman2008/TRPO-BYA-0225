<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$path][$method] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        if (!isset($this->routes[$path])) {
            throw new HttpException(404, 'Страница не найдена');
        }
        if (!isset($this->routes[$path][$method])) {
            header('Allow: ' . implode(', ', array_keys($this->routes[$path])));
            throw new HttpException(405, 'Метод запроса не поддерживается');
        }
        ($this->routes[$path][$method])();
    }
}
