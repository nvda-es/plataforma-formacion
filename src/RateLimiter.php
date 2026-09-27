<?php
declare(strict_types=1);

namespace App;

/**
 * Limitador de frecuencia basado en base de datos (ventana deslizante).
 * Las claves (correo, IP) se guardan como hash.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $bucket, string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $count = (int) DB::value(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND key_hash = ? AND created_at > ?',
            [$bucket, self::hash($key), date('Y-m-d H:i:s', time() - $decaySeconds)]
        );
        return $count >= $maxAttempts;
    }

    public static function hit(string $bucket, string $key): void
    {
        DB::insert(
            'INSERT INTO rate_limits (bucket, key_hash, created_at) VALUES (?, ?, ?)',
            [$bucket, self::hash($key), DB::now()]
        );
        // Limpieza ocasional de registros antiguos
        if (random_int(1, 100) === 1) {
            DB::exec('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public static function clear(string $bucket, string $key): void
    {
        DB::exec('DELETE FROM rate_limits WHERE bucket = ? AND key_hash = ?', [$bucket, self::hash($key)]);
    }

    private static function hash(string $key): string
    {
        return hash('sha256', mb_strtolower($key));
    }
}
