<?php
/*
 * Copia este archivo como config/config.php y ajusta los valores.
 * config/config.php no debe subirse nunca al control de versiones.
 */
return [
    'app' => [
        'name' => 'Plataforma de Formación',
        // URL pública de la aplicación (sin barra final). Se usa para generar los enlaces
        // de los correos, así que nunca se toma de la cabecera Host de la petición.
        // Si la aplicación está en un subdirectorio, inclúyelo: https://ejemplo.es/formacion
        'url' => 'http://localhost:8000',
        'env' => 'development', // development | production
        'debug' => true,        // Pon false en producción
        'timezone' => 'Europe/Madrid',
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'formacion',
        'user' => 'formacion',
        'pass' => 'cambia-esta-contraseña',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        // smtp: envío por SMTP | mail: función mail() de PHP | log: escribe los correos en storage/logs/mail.log (solo desarrollo)
        'driver' => 'log',
        'host' => 'smtp.ejemplo.es',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls', // tls (STARTTLS) | ssl | none
        'from_address' => 'no-responder@ejemplo.es',
        'from_name' => 'Plataforma de Formación',
    ],

    'recaptcha' => [
        // Claves de reCAPTCHA v3: https://www.google.com/recaptcha/admin
        'enabled' => true,
        'site_key' => '',
        'secret_key' => '',
        'min_score' => 0.5,
        // Comprueba que el hostname devuelto por Google coincide con el de app.url
        'check_hostname' => true,
    ],

    'security' => [
        'session_name' => 'formacion_sid',
        // Segundos de inactividad tras los que se cierra la sesión
        'session_idle_timeout' => 1800,
        // Toma la IP del cliente de la cabecera X-Forwarded-For (primera IP válida).
        // Actívalo solo si un proxy de confianza fija esa cabecera y los clientes no pueden
        // llegar a la aplicación sin pasar por él; si no, podrían falsear su IP.
        'trust_forwarded_for' => true,
        // Envía Strict-Transport-Security (solo tiene efecto si app.url usa https)
        'hsts' => false,
    ],
];
