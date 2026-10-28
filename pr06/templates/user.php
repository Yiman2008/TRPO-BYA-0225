<?php
/** @var list<array{id:int,name:string}> $users @var array|null $user @var string $sessionId */
?>
<section class="card" aria-labelledby="user-title">
  <h1 id="user-title">Пользователь сессии</h1>
  <p>Сейчас в сессии: <strong><?= e($user['name'] ?? DEFAULT_USER_NAME . ' (значение по умолчанию)') ?></strong></p>

  <?php if ($users === []): ?>
    <p class="empty">В users.json нет пользователей.</p>
  <?php else: ?>
    <form class="user-form" method="post" action="index.php?action=set_user">
      <fieldset>
        <legend>Выберите пользователя из users.json</legend>
        <?php foreach ($users as $item): ?>
          <label class="radio">
            <input type="radio" name="user_id" value="<?= e($item['id']) ?>" <?= ($user['id'] ?? null) === $item['id'] ? 'checked' : '' ?>>
            <?= e($item['name']) ?> <small>(id <?= e($item['id']) ?>)</small>
          </label>
        <?php endforeach; ?>
      </fieldset>
      <button type="submit" class="button">Сохранить в сессии</button>
    </form>
  <?php endif; ?>

  <p class="hint">Идентификатор сессии (cookie PHPSESSID): <code><?= e(substr($sessionId, 0, 8)) ?>…</code></p>
</section>
