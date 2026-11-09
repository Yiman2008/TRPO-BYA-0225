<?php
declare(strict_types=1);
namespace App\Repository;
use App\Core\Json;
use App\Model\OrderData;

final class OrderRepository
{
    public function __construct(private readonly string $path, private readonly string $seed) {}

    public function save(OrderData $order): void
    {
        // A separate stable lock protects read-modify-write across atomic renames.
        $lock = fopen($this->path . '.lock', 'c');
        if ($lock === false) { throw new \RuntimeException('Не удалось открыть блокировку заказов'); }
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) { throw new \RuntimeException('Не удалось заблокировать заказы'); }
            $orders = Json::read(is_file($this->path) ? $this->path : $this->seed);
            foreach ($orders as $existing) {
                if (($existing['id'] ?? null) === $order->id) { throw new \RuntimeException('Номер заказа уже существует'); }
            }
            $orders[] = $order->toArray();
            $json = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $temporary = tempnam(dirname($this->path), 'orders-');
            if ($temporary === false || file_put_contents($temporary, $json) !== strlen($json)) {
                throw new \RuntimeException('Не удалось записать заказ');
            }
            if (!rename($temporary, $this->path)) { throw new \RuntimeException('Не удалось сохранить заказ'); }
            $temporary = null;
        } finally {
            if (is_string($temporary) && is_file($temporary)) { unlink($temporary); }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
