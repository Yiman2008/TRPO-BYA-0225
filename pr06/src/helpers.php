<?php

declare(strict_types=1);

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Подключает шаблон страницы внутри общего layout и передаёт ему только переданные данные.
 * Шаблон не читает файлы и не меняет session — он получает всё готовое в $data.
 *
 * @param array<string, mixed> $data
 */
function render(string $template, array $data = [], int $status = 200): void
{
    http_response_code($status);
    $templatesDir = __DIR__ . '/../templates';

    $content = (static function (string $__file, array $__data): string {
        extract($__data, EXTR_SKIP);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    })($templatesDir . '/' . $template . '.php', $data);

    (static function (string $__file, array $__data, string $content): void {
        extract($__data, EXTR_SKIP);
        require $__file;
    })($templatesDir . '/layout.php', $data, $content);
}

/** Redirect после POST (Post/Redirect/Get), чтобы F5 не повторял действие. */
function redirect(string $page): never
{
    header('Location: index.php?page=' . rawurlencode($page), true, 303);
    exit;
}

/** Строковый параметр запроса без warning, даже если пришёл массив. */
function param(array $source, string $key): string
{
    $value = $source[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}
