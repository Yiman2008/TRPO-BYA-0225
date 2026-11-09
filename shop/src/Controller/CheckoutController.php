<?php
declare(strict_types=1);
namespace App\Controller;
use App\Core\{View, Session, HttpException};
use App\Service\{CartService, OrderValidator, CheckoutService};

final class CheckoutController
{
    public function __construct(private readonly View $view, private readonly Session $session,
        private readonly CartService $cart, private readonly OrderValidator $validator,
        private readonly CheckoutService $checkout, private readonly array $deliveryMethods) {}

    public function index(): void
    {
        if ($this->cart->summary()['lines'] === []) {
            $this->session->flash('Добавьте товары перед оформлением заказа');
            header('Location: /cart', true, 302);
            return;
        }
        $this->form($this->validator->normalize([]), []);
    }

    public function store(): void
    {
        $this->session->verifyToken($_POST['csrf'] ?? null);
        $data = $this->validator->normalize($_POST);
        $errors = $this->validator->validate($_POST);
        if ($this->cart->summary()['lines'] === []) { $errors['cart'] = 'Корзина пуста'; }
        if ($errors !== []) { $this->form($data, $errors, 422); return; }
        $order = $this->checkout->place($data);
        $this->session->set('cart', []);
        $this->session->set('confirmation', ['id' => $order->id, 'total' => $order->total,
            'delivery' => $order->delivery['title']]);
        header('Location: /order/success', true, 302);
    }

    public function success(): void
    {
        $confirmation = $this->session->get('confirmation');
        if (!is_array($confirmation)) { throw new HttpException(404, 'Заказ в этой сессии не найден'); }
        $this->view->render('success', ['title' => 'Заказ принят', 'order' => $confirmation]);
    }

    private function form(array $data, array $errors, int $status = 200): void
    {
        $this->view->render('checkout', ['title' => 'Оформление заказа', 'data' => $data, 'errors' => $errors,
            'methods' => $this->deliveryMethods, 'summary' => $this->cart->summary()], $status);
    }
}
