<?php
declare(strict_types=1);

namespace App;

/**
 * Arranque de la sesión con cookies endurecidas y caducidad por inactividad.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');

        session_name((string) App::config('security.session_name', 'formacion_sid'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => App::basePath() !== '' ? App::basePath() . '/' : '/',
            'secure' => App::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $timeout = (int) App::config('security.session_idle_timeout', 1800);
        $now = time();
        $last = $_SESSION['_last_activity'] ?? null;
        if (is_int($last) && $now - $last > $timeout) {
            $hadUser = isset($_SESSION['user_id']);
            $_SESSION = [];
            session_regenerate_id(true);
            if ($hadUser) {
                flash('info', 'Tu sesión se ha cerrado por inactividad. Vuelve a iniciar sesión.');
            }
        }
        $_SESSION['_last_activity'] = $now;
    }
}
