<?php
declare(strict_types=1);
namespace App\Service;

final class OrderValidator
{
    public function __construct(private readonly array $deliveryMethods) {}

    public function normalize(array $input): array
    {
        $data = [];
        foreach (['name', 'email', 'phone', 'address', 'delivery'] as $field) {
            $data[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        }
        return $data;
    }

    public function validate(array $input): array
    {
        $data = $this->normalize($input);
        $errors = [];
        if ($data['name'] === '' || strlen($data['name']) > 240) {
            $errors['name'] = 'Введите имя длиной до 120 символов';
        }
        if (strlen($data['email']) > 254 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email';
        }
        if ($data['phone'] !== '' && !preg_match('/^[+0-9 ()-]{7,30}$/D', $data['phone'])) {
            $errors['phone'] = 'Проверьте номер телефона';
        }
        if ($this->delivery($data['delivery']) === null) {
            $errors['delivery'] = 'Выберите существующий способ доставки';
        }
        if (($data['delivery'] === 'courier' && $data['address'] === '') || strlen($data['address']) > 1000) {
            $errors['address'] = 'Для курьера укажите адрес длиной до 500 символов';
        }
        return $errors;
    }

    public function delivery(string $id): ?array
    {
        foreach ($this->deliveryMethods as $method) {
            if ($method['id'] === $id) { return $method; }
        }
        return null;
    }
}
