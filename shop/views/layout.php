<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> — <?= e($storeName ?? 'Магазин') ?></title><link rel="stylesheet" href="/styles.css"></head>
<body>
<a class="skip" href="#main">К содержимому</a>
<header><div class="wrap header"><a class="brand" href="/"><img src="/shop-logo.svg" width="44" height="44" alt=""><?= e($storeName ?? 'Магазин') ?></a><nav aria-label="Главная навигация"><a href="/catalog">Каталог</a><a href="/about">О магазине</a><?php if (isset($cartCount)): ?><a href="/cart">Корзина <span class="badge"><?= e($cartCount) ?></span></a><?php endif ?></nav></div></header>
<main id="main" class="wrap">
<?php if (!empty($flash)): ?><p class="notice" role="status"><?= e($flash) ?></p><?php endif ?>
<?= $content ?>
</main>
<footer class="wrap">Учебный магазин · Технологии разработки программного обеспечения · PHP MVC</footer>
</body></html>
