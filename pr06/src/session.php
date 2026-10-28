<?php

declare(strict_types=1);

/**
 * Работа с пользовательским состоянием в $_SESSION.
 * В сессии хранится только выбор пользователя (id из users.json) и одноразовые flash-сообщения.
 */

const DEFAULT_USER_NAME = 'Гость';

function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['user_id'] ??= null;
}

/**
 * @param list<array{id:int, name:string}> $users
 * @return array{id:int, name:string}|null
 */
function currentUser(array $users): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    foreach ($users as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }

    return null;
}

function setCurrentUser(int $id): void
{
    $_SESSION['user_id'] = $id;
}

function clearCurrentUser(): void
{
    $_SESSION['user_id'] = null;
}

/** Одноразовое сообщение: показывается на следующей странице после redirect. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type:string, message:string}> */
function takeFlashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}
