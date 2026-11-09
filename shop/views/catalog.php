<p class="eyebrow">Товары для обучения</p>
<h1>Каталог</h1>
<form class="filters" method="get" action="/catalog">
    <label>Категория<select name="category"><option value="">Все категории</option><?php foreach ($categories as $item): ?><option value="<?= e($item->id) ?>" <?= $category === $item->id ? 'selected' : '' ?>><?= e($item->name) ?></option><?php endforeach ?></select></label>
    <label>Сортировка<select name="sort"><option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>По названию</option><option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Сначала дешевле</option></select></label>
    <button>Показать</button><a href="/catalog">Сбросить</a>
</form>
<p class="muted">Найдено товаров: <?= e(count($items)) ?></p>
<?php if ($items === []): ?><section class="card"><h2>В этой категории товаров нет</h2><p>Выберите другую категорию или сбросьте фильтр.</p></section><?php endif ?>
<div class="grid">
<?php foreach ($items as $product): ?>
    <article class="card"><p class="eyebrow"><?= e($product->category->name) ?></p><h2><a href="/product?id=<?= e($product->id) ?>"><?= e($product->name) ?></a></h2><p class="price"><?= e(money($product->price)) ?></p><p class="muted"><?= $product->stock === null ? 'Учебный ассортимент' : ($product->stock > 0 ? 'На складе: ' . e($product->stock) : 'Нет в наличии') ?></p>
    <?php if (isset($csrf)): ?><form action="/cart/add" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="product_id" value="<?= e($product->id) ?>"><button <?= $product->stock === 0 ? 'disabled' : '' ?>>В корзину</button></form><?php endif ?></article>
<?php endforeach ?>
</div>
