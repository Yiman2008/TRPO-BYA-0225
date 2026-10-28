<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/functions.php';

// Ошибки показываем только при локальной разработке (встроенный сервер php -S)
$isDev = PHP_SAPI === 'cli-server';
ini_set('display_errors', $isDev ? '1' : '0');
error_reporting(E_ALL);

// ---------- Данные ----------
$dataDir = __DIR__ . '/../data';
$employees = loadEmployees($dataDir . '/employees.csv');
$formCases = loadFormCases($dataDir . '/form_cases.json');
$departments = departmentsOf($employees);

// Переменные разных типов, которые используются в интерфейсе
$appVersion = APP_VERSION;                       // string
$today = date('d.m.Y H:i');                      // string
$employeesCount = count($employees);             // int
$avgExperience = averageExperience($employees);  // float
$hasData = $employeesCount > 0;                  // bool

// ---------- Фильтр (GET) ----------
$rawFilter = $_GET['department'] ?? '';
$departmentFilter = is_string($rawFilter) ? trim($rawFilter) : '';
if ($departmentFilter !== '' && !in_array($departmentFilter, $departments, true)) {
    $departmentFilter = ''; // неизвестное значение — показываем все записи
}
$visibleEmployees = filterByDepartment($employees, $departmentFilter);

// ---------- Форма (POST) ----------
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$input = normalizeInput($isPost ? $_POST : []);
$errors = $isPost ? validateInput($input, $departments) : [];
$application = null;

if ($isPost && $errors === []) {
    $application = buildApplication($input, $employees);
    $input = normalizeInput([]); // после успешной отправки очищаем форму
}

// ---------- Сценарии из form_cases.json ----------
$caseResults = array_map(
    static fn (array $case): array => runFormCase($case, $departments),
    $formCases
);
$casesPassed = count(array_filter($caseResults, static fn (array $r): bool => $r['passed']));

/** Класс ошибки для поля формы. */
function fieldClass(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'field field--error' : 'field';
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?> — ПР-04</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="header">
    <div class="container header__inner">
      <span class="header__title"><?= e(APP_NAME) ?></span>
      <nav class="nav" aria-label="Разделы">
        <a href="#employees">Сотрудники</a>
        <a href="#application">Заявка</a>
        <a href="#cases">Проверка</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <p class="meta">
      Версия приложения <strong><?= e($appVersion) ?></strong> ·
      страница сформирована сервером <?= e($today) ?> ·
      PHP <?= e(PHP_VERSION) ?>
      <?php if ($isDev): ?><span class="tag">режим разработки</span><?php endif; ?>
    </p>

    <?php if ($application !== null): ?>
      <div class="alert alert--success" role="status">
        <strong>Заявка принята.</strong>
        Сотрудник <?= e($application['name']) ?> (<?= e($application['department']) ?>,
        стаж <?= e(yearsLabel($application['experience'])) ?>) добавлен в таблицу под номером <?= e($application['id']) ?>.
        <?php if ($application['comment'] !== ''): ?>
          Комментарий: «<?= e($application['comment']) ?>».
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <section id="employees" class="card" aria-labelledby="employees-title">
      <div class="card__head">
        <h1 id="employees-title">Сотрудники</h1>
        <form class="filter" method="get" action="#employees">
          <label for="department-filter">Отдел</label>
          <select id="department-filter" name="department">
            <option value="">Все отделы</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= e($department) ?>" <?= $department === $departmentFilter ? 'selected' : '' ?>>
                <?= e($department) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="button button--light">Показать</button>
          <?php if ($departmentFilter !== ''): ?>
            <a href="?#employees">Сбросить</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (!$hasData): ?>
        <p class="empty">Файл employees.csv не найден или пуст.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>ID</th><th>Имя</th><th>Отдел</th><th>Стаж</th><th>Email</th></tr>
            </thead>
            <tbody>
              <?php foreach ($visibleEmployees as $employee): ?>
                <tr>
                  <td><?= e($employee['id']) ?></td>
                  <td><?= e($employee['name']) ?></td>
                  <td><?= e($employee['department']) ?></td>
                  <td><?= e(yearsLabel($employee['experience'])) ?></td>
                  <td><a href="mailto:<?= e($employee['email']) ?>"><?= e($employee['email']) ?></a></td>
                </tr>
              <?php endforeach; ?>
              <?php if ($application !== null && ($departmentFilter === '' || $departmentFilter === $application['department'])): ?>
                <tr class="row-new">
                  <td><?= e($application['id']) ?></td>
                  <td><?= e($application['name']) ?> <span class="tag">новая заявка</span></td>
                  <td><?= e($application['department']) ?></td>
                  <td><?= e(yearsLabel($application['experience'])) ?></td>
                  <td><?= e($application['email']) ?></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="hint">
          Показано: <?= e(count($visibleEmployees)) ?> из <?= e($employeesCount) ?>.
          Средний стаж по компании: <?= e(number_format($avgExperience, 1, ',', ' ')) ?> г.
        </p>
      <?php endif; ?>
    </section>

    <section id="application" class="card" aria-labelledby="application-title">
      <h2 id="application-title">Заявка нового сотрудника</h2>

      <?php if ($errors !== []): ?>
        <div class="alert alert--error" role="alert">
          <strong>Исправьте ошибки в форме:</strong>
          <ul>
            <?php foreach ($errors as $message): ?>
              <li><?= e($message) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="#application" class="form" novalidate>
        <div class="<?= fieldClass($errors, 'name') ?>">
          <label for="name">Имя и фамилия</label>
          <input id="name" name="name" type="text" maxlength="100" value="<?= e($input['name']) ?>"
                 aria-describedby="name-msg" <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
          <small id="name-msg"><?= e($errors['name'] ?? sprintf('От %d до %d символов', NAME_MIN, NAME_MAX)) ?></small>
        </div>

        <div class="<?= fieldClass($errors, 'email') ?>">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?= e($input['email']) ?>"
                 aria-describedby="email-msg" <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
          <small id="email-msg"><?= e($errors['email'] ?? 'Например, name@example.test') ?></small>
        </div>

        <div class="<?= fieldClass($errors, 'department') ?>">
          <label for="department">Отдел</label>
          <select id="department" name="department" aria-describedby="department-msg">
            <option value="">— выберите —</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?= e($department) ?>" <?= $department === $input['department'] ? 'selected' : '' ?>>
                <?= e($department) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small id="department-msg"><?= e($errors['department'] ?? 'Отделы взяты из employees.csv') ?></small>
        </div>

        <div class="<?= fieldClass($errors, 'experience') ?>">
          <label for="experience">Стаж, лет</label>
          <input id="experience" name="experience" type="number" min="<?= EXPERIENCE_MIN ?>" max="<?= EXPERIENCE_MAX ?>"
                 value="<?= e($input['experience']) ?>" aria-describedby="experience-msg">
          <small id="experience-msg"><?= e($errors['experience'] ?? sprintf('Целое число от %d до %d', EXPERIENCE_MIN, EXPERIENCE_MAX)) ?></small>
        </div>

        <div class="<?= fieldClass($errors, 'comment') ?> field--wide">
          <label for="comment">Комментарий <span class="optional">(необязательно)</span></label>
          <textarea id="comment" name="comment" rows="3" aria-describedby="comment-msg"><?= e($input['comment']) ?></textarea>
          <small id="comment-msg"><?= e($errors['comment'] ?? sprintf('До %d символов', COMMENT_MAX)) ?></small>
        </div>

        <div class="form__actions">
          <button type="submit" class="button">Отправить заявку</button>
        </div>
      </form>
    </section>

    <section id="cases" class="card" aria-labelledby="cases-title">
      <h2 id="cases-title">Проверка сценариев из form_cases.json</h2>
      <p class="hint">
        Каждый сценарий проходит через те же <code>normalizeInput()</code> и <code>validateInput()</code>, что и форма.
        Пройдено: <strong><?= e($casesPassed) ?> из <?= e(count($caseResults)) ?></strong>.
      </p>
      <?php if ($caseResults === []): ?>
        <p class="empty">Файл form_cases.json не найден или повреждён.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Сценарий</th><th>name</th><th>experience</th><th>Ожидается</th><th>Получено</th><th>Ошибки</th><th>Итог</th></tr>
            </thead>
            <tbody>
              <?php foreach ($caseResults as $result): ?>
                <tr>
                  <td><code><?= e($result['case']) ?></code></td>
                  <td><?= $result['input']['name'] === '' ? '<span class="optional">пусто</span>' : e($result['input']['name']) ?></td>
                  <td><?= e($result['input']['experience']) ?></td>
                  <td><?= $result['expected'] ? 'валидно' : 'ошибка' ?></td>
                  <td><?= $result['actual'] ? 'валидно' : 'ошибка' ?></td>
                  <td><?= e(implode(' ', $result['errors'])) ?></td>
                  <td><?= $result['passed'] ? '<span class="ok">✔ совпало</span>' : '<span class="fail">✘ не совпало</span>' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="footer">
    <div class="container">Практическая работа №4 · ТРПО · данные: data/employees.csv, data/form_cases.json</div>
  </footer>
</body>
</html>
