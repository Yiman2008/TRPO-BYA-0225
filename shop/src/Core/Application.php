<?php
declare(strict_types=1);
namespace App\Core;
use App\Controller\{HomeController, CatalogController, ErrorController};

final class Application
{
    public function __construct(private readonly string $root) {}

    public function run(): void
    {
        $view = new View($this->root . '/views');
        $error = new ErrorController($view);
        try {
            $page = Json::read($this->root . '/dataset/pr07/pages.json');
            $view->share(['storeName' => $page['store_name']]);
            $controllers = [
                'HomeController' => new HomeController($view, $page),
                'CatalogController' => new CatalogController($view),
                'ErrorController' => $error,
            ];
            $router = new Router();
            $handle = fopen($this->root . '/dataset/pr07/routes.csv', 'r');
            if ($handle === false) {
                throw new \RuntimeException('Не удалось открыть маршруты');
            }
            fgetcsv($handle, 0, ';', '"', '');
            while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
                [$method, $path, $controller, $action] = $row;
                $router->add($method, $path, [$controllers[$controller], $action]);
            }
            fclose($handle);
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', is_string($path) ? $path : '/');
        } catch (HttpException $exception) {
            $error->show($exception->status, $exception->getMessage());
        } catch (\Throwable $exception) {
            error_log((string) $exception);
            $error->show(500, 'Не удалось загрузить страницу. Проверьте журнал сервера.');
        }
    }
}
