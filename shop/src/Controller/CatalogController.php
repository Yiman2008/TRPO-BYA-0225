<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\View;

final class CatalogController
{
    public function __construct(private readonly View $view) {}

    public function index(): void
    {
        $this->view->render('catalog', ['title' => 'Каталог', 'items' => [
            ['name' => 'Книга по PHP', 'price' => 900],
            ['name' => 'Учебный курс', 'price' => 2500],
            ['name' => 'Клавиатура', 'price' => 4300],
        ]]);
    }
}
