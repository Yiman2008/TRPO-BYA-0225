<?php /** @var string $message */ ?>
<section class="card card--center">
  <h1>Не удалось загрузить данные</h1>
  <p class="alert alert--error"><?= e($message) ?></p>
  <p>Проверьте, что файл существует в папке <code>data/</code> и содержит корректный JSON. Исходную версию можно вернуть командой <code>git checkout -- data/</code>.</p>
</section>
