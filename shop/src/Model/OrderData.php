<?php
declare(strict_types=1);
namespace App\Model;

final class OrderData
{
    public function __construct(
        public readonly string $id,
        public readonly array $contact,
        public readonly array $delivery,
        public readonly array $items,
        public readonly int $subtotal,
        public readonly int $total,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'created_at' => gmdate('c'), 'contact' => $this->contact,
            'delivery' => $this->delivery, 'items' => $this->items, 'subtotal' => $this->subtotal, 'total' => $this->total];
    }
}
