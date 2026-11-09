<?php
declare(strict_types=1);
namespace App\Repository;
use App\Core\Json;
use App\Model\{Product, Category};

final class ProductRepository
{
    private array $products = [];
    private array $categories = [];

    public function __construct(string $productsFile, string $categoriesFile)
    {
        foreach (Json::read($categoriesFile) as $row) {
            $this->categories[$row['id']] = new Category((int) $row['id'], $row['name']);
        }
        foreach (Json::read($productsFile) as $row) {
            // PR09 has a separate minimal fixture without category or stock fields.
            $category = isset($row['category_id'])
                ? ($this->categories[$row['category_id']] ?? throw new \RuntimeException('Категория не найдена'))
                : new Category(0, 'Учебные товары');
            $product = new Product((int) $row['id'], $row['name'], (int) $row['price'], $category,
                isset($row['stock']) ? (int) $row['stock'] : null);
            $this->products[$product->id] = $product;
        }
    }

    public function find(int $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    public function categories(): array
    {
        return array_values($this->categories);
    }

    public function catalog(?int $category = null, string $sort = 'name'): array
    {
        $items = array_values(array_filter($this->products,
            static fn(Product $p): bool => $category === null || $p->category->id === $category));
        usort($items, $sort === 'price'
            ? static fn(Product $a, Product $b): int => $a->price <=> $b->price ?: $a->id <=> $b->id
            : static fn(Product $a, Product $b): int => strnatcasecmp($a->name, $b->name));
        return $items;
    }
}
