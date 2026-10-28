<?php

declare(strict_types=1);

// Ручное подключение классов (см. README: почему при росте проекта это неудобно)
require_once __DIR__ . '/../src/Model/Product.php';
require_once __DIR__ . '/../src/Model/Category.php';
require_once __DIR__ . '/../src/Service/CatalogService.php';
require_once __DIR__ . '/../src/Service/RequirementsChecker.php';

$isDev = PHP_SAPI === 'cli-server';
ini_set('display_errors', $isDev ? '1' : '0');
error_reporting(E_ALL);

/** Экранирование для HTML. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Форматирование цены — задача представления, поэтому не в Product. */
function money(float $value): string
{
    return number_format($value, 2, ',', ' ') . ' ₽';
}

const SEGMENT_LABELS = ['budget' => 'Бюджетный', 'standard' => 'Стандарт', 'premium' => 'Премиум'];
const DISCOUNT_PERCENT = 10;

// ---------- Данные ----------
$dataDir = __DIR__ . '/../data';
$catalog = new CatalogService($dataDir . '/products.json', $dataDir . '/categories.json');
$requirements = RequirementsChecker::check($dataDir . '/class_requirements.csv');

// ---------- Обработка фильтра (GET) — отдельно от моделей ----------
$rawCategory = $_GET['category'] ?? '';
$categoryId = is_string($rawCategory) ? filter_var(trim($rawCategory), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$selectedCategory = $categoryId !== false ? $catalog->findCategory($categoryId) : null;
$filterError = ($rawCategory !== '' && $selectedCategory === null) ? 'Такой категории нет — показаны все товары.' : '';
$onlyAvailable = ($_GET['available'] ?? '') === '1';

$products = $catalog->filterByCategory($selectedCategory?->id());
if ($onlyAvailable) {
    $products = $catalog->onlyAvailable($products);
}

$allProducts = $catalog->products();
$stats = [
    'shown' => count($products),
    'total' => count($allProducts),
    'available' => count($catalog->onlyAvailable($allProducts)),
    'average' => $catalog->averagePrice($products),
    'sum' => $catalog->totalPrice($products),
];
$countByCategory = $catalog->countByCategory();

// ---------- Демонстрация некорректного конструктора ----------
$constructorChecks = [
    'new Product(0, "Ноль", 100, 1)' => static fn () => new Product(0, 'Ноль', 100, 1),
    'new Product(13, "   ", 500, 2)' => static fn () => new Product(13, '   ', 500, 2),
    'new Product(14, "Минус", -10, 3)' => static fn () => new Product(14, 'Минус', -10, 3),
    'new Category(4, "")' => static fn () => new Category(4, ''),
    'new Product(15, "Клавиатура", 3500, 2)' => static fn () => new Product(15, 'Клавиатура', 3500, 2),
];
$constructorResults = [];
foreach ($constructorChecks as $code => $factory) {
    try {
        $object = $factory();
        $constructorResults[] = ['code' => $code, 'ok' => true, 'message' => 'Объект создан: ' . $object::class . ' #' . $object->id()];
    } catch (InvalidArgumentException $exception) {
        $constructorResults[] = ['code' => $code, 'ok' => false, 'message' => $exception->getMessage()];
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Каталог товаров — ПР-05</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <div class="container header__inner">
      <span class="header__title">Каталог товаров</span>
      <nav class="nav" aria-label="Разделы">
        <a href="#catalog">Каталог</a>
        <a href="#model">Модель</a>
        <a href="#checks">Проверки</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <?php foreach ($catalog->warnings() as $warning): ?>
      <div class="alert alert--error" role="alert"><?= e($warning) ?></div>
    <?php endforeach; ?>

    <section id="catalog" aria-labelledby="catalog-title">
      <div class="toolbar">
        <h1 id="catalog-title"><?= e($selectedCategory?->name() ?? 'Все товары') ?></h1>

        <form class="filter" method="get" action="#catalog">
          <label for="category">Категория</label>
          <select id="category" name="category">
            <option value="">Все категории</option>
            <?php foreach ($catalog->categories() as $category): ?>
              <option value="<?= e($category->id()) ?>" <?= $selectedCategory?->id() === $category->id() ? 'selected' : '' ?>>
                <?= e($category->name()) ?> (<?= e($countByCategory[$category->id()] ?? 0) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <label class="checkbox"><input type="checkbox" name="available" value="1" <?= $onlyAvailable ? 'checked' : '' ?>> только в продаже</label>
          <button type="submit" class="button">Применить</button>
          <a href="./#catalog">Сбросить</a>
        </form>
      </div>

      <?php if ($filterError !== ''): ?>
        <p class="alert alert--warning"><?= e($filterError) ?></p>
      <?php endif; ?>

      <ul class="stats" aria-label="Сводка по выборке">
        <li><span>Показано</span><strong><?= e($stats['shown']) ?> из <?= e($stats['total']) ?></strong></li>
        <li><span>Средняя цена</span><strong><?= e(money($stats['average'])) ?></strong></li>
        <li><span>Сумма выборки</span><strong><?= e(money($stats['sum'])) ?></strong></li>
        <li><span>В продаже всего</span><strong><?= e($stats['available']) ?></strong></li>
      </ul>

      <?php if ($products === []): ?>
        <p class="empty">По выбранным условиям товаров нет.</p>
      <?php else: ?>
        <div class="grid">
          <?php foreach ($products as $product): ?>
            <?php $category = $catalog->categoryOf($product); ?>
            <article class="product<?= $product->isAvailable() ? '' : ' product--off' ?>" id="product-<?= e($product->id()) ?>">
              <div class="product__top">
                <span class="badge"><?= e($category?->name() ?? 'Без категории') ?></span>
                <span class="segment segment--<?= e($product->priceSegment()) ?>"><?= e(SEGMENT_LABELS[$product->priceSegment()]) ?></span>
              </div>
              <h2 class="product__title"><?= e($product->name()) ?></h2>
              <p class="product__price"><?= e(money($product->price())) ?></p>
              <p class="product__discount">со скидкой <?= DISCOUNT_PERCENT ?>%: <?= e(money($product->priceWithDiscount(DISCOUNT_PERCENT))) ?></p>
              <p class="product__status"><?= $product->isAvailable() ? 'В продаже' : 'Нет в продаже' ?></p>
              <small class="product__id">id <?= e($product->id()) ?> · categoryId <?= e($product->categoryId()) ?></small>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section id="model" class="card" aria-labelledby="model-title">
      <h2 id="model-title">Соответствие class_requirements.csv</h2>
      <p class="hint">Проверка через Reflection: есть ли у класса свойство и совпадает ли объявленный тип.</p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Класс</th><th>Свойство</th><th>Тип по требованиям</th><th>Тип в коде</th><th>Обязательное</th><th>Итог</th></tr></thead>
          <tbody>
            <?php foreach ($requirements as $req): ?>
              <tr>
                <td><code><?= e($req['class']) ?></code></td>
                <td><code><?= e($req['property']) ?></code></td>
                <td><?= e($req['type']) ?></td>
                <td><?= e($req['actualType'] ?: '—') ?></td>
                <td><?= $req['required'] ? 'да' : 'нет' ?></td>
                <td><?= $req['ok'] ? '<span class="ok">✔</span>' : '<span class="fail">✘</span>' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="checks" class="card" aria-labelledby="checks-title">
      <h2 id="checks-title">Проверка некорректного конструктора</h2>
      <p class="hint">Конструктор не даёт создать объект в неверном состоянии и выбрасывает <code>InvalidArgumentException</code>.</p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Вызов</th><th>Результат</th></tr></thead>
          <tbody>
            <?php foreach ($constructorResults as $result): ?>
              <tr>
                <td><code><?= e($result['code']) ?></code></td>
                <td class="<?= $result['ok'] ? 'ok' : 'fail' ?>"><?= e($result['message']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">Практическая работа №5 · ТРПО · данные: products.json, categories.json, class_requirements.csv</div>
  </footer>
</body>
</html>
