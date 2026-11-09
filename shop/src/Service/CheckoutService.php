<?php
declare(strict_types=1);
namespace App\Service;
use App\Core\HttpException;
use App\Model\OrderData;
use App\Repository\OrderRepository;

final class CheckoutService
{
    public function __construct(private readonly CartService $cart, private readonly OrderValidator $validator,
        private readonly OrderRepository $orders) {}

    public function place(array $input): OrderData
    {
        if ($this->validator->validate($input) !== []) {
            throw new HttpException(422, 'Исправьте ошибки формы');
        }
        $summary = $this->cart->summary();
        if ($summary['lines'] === []) { throw new HttpException(422, 'Корзина пуста'); }
        $data = $this->validator->normalize($input);
        $delivery = $this->validator->delivery($data['delivery']);
        $delivery['address'] = $data['delivery'] === 'pickup' ? 'Учебный пункт выдачи магазина' : $data['address'];
        $items = array_map(static fn(array $line): array => ['product_id' => $line['product']->id,
            'name' => $line['product']->name, 'price' => $line['product']->price,
            'quantity' => $line['quantity'], 'subtotal' => $line['subtotal']], $summary['lines']);
        $order = new OrderData(strtoupper(bin2hex(random_bytes(6))),
            ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']],
            $delivery, $items, $summary['total'], $summary['total'] + $delivery['price']);
        $this->orders->save($order);
        $this->cart->clear();
        return $order;
    }
}
