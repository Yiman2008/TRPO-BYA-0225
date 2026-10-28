<?php

declare(strict_types=1);

/**
 * Категория каталога. Свойства — по class_requirements.csv: id (int), name (string).
 */
final class Category
{
    private string $name;

    public function __construct(
        private int $id,
        string $name,
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException("Category: id должен быть положительным, получено {$id}.");
        }
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException("Category #{$id}: название не может быть пустым.");
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

    /** Совпадает ли категория с переданным идентификатором. */
    public function hasId(int $id): bool
    {
        return $this->id === $id;
    }
}
