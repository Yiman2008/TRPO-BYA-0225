<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

/**
 * @return list<array{id:int, name:string}>
 */
function loadUsers(string $path): array
{
    $users = [];
    foreach (loadJson($path) as $row) {
        if (is_array($row) && isset($row['id'], $row['name']) && trim((string) $row['name']) !== '') {
            $users[] = ['id' => (int) $row['id'], 'name' => trim((string) $row['name'])];
        }
    }

    return $users;
}
