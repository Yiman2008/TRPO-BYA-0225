<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
$employees = loadEmployees(__DIR__ . '/../data/employees.csv');
?>
<!doctype html>
<html lang="ru">
<head><meta charset="UTF-8"><title><?= e(APP_NAME) ?></title></head>
<body>
<main>
  <p>Версия <?= e(APP_VERSION) ?>, сегодня <?= e(date('d.m.Y H:i')) ?></p>
  <table>
    <tr><th>ID</th><th>Имя</th><th>Отдел</th><th>Стаж</th><th>Email</th></tr>
    <?php foreach ($employees as $employee): ?>
      <tr>
        <td><?= e($employee['id']) ?></td><td><?= e($employee['name']) ?></td>
        <td><?= e($employee['department']) ?></td><td><?= e($employee['experience']) ?></td>
        <td><?= e($employee['email']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
</body>
</html>
