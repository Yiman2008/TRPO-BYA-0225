<?php
declare(strict_types=1);
namespace App\Model;

final class Category
{
    public function __construct(public readonly int $id, public readonly string $name) {}
}
