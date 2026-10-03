<?php
declare(strict_types=1);

namespace App;

use App\Models\Course;
use RuntimeException;

/**
 * Importa un curso completo desde un único archivo Markdown con esta estructura:
 *
 *   # Curso: Título del curso
 *   Resumen del curso…
 *
 *   ## Módulo: Título del módulo
 *   Descripción opcional del módulo…
 *
 *   ### Unidad: Título de la unidad
 *   Contenido de la unidad…
 *
 *   ### Ejercicio: Título del ejercicio
 *   Enunciado del ejercicio…
 *
 * Solo esos encabezados con prefijo delimitan la estructura. Cualquier otro
 * encabezado (por ejemplo "#### Ejemplo" o "## Resumen") forma parte del contenido.
 * Los bloques de código delimitados (``` o ~~~) se ignoran al buscar encabezados.
 */
final class CourseImporter
{
    // La secuencia de cierre opcional (" ##") solo cuenta si va precedida de espacio: "C#" no se recorta
    private const COURSE = '/^#\s+Curso\s*:\s*(.+?)(?:\s+#+)?\s*$/iu';
    private const MODULE = '/^##\s+M[oó]dulo\s*:\s*(.+?)(?:\s+#+)?\s*$/iu';
    private const ITEM = '/^###\s+(Unidad|Ejercicio)\s*:\s*(.+?)(?:\s+#+)?\s*$/iu';

    /**
     * Prepara un texto Markdown para incluirlo en un archivo de curso: las líneas que el
     * importador tomaría por estructura (fuera de bloques de código) reciben un espacio
     * inicial. CommonMark admite hasta 3 espacios antes de un encabezado, así que el
     * contenido se sigue mostrando igual, pero ya no se confunde con un módulo o unidad.
     */
    public static function protectStructure(string $markdown): string
    {
        $fence = null;
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        foreach ($lines as &$line) {
            if (self::fenceStep($fence, $line)) {
                continue;
            }
            if (self::isStructural($line)) {
                $line = ' ' . $line;
            }
        }
        unset($line);
        return implode("\n", $lines);
    }

    private static function isStructural(string $line): bool
    {
        return preg_match(self::COURSE, $line) === 1
            || preg_match(self::MODULE, $line) === 1
            || preg_match(self::ITEM, $line) === 1;
    }

    /**
     * Sigue los bloques de código delimitados (``` o ~~~).
     * Devuelve true si la línea abre, cierra o está dentro de un bloque.
     */
    private static function fenceStep(?string &$fence, string $line): bool
    {
        if ($fence !== null) {
            if (preg_match('/^\s{0,3}' . preg_quote($fence[0], '/') . '{' . strlen($fence) . ',}\s*$/', $line)) {
                $fence = null;
            }
            return true;
        }
        if (preg_match('/^\s{0,3}(`{3,}|~{3,})/', $line, $m)) {
            $fence = $m[1];
            return true;
        }
        return false;
    }

    /**
     * @return array{title:string, summary:string, modules:list<array{title:string, description:string, items:list<array{type:string, title:string, content:string}>}>}
     */
    public static function parse(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", (string) preg_replace('/^\xEF\xBB\xBF/', '', $markdown));
        $lines = explode("\n", $markdown);

        $sections = [];
        $current = -1;
        $fence = null;

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (self::fenceStep($fence, $line)) {
                self::append($sections, $current, $line, $lineNumber);
                continue;
            }

            if (preg_match(self::COURSE, $line, $m)) {
                if ($sections !== []) {
                    throw new RuntimeException("Línea {$lineNumber}: solo puede haber un encabezado «# Curso:» y debe ser el primero.");
                }
                $sections[] = ['kind' => 'course', 'title' => $m[1], 'line' => $lineNumber, 'body' => []];
            } elseif (preg_match(self::MODULE, $line, $m)) {
                $sections[] = ['kind' => 'module', 'title' => $m[1], 'line' => $lineNumber, 'body' => []];
            } elseif (preg_match(self::ITEM, $line, $m)) {
                $kind = mb_strtolower($m[1]) === 'unidad' ? 'unit' : 'exercise';
                $sections[] = ['kind' => $kind, 'title' => $m[2], 'line' => $lineNumber, 'body' => []];
            } else {
                self::append($sections, $current, $line, $lineNumber);
                continue;
            }
            $current = count($sections) - 1;
        }

        return self::build($sections);
    }

    private static function append(array &$sections, int $current, string $line, int $lineNumber): void
    {
        if ($current < 0) {
            if (trim($line) !== '') {
                throw new RuntimeException("Línea {$lineNumber}: el archivo debe empezar con un encabezado «# Curso: Título».");
            }
            return;
        }
        $sections[$current]['body'][] = $line;
    }

    private static function build(array $sections): array
    {
        if ($sections === [] || $sections[0]['kind'] !== 'course') {
            throw new RuntimeException('No se ha encontrado el encabezado «# Curso: Título».');
        }

        $course = [
            'title' => self::checkTitle($sections[0]),
            'summary' => trim(implode("\n", $sections[0]['body'])),
            'modules' => [],
        ];

        foreach (array_slice($sections, 1) as $section) {
            $body = trim(implode("\n", $section['body']));
            if ($section['kind'] === 'course') {
                throw new RuntimeException("Línea {$section['line']}: solo puede haber un encabezado «# Curso:».");
            }
            if ($section['kind'] === 'module') {
                $course['modules'][] = ['title' => self::checkTitle($section), 'description' => $body, 'items' => []];
                continue;
            }
            if ($course['modules'] === []) {
                throw new RuntimeException("Línea {$section['line']}: la unidad o ejercicio debe estar dentro de un «## Módulo:».");
            }
            if ($body === '') {
                throw new RuntimeException("Línea {$section['line']}: «{$section['title']}» no tiene contenido.");
            }
            $course['modules'][array_key_last($course['modules'])]['items'][] = [
                'type' => $section['kind'],
                'title' => self::checkTitle($section),
                'content' => $body,
            ];
        }

        if ($course['modules'] === []) {
            throw new RuntimeException('El curso debe tener al menos un «## Módulo:».');
        }
        return $course;
    }

    private static function checkTitle(array $section): string
    {
        $title = trim($section['title']);
        if ($title === '' || mb_strlen($title) > 200) {
            throw new RuntimeException("Línea {$section['line']}: el título debe tener entre 1 y 200 caracteres.");
        }
        return $title;
    }

    /** Guarda el curso analizado en una transacción y devuelve su id. */
    public static function save(array $parsed, ?int $teacherId, bool $publish): int
    {
        return DB::transaction(static function () use ($parsed, $teacherId, $publish): int {
            $now = DB::now();
            $courseId = Course::create([
                'slug' => Course::uniqueSlug($parsed['title']),
                'title' => $parsed['title'],
                'summary' => $parsed['summary'],
                'teacher_id' => $teacherId,
                'is_published' => $publish,
            ]);
            foreach ($parsed['modules'] as $mIndex => $module) {
                $moduleId = DB::insert(
                    'INSERT INTO modules (course_id, title, description, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$courseId, $module['title'], $module['description'], $mIndex + 1, $now, $now]
                );
                foreach ($module['items'] as $iIndex => $item) {
                    DB::insert(
                        'INSERT INTO items (module_id, type, title, content, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$moduleId, $item['type'], $item['title'], $item['content'], $iIndex + 1, $now, $now]
                    );
                }
            }
            return $courseId;
        });
    }
}
