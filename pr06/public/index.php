<?php

declare(strict_types=1);

/*
 * Единая точка входа. Все переходы идут через index.php:
 *   GET  index.php?page=notes|user|scenarios  — страницы (по умолчанию notes)
 *   POST index.php?action=add|toggle|set_user|clear_user|scenario — действия, после них redirect
 * Неизвестные page/action → страница 404.
 */

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/notes.php';
require_once __DIR__ . '/../src/users.php';
require_once __DIR__ . '/../src/session.php';

$isDev = PHP_SAPI === 'cli-server';
ini_set('display_errors', $isDev ? '1' : '0');
error_reporting(E_ALL);

const NOTES_FILE = __DIR__ . '/../data/notes.json';
const USERS_FILE = __DIR__ . '/../data/users.json';
const SCENARIOS_FILE = __DIR__ . '/../data/session_scenarios.json';
const PAGES = ['notes', 'user', 'scenarios'];

// session_start() — до любого вывода HTML
startSession();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = param($_GET, 'page') ?: 'notes';
$action = param($_GET, 'action');

try {
    $users = loadUsers(USERS_FILE);
    $user = currentUser($users);

    // ---------- Обработчики POST ----------
    if ($method === 'POST') {
        switch ($action) {
            case 'add':
                $title = (string) preg_replace('/\s+/u', ' ', param($_POST, 'title'));
                $error = validateNoteTitle($title);
                if ($error !== null) {
                    flash('error', $error);
                    $_SESSION['old_title'] = $title; // вернуть введённое значение в форму
                    redirect('notes');
                }
                $notes = loadNotes(NOTES_FILE);
                $notes[] = ['id' => nextNoteId($notes), 'title' => $title, 'done' => false, 'author' => $user['name'] ?? DEFAULT_USER_NAME];
                saveNotes(NOTES_FILE, $notes);
                flash('success', 'Заметка добавлена и сохранена в notes.json.');
                redirect('notes');

            case 'toggle':
                $id = filter_var(param($_POST, 'id'), FILTER_VALIDATE_INT);
                $notes = loadNotes(NOTES_FILE);
                if ($id === false || !toggleNote($notes, $id)) {
                    flash('error', 'Заметка не найдена.');
                    redirect('notes');
                }
                saveNotes(NOTES_FILE, $notes);
                flash('success', "Статус заметки #{$id} изменён.");
                redirect('notes');

            case 'set_user':
                $id = filter_var(param($_POST, 'user_id'), FILTER_VALIDATE_INT);
                $found = array_filter($users, static fn (array $u): bool => $u['id'] === $id);
                if ($found === []) {
                    flash('error', 'Выберите пользователя из списка.');
                    redirect('user');
                }
                setCurrentUser((int) $id);
                flash('success', 'Пользователь сохранён в сессии: ' . reset($found)['name'] . '.');
                redirect('user');

            case 'clear_user':
                clearCurrentUser();
                flash('success', 'Выбор пользователя очищен.');
                redirect(in_array(param($_POST, 'back'), PAGES, true) ? param($_POST, 'back') : 'notes');

            case 'scenario':
                $name = param($_POST, 'scenario');
                $scenarios = loadJson(SCENARIOS_FILE);
                $scenario = array_values(array_filter($scenarios, static fn ($s): bool => is_array($s) && ($s['scenario'] ?? '') === $name))[0] ?? null;
                if ($scenario === null) {
                    flash('error', 'Неизвестный сценарий.');
                    redirect('scenarios');
                }
                if ($name === 'new_session') {
                    // Новая сессия: старые данные удаляются, выдаётся новый идентификатор
                    $_SESSION = [];
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = null;
                } elseif ($name === 'set_name') {
                    $match = array_values(array_filter($users, static fn (array $u): bool => $u['name'] === ($scenario['value'] ?? '')));
                    if ($match !== []) {
                        setCurrentUser($match[0]['id']);
                    }
                } elseif ($name === 'clear') {
                    clearCurrentUser();
                }
                $_SESSION['last_scenario'] = $name;
                flash('success', "Сценарий {$name} выполнен.");
                redirect('scenarios');

            default:
                render('404', ['title' => 'Не найдено', 'user' => $user, 'flashes' => [], 'requested' => "POST action={$action}"], 404);
                exit;
        }
    }

    // ---------- Подготовка данных для GET-страниц ----------
    $common = ['user' => $user, 'flashes' => takeFlashes(), 'page' => $page];

    switch ($page) {
        case 'notes':
            $notes = loadNotes(NOTES_FILE);
            $oldTitle = $_SESSION['old_title'] ?? '';
            unset($_SESSION['old_title']);
            render('list', $common + [
                'title' => 'Заметки',
                'notes' => $notes,
                'stats' => notesStats($notes),
                'oldTitle' => $oldTitle,
                'titleMax' => NOTE_TITLE_MAX,
            ]);
            break;

        case 'user':
            render('user', $common + [
                'title' => 'Пользователь',
                'users' => $users,
                'sessionId' => session_id(),
            ]);
            break;

        case 'scenarios':
            $scenarios = loadJson(SCENARIOS_FILE);
            $state = $user === null ? 'default' : 'stored';
            $lastScenario = $_SESSION['last_scenario'] ?? null;
            $rows = [];
            foreach ($scenarios as $scenario) {
                if (!is_array($scenario)) {
                    continue;
                }
                $expected = (string) ($scenario['expected'] ?? '');
                // guest_or_default и default означают одно состояние: пользователь не выбран
                $matches = $expected === 'stored' ? $state === 'stored' : $state === 'default';
                $rows[] = [
                    'scenario' => (string) ($scenario['scenario'] ?? ''),
                    'value' => isset($scenario['value']) ? (string) $scenario['value'] : '',
                    'expected' => $expected,
                    'isLast' => $lastScenario === ($scenario['scenario'] ?? null),
                    'matches' => $matches,
                ];
            }
            render('scenarios', $common + [
                'title' => 'Сценарии сессии',
                'rows' => $rows,
                'state' => $state,
                'sessionId' => session_id(),
                'lastScenario' => $lastScenario,
            ]);
            break;

        default:
            render('404', $common + ['title' => 'Не найдено', 'requested' => "page={$page}"], 404);
    }
} catch (StorageException $exception) {
    // Отсутствующий или повреждённый JSON — понятная страница вместо fatal error
    error_log('[pr06] ' . $exception->getMessage());
    render('error', [
        'title' => 'Ошибка данных',
        'user' => null,
        'flashes' => [],
        'message' => $exception->getMessage(),
    ], 500);
}
