<?php

declare(strict_types=1);

/**
 * Товар каталога.
 * Обязательные свойства — по class_requirements.csv: id (int), name (string), price (float), categoryId (int).
 * active (bool) есть в products.json, но не обязателен по требованиям, поэтому по умолчанию true.
 * Модель описывает только данные и поведение и ничего не знает об HTML.
 */
final class Product
{
    public const BUDGET_LIMIT = 800.0;
    public const PREMIUM_LIMIT = 1500.0;

    private string $name;

    public function __construct(
        private int $id,
        string $name,
        private float $price,
        private int $categoryId,
        private bool $active = true,
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException("Product: id должен быть положительным, получено {$id}.");
        }
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException("Product #{$id}: название не может быть пустым.");
        }
        if ($price < 0) {
            throw new InvalidArgumentException("Product #{$id}: цена не может быть отрицательной ({$price}).");
        }
        if ($categoryId <= 0) {
            throw new InvalidArgumentException("Product #{$id}: некорректный categoryId {$categoryId}.");
        }
        $this->name = $name;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): float
    {
        return $this->price;
    }

    public function categoryId(): int
    {
        return $this->categoryId;
    }

    // ---------- Методы предметной области ----------

    /** Товар доступен для заказа. */
    public function isAvailable(): bool
    {
        return $this->active;
    }

    /** Принадлежит ли товар категории. */
    public function belongsTo(Category $category): bool
    {
        return $category->hasId($this->categoryId);
    }

    /** Цена со скидкой в процентах (0–100), округление до копеек. */
    public function priceWithDiscount(float $percent): float
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException("Скидка должна быть от 0 до 100%, получено {$percent}.");
        }

        return round($this->price * (1 - $percent / 100), 2);
    }

    /** Ценовой сегмент товара: budget / standard / premium. */
    public function priceSegment(): string
    {
        return match (true) {
            $this->price < self::BUDGET_LIMIT => 'budget',
            $this->price >= self::PREMIUM_LIMIT => 'premium',
            default => 'standard',
        };
    }

    /** Попадает ли цена в диапазон (границы включительно, null — без ограничения). */
    public function isPriceInRange(?float $min, ?float $max): bool
    {
        return ($min === null || $this->price >= $min) && ($max === null || $this->price <= $max);
    }

    /** Снять товар с продажи — возвращает новый объект, исходный не меняется. */
    public function deactivate(): self
    {
        return new self($this->id, $this->name, $this->price, $this->categoryId, false);
    }
}
