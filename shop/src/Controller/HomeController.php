<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\View;

final class HomeController
{
    public function __construct(private readonly View $view, private readonly array $page) {}

    public function index(): void
    {
        $this->view->render('home', ['title' => $this->page['store_name'], 'page' => $this->page]);
    }

    public function about(): void
    {
        $this->view->render('about', ['title' => 'О магазине']);
    }
}
