<?php
declare(strict_types=1);

/*
 * Crea las tablas de la base de datos a partir de database/schema.sql.
 * Uso: php bin/install.php
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

$sql = file_get_contents(BASE_PATH . '/database/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "No se puede leer database/schema.sql\n");
    exit(1);
}

// Quita los comentarios de línea y separa las sentencias
$sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql) ?: []));

try {
    $pdo = App\App::db();
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
} catch (PDOException $e) {
    fwrite(STDERR, 'Error de base de datos: ' . $e->getMessage() . "\n");
    exit(1);
}

echo 'Base de datos preparada (' . count($statements) . " sentencias ejecutadas).\n";
echo "Siguiente paso: php bin/create-admin.php \"Nombre\" correo@ejemplo.es\n";
