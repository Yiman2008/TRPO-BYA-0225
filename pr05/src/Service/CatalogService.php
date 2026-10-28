<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/Product.php';
require_once __DIR__ . '/../Model/Category.php';

/**
 * Загружает products.json и categories.json, создаёт объекты Product и Category
 * и выполняет операции над набором товаров: фильтрация, агрегаты, поиск категории.
 */
final class CatalogService
{
    /** @var list<Product> */
    private array $products = [];

    /** @var array<int, Category> категории по id */
    private array $categories = [];

    /** @var list<string> сообщения о строках dataset, которые не удалось превратить в объекты */
    private array $warnings = [];

    public function __construct(string $productsPath, string $categoriesPath)
    {
        foreach ($this->readJson($categoriesPath) as $index => $row) {
            try {
                $category = new Category((int) ($row['id'] ?? 0), (string) ($row['name'] ?? ''));
                $this->categories[$category->id()] = $category;
            } catch (InvalidArgumentException $e) {
                $this->warnings[] = "categories.json, запись {$index}: {$e->getMessage()}";
            }
        }

        foreach ($this->readJson($productsPath) as $index => $row) {
            try {
                $this->products[] = new Product(
                    (int) ($row['id'] ?? 0),
                    (string) ($row['name'] ?? ''),
                    (float) ($row['price'] ?? -1),
                    (int) ($row['category_id'] ?? 0),
                    (bool) ($row['active'] ?? true),
                );
            } catch (InvalidArgumentException $e) {
                $this->warnings[] = "products.json, запись {$index}: {$e->getMessage()}";
            }
        }
    }

    /** @return list<Product> */
    public function products(): array
    {
        return $this->products;
    }

    /** @return list<Category> */
    public function categories(): array
    {
        return array_values($this->categories);
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function findCategory(int $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    /** Название категории товара или null, если категории нет в categories.json. */
    public function categoryOf(Product $product): ?Category
    {
        return $this->findCategory($product->categoryId());
    }

    /**
     * Фильтр по категории. null — все товары.
     *
     * @param list<Product>|null $products
     * @return list<Product>
     */
    public function filterByCategory(?int $categoryId, ?array $products = null): array
    {
        $products ??= $this->products;
        if ($categoryId === null) {
            return $products;
        }

        return array_values(array_filter(
            $products,
            static fn (Product $p): bool => $p->categoryId() === $categoryId
        ));
    }

    /**
     * Только доступные товары.
     *
     * @param list<Product> $products
     * @return list<Product>
     */
    public function onlyAvailable(array $products): array
    {
        return array_values(array_filter($products, static fn (Product $p): bool => $p->isAvailable()));
    }

    /** @param list<Product> $products */
    public function averagePrice(array $products): float
    {
        if ($products === []) {
            return 0.0;
        }

        return round($this->totalPrice($products) / count($products), 2);
    }

    /** @param list<Product> $products */
    public function totalPrice(array $products): float
    {
        return array_reduce($products, static fn (float $sum, Product $p): float => $sum + $p->price(), 0.0);
    }

    /**
     * Количество товаров по каждой категории.
     *
     * @return array<int, int> categoryId => количество
     */
    public function countByCategory(): array
    {
        $counts = array_fill_keys(array_keys($this->categories), 0);
        foreach ($this->products as $product) {
            $counts[$product->categoryId()] = ($counts[$product->categoryId()] ?? 0) + 1;
        }

        return $counts;
    }

    /** @return list<array<string, mixed>> */
    private function readJson(string $path): array
    {
        if (!is_readable($path)) {
            $this->warnings[] = 'Файл не найден: ' . basename($path);
            return [];
        }
        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->warnings[] = basename($path) . ': повреждённый JSON (' . $e->getMessage() . ')';
            return [];
        }

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }
}
