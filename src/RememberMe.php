<?php
declare(strict_types=1);

namespace App;

use App\Models\User;

/**
 * Sesión recordada mediante una cookie persistente.
 *
 * La cookie contiene "selector:validador". El selector localiza el registro y el
 * validador (256 bits) se compara con su hash en tiempo constante. En cada uso el
 * validador rota; el anterior se sigue aceptando durante unos segundos para que dos
 * peticiones simultáneas con la cookie antigua no cierren la sesión. La caducidad es
 * absoluta: no se alarga con el uso.
 */
final class RememberMe
{
    private const GRACE_SECONDS = 60;

    public static function cookieName(): string
    {
        return (string) App::config('security.remember_cookie', 'formacion_remember');
    }

    public static function days(): int
    {
        return max(1, (int) App::config('security.remember_days', 30));
    }

    public static function hasCookie(): bool
    {
        return isset($_COOKIE[self::cookieName()]) && is_string($_COOKIE[self::cookieName()]);
    }

    /** Crea un token para el usuario y envía la cookie. */
    public static function issue(int $userId): void
    {
        self::forget();
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + self::days() * 86400;

        DB::insert(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires), DB::now()]
        );
        self::setCookie($selector . ':' . $validator, $expires);

        // Limpieza ocasional de tokens caducados
        if (random_int(1, 50) === 1) {
            DB::exec('DELETE FROM remember_tokens WHERE expires_at < ?', [DB::now()]);
        }
    }

    /**
     * Valida la cookie y devuelve el usuario si es correcta. Rota el validador.
     * Una cookie no válida se elimina del navegador.
     */
    public static function consume(): ?array
    {
        if (!self::hasCookie()) {
            return null;
        }
        $parts = self::parse($_COOKIE[self::cookieName()]);
        if ($parts === null) {
            self::clearCookie();
            return null;
        }
        [$selector, $validator] = $parts;

        $row = DB::one(
            'SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > ?',
            [$selector, DB::now()]
        );
        if ($row === null) {
            self::clearCookie();
            return null;
        }

        $hash = hash('sha256', $validator);
        $current = hash_equals($row['validator_hash'], $hash);
        $recentPrevious = !$current
            && $row['previous_hash'] !== null
            && hash_equals($row['previous_hash'], $hash)
            && $row['rotated_at'] !== null
            && strtotime($row['rotated_at']) >= time() - self::GRACE_SECONDS;
        if (!$current && !$recentPrevious) {
            self::clearCookie();
            return null;
        }

        $user = User::find((int) $row['user_id']);
        if ($user === null || (int) $user['is_active'] !== 1 || $user['email_verified_at'] === null) {
            DB::exec('DELETE FROM remember_tokens WHERE id = ?', [(int) $row['id']]);
            self::clearCookie();
            return null;
        }

        if ($current) {
            $newValidator = bin2hex(random_bytes(32));
            $rotated = DB::exec(
                'UPDATE remember_tokens SET previous_hash = validator_hash, validator_hash = ?, rotated_at = ?
                 WHERE id = ? AND validator_hash = ?',
                [hash('sha256', $newValidator), DB::now(), (int) $row['id'], $row['validator_hash']]
            );
            if ($rotated === 1) {
                self::setCookie($selector . ':' . $newValidator, (int) strtotime($row['expires_at']));
            }
        }
        return $user;
    }

    /** Elimina el token de este navegador (al cerrar sesión). */
    public static function forget(): void
    {
        if (self::hasCookie()) {
            $parts = self::parse($_COOKIE[self::cookieName()]);
            if ($parts !== null) {
                DB::exec('DELETE FROM remember_tokens WHERE selector = ?', [$parts[0]]);
            }
        }
        self::clearCookie();
    }

    /** Elimina todas las sesiones recordadas del usuario (cambio de contraseña, baja…). */
    public static function forgetUser(int $userId): void
    {
        DB::exec('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    /** @return array{0: string, 1: string}|null */
    private static function parse(string $value): ?array
    {
        if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $value, $m)) {
            return null;
        }
        return [$m[1], $m[2]];
    }

    private static function setCookie(string $value, int $expires): void
    {
        $_COOKIE[self::cookieName()] = $value;
        if (headers_sent()) {
            return;
        }
        setcookie(self::cookieName(), $value, [
            'expires' => $expires,
            'path' => App::basePath() !== '' ? App::basePath() . '/' : '/',
            'secure' => App::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearCookie(): void
    {
        $present = self::hasCookie();
        unset($_COOKIE[self::cookieName()]);
        if ($present && !headers_sent()) {
            setcookie(self::cookieName(), '', [
                'expires' => time() - 3600,
                'path' => App::basePath() !== '' ? App::basePath() . '/' : '/',
                'secure' => App::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}
