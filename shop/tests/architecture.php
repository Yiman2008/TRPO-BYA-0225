<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
$root = dirname(__DIR__);
$read = static fn(string $path): string => file_get_contents($root . '/' . $path);
$models = glob($root . '/src/Model/*.php');
$views = glob($root . '/views/*.php');
$modelText = implode("\n", array_map('file_get_contents', $models));
$viewText = implode("\n", array_map('file_get_contents', $views));
$classes = [];
foreach (['Core', 'Controller', 'Model', 'Repository', 'Service'] as $layer) {
    foreach (glob($root . '/src/' . $layer . '/*.php') as $file) {
        $classes[] = str_contains(file_get_contents($file), 'namespace App\\' . $layer . ';');
    }
}
$checks = [
    'Single entry point' => str_contains($read('public/index.php'), 'Application') && !str_contains($read('public/index.php'), '<html'),
    'Router without HTML' => !preg_match('/<html|<div|echo\s|print\s/', $read('src/Core/Router.php')),
    'Controllers choose views' => str_contains($read('src/Controller/CatalogController.php'), "->render('catalog'"),
    'Models without HTTP globals' => !preg_match('/\$_(?:GET|POST|SESSION|SERVER|REQUEST)/', $modelText),
    'Views without data access' => !preg_match('/file_get_contents|fopen|json_decode|new ProductRepository/', $viewText),
    'Namespaces' => !in_array(false, $classes, true),
    'Autoload' => class_exists(App\Model\Product::class) && class_exists(App\Core\Router::class),
    '404 handler' => str_contains($read('src/Core/Router.php'), 'new HttpException(404')
        && str_contains($read('src/Controller/ErrorController.php'), '$this->show(404'),
];
$file = fopen($root . '/dataset/pr11/architecture_checklist.csv', 'r');
fgetcsv($file, 0, ';', '"', '');
$count = 0;
while (($row = fgetcsv($file, 0, ';', '"', '')) !== false) {
    [$item, $required] = $row;
    if ($required === 'yes' && !($checks[$item] ?? false)) { throw new RuntimeException('FAIL: ' . $item); }
    echo 'PASS: ' . $item . PHP_EOL;
    $count++;
}
fclose($file);
echo "Architecture checks: $count\n";
