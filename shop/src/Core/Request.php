<?php
declare(strict_types=1);
namespace App\Core;

final class Request
{
    public static function string(array $input, string $key, string $default = ''): string
    {
        $value = $input[$key] ?? $default;
        if (!is_string($value)) { throw new HttpException(422, 'Ожидалось строковое поле: ' . $key); }
        return trim($value);
    }

    public static function positiveInt(array $input, string $key, ?int $default = null): int
    {
        $raw = $input[$key] ?? $default;
        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ((!is_string($raw) && !is_int($raw)) || $value === false || $value < 1) {
            throw new HttpException(422, 'Ожидалось положительное целое число: ' . $key);
        }
        return $value;
    }
}
