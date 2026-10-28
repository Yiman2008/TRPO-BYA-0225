<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

const NOTE_TITLE_MIN = 3;
const NOTE_TITLE_MAX = 100;

/**
 * Загружает заметки и приводит каждую к единому виду.
 *
 * @return list<array{id:int, title:string, done:bool, author:?string}>
 */
function loadNotes(string $path): array
{
    $notes = [];
    foreach (loadJson($path) as $row) {
        if (!is_array($row) || !isset($row['id'], $row['title'])) {
            continue; // запись без обязательных полей пропускаем
        }
        $notes[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'done' => (bool) ($row['done'] ?? false),
            'author' => isset($row['author']) ? (string) $row['author'] : null,
        ];
    }

    return $notes;
}

/**
 * @param list<array<string, mixed>> $notes
 */
function saveNotes(string $path, array $notes): void
{
    // author записываем только если он есть, чтобы не засорять исходные записи dataset
    $clean = array_map(static function (array $note): array {
        $row = ['id' => $note['id'], 'title' => $note['title'], 'done' => $note['done']];
        if ($note['author'] !== null) {
            $row['author'] = $note['author'];
        }
        return $row;
    }, $notes);

    saveJson($path, $clean);
}

/**
 * Следующий уникальный id: максимальный существующий + 1.
 *
 * @param list<array<string, mixed>> $notes
 */
function nextNoteId(array $notes): int
{
    return $notes === [] ? 1 : max(array_column($notes, 'id')) + 1;
}

/**
 * Проверка заголовка новой заметки. Возвращает текст ошибки или null.
 */
function validateNoteTitle(string $title): ?string
{
    $length = mb_strlen($title);
    if ($title === '') {
        return 'Введите текст заметки.';
    }
    if ($length < NOTE_TITLE_MIN || $length > NOTE_TITLE_MAX) {
        return sprintf('Текст заметки — от %d до %d символов.', NOTE_TITLE_MIN, NOTE_TITLE_MAX);
    }

    return null;
}

/**
 * Переключает признак «выполнено» у заметки. Возвращает false, если заметки нет.
 *
 * @param list<array<string, mixed>> $notes
 */
function toggleNote(array &$notes, int $id): bool
{
    foreach ($notes as &$note) {
        if ($note['id'] === $id) {
            $note['done'] = !$note['done'];
            return true;
        }
    }

    return false;
}

/**
 * @param list<array<string, mixed>> $notes
 * @return array{total:int, done:int, open:int}
 */
function notesStats(array $notes): array
{
    $done = count(array_filter($notes, static fn (array $n): bool => $n['done']));

    return ['total' => count($notes), 'done' => $done, 'open' => count($notes) - $done];
}
