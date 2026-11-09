<?php
declare(strict_types=1);
namespace App\Core;

final class Json
{
    public static function read(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Не удалось прочитать данные');
        }
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException('Ожидался JSON-массив');
        }
        return $data;
    }
}
