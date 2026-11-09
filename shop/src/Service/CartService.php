<?php
declare(strict_types=1);
namespace App\Service;
use App\Core\HttpException;
use App\Repository\ProductRepository;

final class CartService
{
    private array $quantities;

    public function __construct(private readonly ProductRepository $products, array $quantities = [])
    {
        $this->quantities = [];
        foreach ($quantities as $id => $qty) {
            $product = $this->products->find((int) $id);
            if ($product !== null && is_int($qty) && $qty > 0 && $qty <= 999 && ($product->stock === null || $qty <= $product->stock)) {
                $this->quantities[(int) $id] = $qty;
            }
        }
    }

    public function add(int $id, int $quantity = 1): void
    {
        $this->validateQuantity($quantity);
        $this->set($id, ($this->quantities[$id] ?? 0) + $quantity);
    }

    public function set(int $id, int $quantity): void
    {
        $product = $this->products->find($id) ?? throw new HttpException(404, 'Товар не найден');
        $this->validateQuantity($quantity);
        if ($product->stock !== null && $quantity > $product->stock) {
            throw new HttpException(422, 'Недостаточно товара на складе');
        }
        $this->quantities[$id] = $quantity;
    }

    public function remove(int $id): void
    {
        if ($this->products->find($id) === null) {
            throw new HttpException(404, 'Товар не найден');
        }
        unset($this->quantities[$id]);
    }

    public function clear(): void { $this->quantities = []; }
    public function state(): array { return $this->quantities; }

    public function summary(): array
    {
        $lines = [];
        $total = $count = 0;
        foreach ($this->quantities as $id => $quantity) {
            $product = $this->products->find($id);
            $subtotal = $product->price * $quantity;
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
            $total += $subtotal;
            $count += $quantity;
        }
        return ['lines' => $lines, 'total' => $total, 'count' => $count];
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 999) {
            throw new HttpException(422, 'Количество должно быть целым числом от 1 до 999');
        }
    }
}
