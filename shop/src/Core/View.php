<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    private array $shared = [];

    public function __construct(private readonly string $directory) {}

    public function share(array $data): void
    {
        $this->shared = array_merge($this->shared, $data);
    }

    public function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        extract(array_merge($this->shared, $data), EXTR_SKIP);
        ob_start();
        try {
            require $this->directory . '/' . $template . '.php';
            $content = ob_get_clean();
        } catch (\Throwable $error) {
            ob_end_clean();
            throw $error;
        }
        require $this->directory . '/layout.php';
    }
}
