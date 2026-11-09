<?php
declare(strict_types=1);
namespace App\Core;
use App\Controller\{HomeController, CatalogController, ErrorController, CartController, CheckoutController};
use App\Repository\{ProductRepository, OrderRepository};
use App\Service\{CartService, OrderValidator, CheckoutService};

final class Application
{
    public function __construct(private readonly string $root) {}

    public function run(): void
    {
        $view = new View($this->root . '/views');
        $error = new ErrorController($view);
        try {
            $session = new Session();
            $session->start();
            $page = Json::read($this->root . '/dataset/pr07/pages.json');
            $view->share(['storeName' => $page['store_name']]);
            $products = new ProductRepository($this->root . '/dataset/pr08/products.json', $this->root . '/dataset/pr08/categories.json');
            $state = $session->get('cart', []);
            $cart = new CartService($products, is_array($state) ? $state : []);
            $view->share(['cartCount' => $cart->summary()['count'], 'csrf' => $session->token(), 'flash' => $session->flash()]);
            $cartController = new CartController($view, $cart, $session);
            $methods = Json::read($this->root . '/dataset/pr10/delivery_methods.json');
            $validator = new OrderValidator($methods);
            $ordersPath = getenv('SHOP_ORDERS_PATH') ?: $this->root . '/var/orders.json';
            $orders = new OrderRepository($ordersPath, $this->root . '/dataset/pr10/orders_seed.json');
            $checkout = new CheckoutController($view, $session, $cart, $validator,
                new CheckoutService($cart, $validator, $orders), $methods);
            $catalog = new CatalogController($view, $products);
            $controllers = [
                'HomeController' => new HomeController($view, $page),
                'CatalogController' => $catalog,
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
            $router->add('GET', '/product', [$catalog, 'show']);
            $router->add('GET', '/cart', [$cartController, 'index']);
            $router->add('POST', '/cart/add', [$cartController, 'add']);
            $router->add('POST', '/cart/update', [$cartController, 'update']);
            $router->add('POST', '/cart/remove', [$cartController, 'remove']);
            $router->add('GET', '/checkout', [$checkout, 'index']);
            $router->add('POST', '/checkout', [$checkout, 'store']);
            $router->add('GET', '/order/success', [$checkout, 'success']);
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
