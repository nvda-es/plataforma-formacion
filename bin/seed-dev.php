<?php
declare(strict_types=1);

/*
 * Datos de prueba para desarrollo: un administrador, un profesor y el curso de ejemplo
 * asignado a ese profesor. Solo se ejecuta con app.env = development.
 * Uso: php bin/seed-dev.php
 *
 * Credenciales de prueba (NUNCA usarlas en producción):
 *   admin@formacion.test    / Admin-de-pruebas-2026
 *   profesor@formacion.test / Profesor-de-pruebas-2026
 * La alumna de las pruebas manuales se registra desde el formulario:
 *   alumna@formacion.test   / Alumna-de-pruebas-2026
 * El profesor invitado desde la administración fija su contraseña con el enlace del correo:
 *   invitado@formacion.test / Invitado-de-pruebas-2026
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\CourseImporter;
use App\DB;
use App\Models\User;
use App\Password;

if (App\App::config('app.env') !== 'development') {
    fwrite(STDERR, "Este script solo se puede ejecutar con app.env = development.\n");
    exit(1);
}

$users = [
    ['Administración de pruebas', 'admin@formacion.test', 'Admin-de-pruebas-2026', 'admin'],
    ['Profesora de pruebas', 'profesor@formacion.test', 'Profesor-de-pruebas-2026', 'teacher'],
];

$ids = [];
foreach ($users as [$name, $email, $password, $role]) {
    $existing = User::findByEmail($email);
    if ($existing !== null) {
        $ids[$role] = (int) $existing['id'];
        echo "Ya existe {$email}\n";
        continue;
    }
    $ids[$role] = User::create($name, $email, Password::hash($password), $role, true);
    echo "Creado {$email} ({$role})\n";
}

if (DB::value('SELECT 1 FROM courses WHERE title = ?', ['Introducción a la accesibilidad web']) === null) {
    $parsed = CourseImporter::parse((string) file_get_contents(BASE_PATH . '/content/ejemplo-accesibilidad-web.md'));
    $courseId = CourseImporter::save($parsed, $ids['teacher'], true);
    echo "Curso de ejemplo importado (id {$courseId}) y asignado al profesor\n";
} else {
    echo "El curso de ejemplo ya existe\n";
}
