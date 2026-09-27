<?php
declare(strict_types=1);

namespace App;

/**
 * Verificación de tokens de reCAPTCHA v3 en el servidor.
 */
final class Recaptcha
{
    public const ERROR_MESSAGE = 'No hemos podido comprobar que la solicitud la hace una persona. '
        . 'Espera unos segundos y vuelve a intentarlo. Si el problema continúa, desactiva los bloqueadores de contenido para este sitio.';

    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function enabled(): bool
    {
        return (bool) App::config('recaptcha.enabled', true);
    }

    public static function siteKey(): string
    {
        return (string) App::config('recaptcha.site_key', '');
    }

    public static function verify(?string $token, string $expectedAction): bool
    {
        if (!self::enabled()) {
            return true;
        }
        if ($token === null || $token === '' || strlen($token) > 4096) {
            return false;
        }
        $secret = (string) App::config('recaptcha.secret_key', '');
        if ($secret === '') {
            error_log('reCAPTCHA está activado pero falta recaptcha.secret_key');
            return false;
        }

        $body = self::post(http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => client_ip(),
        ]));
        if ($body === null) {
            return false;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return false;
        }
        if (($data['action'] ?? '') !== $expectedAction) {
            return false;
        }
        if ((float) ($data['score'] ?? 0.0) < (float) App::config('recaptcha.min_score', 0.5)) {
            return false;
        }
        if (App::config('recaptcha.check_hostname', true)) {
            $expectedHost = parse_url((string) App::config('app.url', ''), PHP_URL_HOST);
            if (($data['hostname'] ?? null) !== $expectedHost) {
                return false;
            }
        }
        return true;
    }

    private static function post(string $payload): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init(self::VERIFY_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            if (!is_string($response)) {
                error_log('Error al verificar reCAPTCHA: ' . $error);
                return null;
            }
            return $response;
        }

        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 8,
        ]]);
        $response = @file_get_contents(self::VERIFY_URL, false, $context);
        if ($response === false) {
            error_log('Error al verificar reCAPTCHA: no se pudo contactar con Google');
            return null;
        }
        return $response;
    }
}
