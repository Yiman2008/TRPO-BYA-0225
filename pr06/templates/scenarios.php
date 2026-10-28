<?php
/** @var list<array> $rows @var string $state @var string $sessionId @var string|null $lastScenario */
$stateLabels = ['default' => 'пользователь не выбран (Гость)', 'stored' => 'пользователь сохранён'];
?>
<section class="card" aria-labelledby="scenarios-title">
  <h1 id="scenarios-title">Сценарии из session_scenarios.json</h1>
  <p>Текущее состояние сессии: <strong><?= e($stateLabels[$state]) ?></strong> · id: <code><?= e(substr($sessionId, 0, 8)) ?>…</code></p>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Сценарий</th><th>Значение</th><th>Ожидается</th><th>Сейчас</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr<?= $row['isLast'] ? ' class="row-last"' : '' ?>>
            <td><code><?= e($row['scenario']) ?></code><?= $row['isLast'] ? ' <span class="tag">выполнен последним</span>' : '' ?></td>
            <td><?= e($row['value'] ?: '—') ?></td>
            <td><code><?= e($row['expected']) ?></code></td>
            <td><?= $row['matches'] ? '<span class="ok">соответствует</span>' : '<span class="muted">другое состояние</span>' ?></td>
            <td>
              <form method="post" action="index.php?action=scenario">
                <input type="hidden" name="scenario" value="<?= e($row['scenario']) ?>">
                <button type="submit" class="button button--small">Выполнить</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="hint">После «Выполнить» сценарий соответствует ожиданию. Последовательность new_session → set_name → clear повторяет три запроса подряд; обновите страницу — состояние сохраняется до конца сессии.</p>
</section>
