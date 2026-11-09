<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\View;
use App\Core\HttpException;
use App\Core\Request;
use App\Repository\ProductRepository;

final class CatalogController
{
    public function __construct(private readonly View $view, private readonly ProductRepository $products) {}

    public function index(): void
    {
        $rawCategory = Request::string($_GET, 'category');
        $category = $rawCategory === '' ? null : Request::positiveInt($_GET, 'category');
        $sort = Request::string($_GET, 'sort', 'name');
        if (!in_array($sort, ['name', 'price'], true)) {
            throw new HttpException(422, 'Неизвестный режим сортировки');
        }
        $this->view->render('catalog', ['title' => 'Каталог',
            'items' => $this->products->catalog($category, $sort),
            'categories' => $this->products->categories(), 'category' => $category, 'sort' => $sort]);
    }

    public function show(): void
    {
        $id = Request::positiveInt($_GET, 'id');
        $product = $this->products->find($id);
        if ($product === null) {
            throw new HttpException(404, 'Товар не найден');
        }
        $this->view->render('product', ['title' => $product->name, 'product' => $product]);
    }
}
