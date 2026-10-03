<?php
declare(strict_types=1);

/*
 * Controlador frontal. La raíz pública del servidor web debe apuntar a esta carpeta (public/).
 */

// Servidor integrado de PHP: sirve directamente los archivos estáticos existentes
if (PHP_SAPI === 'cli-server') {
    $requested = realpath(__DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    if ($requested !== false
        && is_file($requested)
        && str_starts_with($requested, __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR)
    ) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

App\SecurityHeaders::send();
App\Session::start();

set_exception_handler(static function (Throwable $e): void {
    App\Http\ErrorHandler::handle($e);
});

// Resuelve el usuario (y una posible sesión recordada) antes de generar ninguna salida
App\Auth::user();

/** @var App\Router $router */
$router = require BASE_PATH . '/src/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
