<?php
declare(strict_types=1);

namespace App;

use App\Models\Course;
use App\Models\Module;

/**
 * Exportación a Markdown con el mismo formato que lee CourseImporter:
 *
 * - curso completo: archivo que se puede volver a importar tal cual;
 * - módulo: sección "## Módulo:" con sus unidades y ejercicios, lista para pegarla
 *   en un archivo de curso;
 * - unidad o ejercicio: su contenido Markdown sin cambios, que se puede volver a
 *   subir en el formulario de edición del contenido.
 */
final class CourseExporter
{
    public static function course(array $course): string
    {
        $out = '# Curso: ' . $course['title'] . "\n";
        $summary = trim((string) ($course['summary'] ?? ''));
        if ($summary !== '') {
            $out .= "\n" . CourseImporter::protectStructure($summary) . "\n";
        }
        foreach (Course::modules((int) $course['id']) as $module) {
            $out .= "\n" . self::module($module);
        }
        return $out;
    }

    public static function module(array $module): string
    {
        $out = '## Módulo: ' . $module['title'] . "\n";
        $description = trim((string) ($module['description'] ?? ''));
        if ($description !== '') {
            $out .= "\n" . CourseImporter::protectStructure($description) . "\n";
        }
        foreach (Module::items((int) $module['id']) as $item) {
            $label = $item['type'] === 'exercise' ? 'Ejercicio' : 'Unidad';
            $out .= "\n### {$label}: " . $item['title'] . "\n\n"
                . CourseImporter::protectStructure(trim((string) $item['content'])) . "\n";
        }
        return $out;
    }

    public static function item(array $item): string
    {
        return rtrim(str_replace(["\r\n", "\r"], "\n", (string) $item['content'])) . "\n";
    }
}
