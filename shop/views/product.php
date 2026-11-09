<p><a href="/catalog">← Каталог</a></p>
<article class="hero"><p class="eyebrow"><?= e($product->category->name) ?></p><h1><?= e($product->name) ?></h1><p class="price"><?= e(money($product->price)) ?></p><p><?= $product->stock === null ? 'Учебный ассортимент' : ($product->stock > 0 ? 'На складе: ' . e($product->stock) : 'Нет в наличии') ?></p>
<?php if (isset($csrf)): ?><form action="/cart/add" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="product_id" value="<?= e($product->id) ?>"><label>Количество <input type="number" name="qty" value="1" min="1" max="999"></label><button <?= $product->stock === 0 ? 'disabled' : '' ?>>Добавить в корзину</button></form><?php endif ?>
</article>
