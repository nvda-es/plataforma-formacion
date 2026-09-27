<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Contenedor mínimo de configuración y conexión a la base de datos.
 */
final class App
{
    private static array $config = [];
    private static ?PDO $db = null;

    public static function boot(array $config): void
    {
        self::$config = $config;

        mb_internal_encoding('UTF-8');
        date_default_timezone_set((string) self::config('app.timezone', 'UTC'));

        error_reporting(E_ALL);
        ini_set('display_errors', self::debug() ? '1' : '0');
        ini_set('log_errors', '1');
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        ini_set('error_log', $logDir . '/app.log');
    }

    /** Lee un valor de configuración con notación de puntos: "mail.driver". */
    public static function config(string $key, mixed $default = null): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function debug(): bool
    {
        return (bool) self::config('app.debug', false);
    }

    public static function db(): PDO
    {
        if (self::$db === null) {
            $c = self::config('db', []);
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $c['host'] ?? '127.0.0.1',
                (int) ($c['port'] ?? 3306),
                $c['name'] ?? '',
                $c['charset'] ?? 'utf8mb4'
            );
            self::$db = new PDO($dsn, (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Sentencias preparadas reales en el servidor
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Alinea la zona horaria de MySQL con la de PHP
            $offset = (new \DateTimeImmutable())->format('P');
            self::$db->exec("SET time_zone = '" . $offset . "'");
        }
        return self::$db;
    }

    /** Ruta base cuando la aplicación está en un subdirectorio (sin barra final). */
    public static function basePath(): string
    {
        $path = parse_url((string) self::config('app.url', ''), PHP_URL_PATH) ?: '';
        return rtrim($path, '/');
    }

    public static function isHttps(): bool
    {
        return str_starts_with((string) self::config('app.url', ''), 'https://');
    }
}
