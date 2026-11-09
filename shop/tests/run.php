<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Core\{Json, HttpException};
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\{OrderValidator, CheckoutService};
use App\Repository\OrderRepository;

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    $checks++;
    echo 'PASS: ' . $message . PHP_EOL;
}
function rejects(callable $operation, int $status, string $message): void
{
    try { $operation(); } catch (HttpException $error) {
        check($error->status === $status, $message);
        return;
    }
    throw new RuntimeException('FAIL: expected rejection: ' . $message);
}
$root = dirname(__DIR__);
$categories = $root . '/dataset/pr08/categories.json';
$products = new ProductRepository($root . '/dataset/pr08/products.json', $categories);
$file = fopen($root . '/dataset/pr08/catalog_cases.csv', 'r');
fgetcsv($file, 0, ';', '"', '');
while (($row = fgetcsv($file, 0, ';', '"', '')) !== false) {
    [$case, $category, $sort, $minimum] = $row;
    $items = $products->catalog($category === '' ? null : (int) $category, $sort);
    check((int) $minimum === 0 ? $items === [] : count($items) >= (int) $minimum, 'catalog: ' . $case);
    if ($sort === 'price') {
        $prices = array_map(static fn($p) => $p->price, $items);
        $sorted = $prices; sort($sorted);
        check($prices === $sorted, 'catalog sorted by price');
    }
}
fclose($file);
$fixtures = new ProductRepository($root . '/dataset/pr09/products.json', $categories);
foreach (Json::read($root . '/dataset/pr09/cart_scenarios.json') as $index => $scenario) {
    $cart = new CartService($fixtures);
    foreach ($scenario['steps'] as $step) {
        $parts = explode(':', $step);
        match ($parts[0]) {
            'add' => $cart->add((int) $parts[1]),
            'remove' => $cart->remove((int) $parts[1]),
            'set' => $cart->set((int) $parts[1], (int) $parts[2]),
        };
    }
    check($cart->state() == $scenario['expected'], 'cart scenario ' . ($index + 1));
    $expectedTotal = $expectedCount = 0;
    foreach ($scenario['expected'] as $id => $qty) {
        $expectedTotal += $fixtures->find((int) $id)->price * $qty;
        $expectedCount += $qty;
    }
    check($cart->summary()['total'] === $expectedTotal && $cart->summary()['count'] === $expectedCount, 'cart totals ' . ($index + 1));
}
$cart = new CartService($products);
rejects(fn() => $cart->add(99999), 404, 'unknown product rejected');
rejects(fn() => $cart->add(1, -1), 422, 'negative quantity rejected');
rejects(fn() => $cart->add(11), 422, 'out of stock rejected');
$methods = Json::read($root . '/dataset/pr10/delivery_methods.json');
$validator = new OrderValidator($methods);
foreach (Json::read($root . '/dataset/pr10/order_cases.json') as $case) {
    check(($validator->validate($case['input']) === []) === $case['valid'], 'order: ' . $case['case']);
}
check(isset($validator->validate(['name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'unknown'])['delivery']), 'unknown delivery rejected');
check(isset($validator->validate(['name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'courier'])['address']), 'courier requires address');
$temporary = sys_get_temp_dir() . '/trpo-order-' . bin2hex(random_bytes(8)) . '.json';
try {
    $cart = new CartService($products);
    $cart->add(1, 2);
    $repository = new OrderRepository($temporary, $root . '/dataset/pr10/orders_seed.json');
    $checkout = new CheckoutService($cart, $validator, $repository);
    $order = $checkout->place(['name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'courier', 'address' => 'Учебная, 1']);
    check($order->total === 1590, 'order total includes delivery');
    check($cart->state() === [], 'cart cleared after save');
    check(Json::read($temporary)[0]['items'][0]['price'] === 570, 'order contains server price snapshot');
    $cart->add(2);
    $checkout->place(['name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'pickup']);
    check(count(Json::read($temporary)) === 2, 'second order preserves first order');
    $cart->add(1);
    file_put_contents($temporary, '{broken json');
    try { $checkout->place(['name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'pickup']); throw new RuntimeException('Expected JSON failure'); }
    catch (JsonException) { check($cart->state() === [1 => 1], 'storage failure retains cart'); }
} finally {
    foreach ([$temporary, $temporary . '.lock'] as $path) { if (is_file($path)) { unlink($path); } }
}
echo "Checks: $checks\n";
