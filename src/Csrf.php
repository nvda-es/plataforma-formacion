<?php
declare(strict_types=1);

namespace App;

/**
 * Token sincronizado por sesión contra CSRF. Se valida en todas las peticiones POST.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            self::regenerate();
        }
        return $_SESSION['_csrf'];
    }

    public static function regenerate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function validate(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf'])
            && is_string($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }
}
