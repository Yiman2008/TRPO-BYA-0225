<?php

declare(strict_types=1);

const APP_NAME = 'Заявки сотрудников';
const APP_VERSION = '1.0.0';

const NAME_MIN = 2;
const NAME_MAX = 60;
const EXPERIENCE_MIN = 0;
const EXPERIENCE_MAX = 50;
const COMMENT_MAX = 200;

/** Поля формы заявки. */
const FORM_FIELDS = ['name', 'email', 'department', 'experience', 'comment'];

/**
 * Безопасный вывод любого значения в HTML.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Преобразует одну строку employees.csv в удобную структуру.
 *
 * @param array<string, string> $row строка CSV, ключи — заголовки (id, name, department, experience, email)
 * @return array{id:int, name:string, department:string, experience:int, email:string}
 */
function mapEmployee(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => trim($row['name'] ?? ''),
        'department' => trim($row['department'] ?? ''),
        'experience' => (int) ($row['experience'] ?? 0),
        'email' => trim($row['email'] ?? ''),
    ];
}

/**
 * Читает employees.csv через fgetcsv и возвращает массив сотрудников.
 * При отсутствии файла возвращает пустой массив — интерфейс покажет пустое состояние.
 *
 * @return list<array{id:int, name:string, department:string, experience:int, email:string}>
 */
function loadEmployees(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $header = fgetcsv($handle, null, ';', '"', '\\');
    if ($header === false || $header === [null]) {
        fclose($handle);
        return [];
    }
    // Файл сохранён с BOM: убираем его из первого заголовка, иначе ключ будет "\u{FEFF}id"
    $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);
    $header = array_map('trim', $header);

    $employees = [];
    while (($line = fgetcsv($handle, null, ';', '"', '\\')) !== false) {
        if ($line === [null] || count($line) !== count($header)) {
            continue; // пустая или повреждённая строка
        }
        $employees[] = mapEmployee(array_combine($header, $line));
    }
    fclose($handle);

    return $employees;
}

/**
 * Читает сценарии проверки формы из form_cases.json.
 *
 * @return list<array<string, mixed>>
 */
function loadFormCases(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    try {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return [];
    }

    return is_array($data) ? array_values($data) : [];
}

/**
 * Список отделов из dataset (для select и фильтра).
 *
 * @param list<array<string, mixed>> $employees
 * @return list<string>
 */
function departmentsOf(array $employees): array
{
    $departments = array_unique(array_column($employees, 'department'));
    sort($departments, SORT_STRING);

    return array_values($departments);
}

/**
 * Фильтр по отделу. Пустая строка — все записи.
 *
 * @param list<array<string, mixed>> $employees
 * @return list<array<string, mixed>>
 */
function filterByDepartment(array $employees, string $department): array
{
    if ($department === '') {
        return $employees;
    }

    return array_values(array_filter(
        $employees,
        static fn (array $employee): bool => $employee['department'] === $department
    ));
}

/**
 * Средний стаж сотрудников.
 *
 * @param list<array<string, mixed>> $employees
 */
function averageExperience(array $employees): float
{
    if ($employees === []) {
        return 0.0;
    }

    return array_sum(array_column($employees, 'experience')) / count($employees);
}

/**
 * Достаёт поля формы из массива запроса и нормализует их через trim.
 * Любое поле может отсутствовать или прийти массивом — тогда считаем его пустым.
 *
 * @param array<string, mixed> $source обычно $_POST
 * @return array<string, string>
 */
function normalizeInput(array $source): array
{
    $result = [];
    foreach (FORM_FIELDS as $field) {
        $value = $source[$field] ?? '';
        $result[$field] = is_scalar($value) ? trim((string) $value) : '';
    }
    // Несколько пробелов внутри имени схлопываем в один
    $result['name'] = (string) preg_replace('/\s+/u', ' ', $result['name']);

    return $result;
}

/**
 * Серверная валидация заявки. Возвращает массив ошибок «поле => сообщение».
 *
 * @param array<string, string> $input нормализованные данные
 * @param list<string> $allowedDepartments
 * @return array<string, string>
 */
function validateInput(array $input, array $allowedDepartments): array
{
    $errors = [];

    $nameLength = mb_strlen($input['name']);
    if ($input['name'] === '') {
        $errors['name'] = 'Укажите имя сотрудника.';
    } elseif ($nameLength < NAME_MIN || $nameLength > NAME_MAX) {
        $errors['name'] = sprintf('Имя должно содержать от %d до %d символов.', NAME_MIN, NAME_MAX);
    }

    if ($input['email'] === '') {
        $errors['email'] = 'Укажите email.';
    } elseif (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Некорректный email, пример: name@example.test.';
    }

    if ($input['department'] === '') {
        $errors['department'] = 'Выберите отдел.';
    } elseif (!in_array($input['department'], $allowedDepartments, true)) {
        $errors['department'] = 'Такого отдела нет в списке.';
    }

    if ($input['experience'] === '') {
        $errors['experience'] = 'Укажите стаж в годах.';
    } elseif (filter_var($input['experience'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => EXPERIENCE_MIN, 'max_range' => EXPERIENCE_MAX],
    ]) === false) {
        $errors['experience'] = sprintf('Стаж — целое число от %d до %d.', EXPERIENCE_MIN, EXPERIENCE_MAX);
    }

    if (mb_strlen($input['comment']) > COMMENT_MAX) {
        $errors['comment'] = sprintf('Комментарий — не длиннее %d символов.', COMMENT_MAX);
    }

    return $errors;
}

/**
 * Превращает валидную заявку в запись того же вида, что и строки employees.csv.
 *
 * @param array<string, string> $input
 * @param list<array<string, mixed>> $employees
 * @return array<string, mixed>
 */
function buildApplication(array $input, array $employees): array
{
    $nextId = ($employees === [] ? 0 : max(array_column($employees, 'id'))) + 1;

    return [
        'id' => $nextId,
        'name' => $input['name'],
        'department' => $input['department'],
        'experience' => (int) $input['experience'],
        'email' => mb_strtolower($input['email']),
        'comment' => $input['comment'],
    ];
}

/**
 * Прогоняет сценарий из form_cases.json через ту же валидацию, что и форма.
 * В сценарии заданы только name и experience — остальные поля заполняются корректными значениями,
 * чтобы проверялось именно то, что описано в сценарии.
 *
 * @param array<string, mixed> $case
 * @param list<string> $departments
 * @return array{case:string, input:array<string,string>, expected:bool, actual:bool, errors:array<string,string>, passed:bool}
 */
function runFormCase(array $case, array $departments): array
{
    $source = [
        'name' => $case['name'] ?? '',
        'email' => 'case@example.test',
        'department' => $departments[0] ?? '',
        'experience' => $case['experience'] ?? '',
        'comment' => '',
    ];
    $input = normalizeInput($source);
    $errors = validateInput($input, $departments);
    $expected = (bool) ($case['valid'] ?? false);
    $actual = $errors === [];

    return [
        'case' => (string) ($case['case'] ?? 'без названия'),
        'input' => $input,
        'expected' => $expected,
        'actual' => $actual,
        'errors' => $errors,
        'passed' => $expected === $actual,
    ];
}

/**
 * Склонение слова «год».
 */
function yearsLabel(int $years): string
{
    $mod100 = abs($years) % 100;
    $mod10 = abs($years) % 10;
    if ($mod100 >= 11 && $mod100 <= 14) {
        return $years . ' лет';
    }

    return $years . match ($mod10) {
        1 => ' год',
        2, 3, 4 => ' года',
        default => ' лет',
    };
}
