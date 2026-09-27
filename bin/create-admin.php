<?php
declare(strict_types=1);

/*
 * Crea (o promociona) una cuenta de administrador.
 * Uso: php bin/create-admin.php "Nombre y apellidos" correo@ejemplo.es
 * La contraseña se pide de forma interactiva para que no quede en el historial de la consola.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Password;

[$script, $name, $email] = $argv + [null, null, null];
if ($name === null || $email === null) {
    fwrite(STDERR, "Uso: php bin/create-admin.php \"Nombre y apellidos\" correo@ejemplo.es\n");
    exit(1);
}
$email = User::normalizeEmail($email);
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "El correo electrónico no es válido.\n");
    exit(1);
}

function prompt_hidden(string $label): string
{
    echo $label;
    $isWindows = PHP_OS_FAMILY === 'Windows';
    if (!$isWindows) {
        shell_exec('stty -echo');
    }
    // Sin BOM: PowerShell la antepone al texto que se envía por una tubería
    $value = (string) preg_replace('/^\xEF\xBB\xBF/', '', rtrim((string) fgets(STDIN), "\r\n"));
    if (!$isWindows) {
        shell_exec('stty echo');
    }
    echo PHP_EOL;
    return $value;
}

if (PHP_OS_FAMILY === 'Windows') {
    echo "Aviso: en Windows la contraseña se verá al escribirla.\n";
}
$password = prompt_hidden('Contraseña (mínimo ' . Password::MIN_LENGTH . ' caracteres): ');
$confirmation = prompt_hidden('Repite la contraseña: ');

$errors = Password::validate($password, $confirmation);
if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$existing = User::findByEmail($email);
if ($existing !== null) {
    User::update((int) $existing['id'], $name, $email, 'admin', true, $existing['email_verified_at'] ?? App\DB::now());
    User::updatePassword((int) $existing['id'], Password::hash($password));
    echo "El usuario {$email} ya existía: ahora es administrador y tiene la contraseña nueva.\n";
} else {
    User::create($name, $email, Password::hash($password), 'admin', true);
    echo "Administrador {$email} creado.\n";
}
