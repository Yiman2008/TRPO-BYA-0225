<p class="eyebrow">Ваш выбор</p><h1>Корзина</h1>
<?php if ($summary['lines'] === []): ?>
<section class="hero"><h2>В корзине пока пусто</h2><p>Откройте каталог и добавьте товары для учёбы.</p><a class="button" href="/catalog">Выбрать товары →</a></section>
<?php else: ?>
<div class="table-scroll"><table><thead><tr><th>Товар</th><th>Цена</th><th>Количество</th><th>Сумма</th><th>Действие</th></tr></thead><tbody>
<?php foreach ($summary['lines'] as $line): $product = $line['product']; ?>
<tr><td><a href="/product?id=<?= e($product->id) ?>"><?= e($product->name) ?></a></td><td><?= e(money($product->price)) ?></td><td><form action="/cart/update" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="product_id" value="<?= e($product->id) ?>"><input type="number" name="qty" min="1" max="999" value="<?= e($line['quantity']) ?>" aria-label="Количество <?= e($product->name) ?>"><button class="secondary">Обновить</button></form></td><td><?= e(money($line['subtotal'])) ?></td><td><form action="/cart/remove" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="product_id" value="<?= e($product->id) ?>"><button class="secondary">Удалить</button></form></td></tr>
<?php endforeach ?>
</tbody></table></div><p class="summary">Итого: <?= e(money($summary['total'])) ?></p><div class="actions"><a class="button" href="/checkout">Оформить заказ →</a><a href="/catalog">Продолжить покупки</a></div>
<?php endif ?>
