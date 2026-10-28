<?php

declare(strict_types=1);

/**
 * Файловое JSON-хранилище: одна функция чтения и одна функция записи для всех файлов.
 */
final class StorageException extends RuntimeException
{
}

/**
 * Читает JSON-файл и возвращает массив. Ошибки превращает в StorageException
 * с понятным сообщением, чтобы точка входа могла показать страницу ошибки, а не fatal error.
 *
 * @return array<int|string, mixed>
 */
function loadJson(string $path): array
{
    if (!is_file($path)) {
        throw new StorageException('Файл данных не найден: ' . basename($path));
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new StorageException('Не удалось прочитать файл: ' . basename($path));
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new StorageException(basename($path) . ' повреждён: ' . $e->getMessage(), 0, $e);
    }
    if (!is_array($data)) {
        throw new StorageException(basename($path) . ': ожидался массив записей.');
    }

    return $data;
}

/**
 * Сохраняет массив в JSON-файл с блокировкой на время записи.
 *
 * @param array<int|string, mixed> $data
 */
function saveJson(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
        throw new StorageException('Не удалось записать файл: ' . basename($path));
    }
}
