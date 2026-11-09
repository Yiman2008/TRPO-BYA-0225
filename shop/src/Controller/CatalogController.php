<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\View;
use App\Core\HttpException;
use App\Repository\ProductRepository;

final class CatalogController
{
    public function __construct(private readonly View $view, private readonly ProductRepository $products) {}

    public function index(): void
    {
        $rawCategory = $_GET['category'] ?? '';
        $category = $rawCategory === '' ? null : filter_var($rawCategory, FILTER_VALIDATE_INT);
        if ($category === false || ($category !== null && $category < 1)) {
            throw new HttpException(422, 'Некорректная категория');
        }
        $sort = $_GET['sort'] ?? 'name';
        if (!is_string($sort) || !in_array($sort, ['name', 'price'], true)) {
            throw new HttpException(422, 'Неизвестный режим сортировки');
        }
        $this->view->render('catalog', ['title' => 'Каталог',
            'items' => $this->products->catalog($category, $sort),
            'categories' => $this->products->categories(), 'category' => $category, 'sort' => $sort]);
    }

    public function show(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $product = $id === false || $id === null ? null : $this->products->find($id);
        if ($product === null) {
            throw new HttpException(404, 'Товар не найден');
        }
        $this->view->render('product', ['title' => $product->name, 'product' => $product]);
    }
}
