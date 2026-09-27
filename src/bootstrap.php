<?php
declare(strict_types=1);

/*
 * Arranque común para la web y los scripts de consola.
 */
define('BASE_PATH', dirname(__DIR__));

if (!is_file(BASE_PATH . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Faltan las dependencias. Ejecuta "composer install" en la raíz del proyecto.' . PHP_EOL);
}
require BASE_PATH . '/vendor/autoload.php';

if (!is_file(BASE_PATH . '/config/config.php')) {
    http_response_code(500);
    exit('Falta la configuración. Copia config/config.example.php como config/config.php y ajústalo.' . PHP_EOL);
}

App\App::boot(require BASE_PATH . '/config/config.php');
