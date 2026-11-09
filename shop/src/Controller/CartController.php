<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\{View, Session, HttpException};
use App\Service\CartService;

final class CartController
{
    public function __construct(private readonly View $view, private readonly CartService $cart, private readonly Session $session) {}

    public function index(): void
    {
        $this->view->render('cart', ['title' => 'Корзина', 'summary' => $this->cart->summary()]);
    }

    public function add(): void { $this->change('add', 'Товар добавлен в корзину'); }
    public function update(): void { $this->change('set', 'Количество обновлено'); }
    public function remove(): void { $this->change('remove', 'Товар удалён из корзины'); }

    private function change(string $operation, string $message): void
    {
        $this->session->verifyToken($_POST['csrf'] ?? null);
        $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            throw new HttpException(422, 'Некорректный идентификатор товара');
        }
        if ($operation === 'remove') {
            $this->cart->remove($id);
        } else {
            $qty = filter_var($_POST['qty'] ?? ($operation === 'add' ? '1' : null), FILTER_VALIDATE_INT);
            if ($qty === false || $qty === null) {
                throw new HttpException(422, 'Количество должно быть целым числом');
            }
            $this->cart->{$operation}($id, $qty);
        }
        $this->session->set('cart', $this->cart->state());
        $this->session->flash($message);
        header('Location: /cart', true, 302);
    }
}
