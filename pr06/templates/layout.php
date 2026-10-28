<?php
/** @var string $title @var string $content @var array|null $user @var list<array> $flashes */
$currentPage = $page ?? '';
$links = ['notes' => 'Заметки', 'user' => 'Пользователь', 'scenarios' => 'Сценарии сессии'];
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> — Менеджер заметок</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <div class="container header__inner">
      <a class="header__title" href="index.php">Менеджер заметок</a>
      <nav class="nav" aria-label="Разделы">
        <?php foreach ($links as $key => $label): ?>
          <a href="index.php?page=<?= e($key) ?>" <?= $currentPage === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="session-box">
        <span>Пользователь: <strong><?= e($user['name'] ?? DEFAULT_USER_NAME) ?></strong></span>
        <?php if ($user !== null): ?>
          <form method="post" action="index.php?action=clear_user">
            <input type="hidden" name="back" value="<?= e($currentPage) ?>">
            <button type="submit" class="link-button">Сбросить</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
    <?php foreach ($flashes as $flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>

  <footer class="footer">
    <div class="container">Практическая работа №6 · ТРПО · данные: data/notes.json, users.json, session_scenarios.json</div>
  </footer>
</body>
</html>
