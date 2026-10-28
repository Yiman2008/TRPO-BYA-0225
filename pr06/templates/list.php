<?php
/** @var list<array{id:int,title:string,done:bool,author:?string}> $notes @var array $stats @var string $oldTitle @var int $titleMax */
?>
<section class="card" aria-labelledby="notes-title">
  <div class="card__head">
    <h1 id="notes-title">Заметки</h1>
    <p class="stats">Всего: <strong><?= e($stats['total']) ?></strong> · выполнено: <strong><?= e($stats['done']) ?></strong> · осталось: <strong><?= e($stats['open']) ?></strong></p>
  </div>

  <?php if ($notes === []): ?>
    <p class="empty">Заметок пока нет — добавьте первую.</p>
  <?php else: ?>
    <ul class="notes">
      <?php foreach ($notes as $note): ?>
        <li class="note<?= $note['done'] ? ' note--done' : '' ?>" id="note-<?= e($note['id']) ?>">
          <form method="post" action="index.php?action=toggle">
            <input type="hidden" name="id" value="<?= e($note['id']) ?>">
            <button type="submit" class="check" aria-label="<?= $note['done'] ? 'Вернуть в работу' : 'Отметить выполненной' ?>"><?= $note['done'] ? '✔' : '' ?></button>
          </form>
          <div class="note__body">
            <span class="note__title"><?= e($note['title']) ?></span>
            <small class="note__meta">#<?= e($note['id']) ?><?= $note['author'] !== null ? ' · добавил(а) ' . e($note['author']) : '' ?></small>
          </div>
          <span class="status"><?= $note['done'] ? 'готово' : 'в работе' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section class="card" aria-labelledby="add-title">
  <h2 id="add-title">Новая заметка</h2>
  <form class="add-form" method="post" action="index.php?action=add">
    <label for="title">Текст заметки</label>
    <div class="add-form__row">
      <input id="title" name="title" type="text" maxlength="<?= e($titleMax) ?>" value="<?= e($oldTitle) ?>" placeholder="Например: подготовить отчёт" required>
      <button type="submit" class="button">Добавить</button>
    </div>
    <small class="hint">От 3 до <?= e($titleMax) ?> символов. После сохранения страница перенаправляется (PRG), поэтому F5 не добавит заметку повторно.</small>
  </form>
</section>
