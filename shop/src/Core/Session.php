<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['use_strict_mode' => 1, 'cookie_httponly' => true,
                'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        }
    }

    public function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public function set(string $key, mixed $value): void { $_SESSION[$key] = $value; }

    public function flash(?string $message = null): ?string
    {
        if ($message !== null) {
            $this->set('flash', $message);
            return null;
        }
        $value = $this->get('flash');
        unset($_SESSION['flash']);
        return is_string($value) ? $value : null;
    }

    public function token(): string
    {
        if (!is_string($this->get('csrf'))) {
            $this->set('csrf', bin2hex(random_bytes(32)));
        }
        return $this->get('csrf');
    }

    public function verifyToken(mixed $token): void
    {
        if (!is_string($token) || !hash_equals($this->token(), $token)) {
            throw new HttpException(403, 'Форма устарела. Обновите страницу и повторите действие.');
        }
    }
}
