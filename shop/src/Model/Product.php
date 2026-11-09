<?php
declare(strict_types=1);
namespace App\Model;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price,
        public readonly Category $category,
        public readonly ?int $stock,
    ) {
        if ($id < 1 || $price < 0 || ($stock !== null && $stock < 0)) {
            throw new \InvalidArgumentException('Некорректные данные товара');
        }
    }
}
