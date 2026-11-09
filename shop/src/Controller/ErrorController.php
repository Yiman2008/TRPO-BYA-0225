<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\View;

final class ErrorController
{
    public function __construct(private readonly View $view) {}

    public function notFound(): void
    {
        $this->show(404, 'Страница не найдена');
    }

    public function show(int $status, string $message): void
    {
        $this->view->render('error', ['title' => (string) $status, 'message' => $message], $status);
    }
}
