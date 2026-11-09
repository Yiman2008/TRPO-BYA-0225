<?php
declare(strict_types=1);
// Self-contained integration test: starts and stops its own PHP server.
$root = dirname(__DIR__);
require $root . '/bootstrap.php';
use App\Core\Json;
$checks = 0;
function assertHttp(bool $value, string $message): void
{
    global $checks;
    if (!$value) { throw new RuntimeException('FAIL: ' . $message); }
    $checks++;
    echo 'PASS: ' . $message . PHP_EOL;
}
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($socket === false) { throw new RuntimeException($error); }
$address = stream_socket_get_name($socket, false);
fclose($socket);
$temporary = sys_get_temp_dir() . '/trpo-http-' . bin2hex(random_bytes(8));
mkdir($temporary);
putenv('SHOP_ORDERS_PATH=' . $temporary . '/orders.json');
$log = $temporary . '/server.log';
$process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . $temporary, '-S', $address,
    '-t', $root . '/public', $root . '/public/router.php'],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root);
if (!is_resource($process)) { throw new RuntimeException('Could not start PHP server'); }
fclose($pipes[0]);
$cookie = '';
function request(string $method, string $path, array $data = [], bool $fresh = false): array
{
    global $address, $cookie;
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    if (!$fresh && $cookie !== '') { $headers[] = 'Cookie: ' . $cookie; }
    $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers),
        'content' => $method === 'POST' ? http_build_query($data) : '', 'ignore_errors' => true,
        'follow_location' => 0, 'timeout' => 5]]);
    $body = file_get_contents('http://' . $address . $path, false, $context);
    $responseHeaders = $http_response_header ?? [];
    foreach ($responseHeaders as $header) {
        if (!$fresh && preg_match('/^Set-Cookie: ([^;]+)/i', $header, $match)) { $cookie = $match[1]; }
    }
    preg_match('/^HTTP\/\S+ (\d+)/', $responseHeaders[0] ?? '', $match);
    return ['status' => (int) ($match[1] ?? 0), 'body' => $body === false ? '' : $body, 'headers' => $responseHeaders];
}
function token(): string
{
    $response = request('GET', '/catalog');
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $response['body'], $match);
    if (!isset($match[1])) { throw new RuntimeException('No CSRF token in catalog'); }
    return $match[1];
}
try {
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($connection !== false) { fclose($connection); $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) { throw new RuntimeException('Server did not start: ' . file_get_contents($log)); }
    assertHttp(str_contains(request('GET', '/cart', [], true)['body'], 'В корзине пока пусто'), 'fresh session has empty cart');
    assertHttp(request('GET', '/order/success', [], true)['status'] === 404, 'confirmation private to session');
    $csrf = token();
    $csv = fopen($root . '/dataset/pr11/route_tests.csv', 'r');
    fgetcsv($csv, 0, ';', '"', '');
    while (($row = fgetcsv($csv, 0, ';', '"', '')) !== false) {
        [$method, $path, $status] = $row;
        $response = request($method, $path, ['product_id' => 1, 'csrf' => $csrf]);
        assertHttp($response['status'] === (int) $status, "$method $path = $status");
    }
    fclose($csv);
    $cart = request('GET', '/cart');
    assertHttp(str_contains($cart['body'], 'Товар добавлен в корзину'), 'flash visible after redirect');
    assertHttp(!str_contains(request('GET', '/cart')['body'], 'Товар добавлен в корзину'), 'flash consumed once');
    assertHttp(request('GET', '/cart/add')['status'] === 405, 'GET cannot add product');
    assertHttp(request('POST', '/cart/add', ['product_id' => 2])['status'] === 403, 'missing CSRF rejected');
    assertHttp(request('POST', '/cart/add', ['product_id' => 2, 'csrf' => 'bad'])['status'] === 403, 'invalid CSRF rejected');
    foreach (Json::read($root . '/dataset/pr11/security_cases.json') as $case) {
        if (isset($case['input'])) {
            $response = request('POST', '/checkout', ['csrf' => $csrf, 'name' => $case['input']]);
            assertHttp($response['status'] === 422 && str_contains($response['body'], e($case['input'])) && !str_contains($response['body'], $case['input']), 'HTML input escaped on form redisplay');
        } elseif (isset($case['qty'])) {
            assertHttp(request('POST', '/cart/update', ['csrf' => $csrf, 'product_id' => 1, 'qty' => $case['qty']])['status'] === 422, 'negative quantity rejected');
        } else {
            assertHttp(request('POST', '/cart/add', ['csrf' => $csrf, 'product_id' => $case['product_id']])['status'] === 404, 'unknown product rejected');
        }
    }
    foreach (['0', '1.5', 'abc', ['1']] as $qty) {
        assertHttp(request('POST', '/cart/update', ['csrf' => $csrf, 'product_id' => 1, 'qty' => $qty])['status'] === 422, 'malformed quantity rejected: ' . json_encode($qty));
    }
    foreach (['/catalog?category[]=1', '/catalog?sort[]=name', '/product?id[]=1'] as $path) {
        assertHttp(request('GET', $path)['status'] === 422, 'malformed query handled: ' . $path);
    }
    assertHttp(request('GET', '/product?id=99999')['status'] === 404, 'missing product page');
    assertHttp(request('GET', '/missing')['status'] === 404, 'dataset missing route');
    assertHttp(request('GET', '/about')['status'] === 200, 'about route');
    assertHttp(request('GET', '/checkout')['status'] === 200, 'checkout form');
    foreach (Json::read($root . '/dataset/pr10/order_cases.json') as $case) {
        $response = request('POST', '/checkout', array_merge($case['input'], ['csrf' => $csrf]));
        assertHttp($response['status'] === ($case['valid'] ? 302 : 422), 'HTTP order case: ' . $case['case']);
    }
    $confirmation = request('GET', '/order/success');
    assertHttp($confirmation['status'] === 200 && str_contains($confirmation['body'], '570'), 'confirmation shows server total');
    assertHttp(!str_contains($confirmation['body'], 'ivan@example.test'), 'confirmation omits contact');
    request('GET', '/order/success');
    assertHttp(count(Json::read($temporary . '/orders.json')) === 1, 'confirmation refresh does not duplicate order');
    assertHttp(str_contains(request('GET', '/cart')['body'], 'В корзине пока пусто'), 'cart cleared after successful order');
    assertHttp(request('POST', '/checkout', ['csrf' => $csrf, 'name' => 'Иван', 'email' => 'ivan@example.test', 'delivery' => 'pickup'])['status'] === 422, 'empty cart cannot create order');
    assertHttp(request('GET', '/checkout')['status'] === 302, 'empty checkout redirects to cart');
    assertHttp(request('GET', '/dataset/pr08/products.json')['status'] === 404, 'dataset outside public');
    assertHttp(request('GET', '/var/orders.json')['status'] === 404, 'orders outside public');
    assertHttp(request('GET', '/styles.css')['status'] === 200 && request('GET', '/shop-logo.svg')['status'] === 200, 'public assets available');
    assertHttp(!preg_match('/PHP (Warning|Notice|Fatal|Deprecated)/', file_get_contents($log)), 'no PHP warnings in HTTP scenarios');
    echo "HTTP checks: $checks\n";
} finally {
    proc_terminate($process);
    proc_close($process);
    putenv('SHOP_ORDERS_PATH');
    foreach (glob($temporary . '/*') as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($temporary);
}
