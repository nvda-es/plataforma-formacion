<?php
declare(strict_types=1);

/*
 * Importa un curso desde un archivo Markdown.
 * Uso: php bin/import-course.php ruta/curso.md [--teacher=correo@ejemplo.es] [--publish]
 * El formato se describe en src/CourseImporter.php y en el README.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/src/bootstrap.php';

use App\CourseImporter;
use App\Models\User;

$file = null;
$teacherEmail = null;
$publish = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--publish') {
        $publish = true;
    } elseif (str_starts_with($arg, '--teacher=')) {
        $teacherEmail = substr($arg, strlen('--teacher='));
    } else {
        $file = $arg;
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Uso: php bin/import-course.php ruta/curso.md [--teacher=correo@ejemplo.es] [--publish]\n");
    exit(1);
}

$teacherId = null;
if ($teacherEmail !== null) {
    $teacher = User::findByEmail($teacherEmail);
    if ($teacher === null || $teacher['role'] !== 'teacher') {
        fwrite(STDERR, "No existe ningún profesor con el correo {$teacherEmail}.\n");
        exit(1);
    }
    $teacherId = (int) $teacher['id'];
}

$markdown = file_get_contents($file);
if ($markdown === false || !mb_check_encoding($markdown, 'UTF-8')) {
    fwrite(STDERR, "El archivo debe estar codificado en UTF-8.\n");
    exit(1);
}

try {
    $parsed = CourseImporter::parse($markdown);
} catch (RuntimeException $e) {
    fwrite(STDERR, 'Formato no válido: ' . $e->getMessage() . "\n");
    exit(1);
}

$courseId = CourseImporter::save($parsed, $teacherId, $publish);
$items = array_sum(array_map(static fn (array $m): int => count($m['items']), $parsed['modules']));
printf(
    "Curso «%s» importado (id %d). Módulos: %d. Contenidos: %d. Estado: %s.\n",
    $parsed['title'],
    $courseId,
    count($parsed['modules']),
    $items,
    $publish ? 'publicado' : 'borrador'
);
