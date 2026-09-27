<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;
use App\Support\Ordering;

/** Contenido de un módulo: unidad de texto (unit) o ejercicio (exercise). */
final class Item
{
    public const TYPES = [
        'unit' => 'Unidad',
        'exercise' => 'Ejercicio',
    ];

    public static function find(int $id): ?array
    {
        return DB::one(
            'SELECT i.*, m.course_id, m.title AS module_title, c.title AS course_title, c.slug AS course_slug
             FROM items i
             JOIN modules m ON m.id = i.module_id
             JOIN courses c ON c.id = m.course_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public static function create(int $moduleId, string $type, string $title, string $content): int
    {
        $now = DB::now();
        return DB::insert(
            'INSERT INTO items (module_id, type, title, content, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$moduleId, $type, $title, $content, Ordering::next('items', 'module_id', $moduleId), $now, $now]
        );
    }

    public static function update(array $item, int $moduleId, string $type, string $title, string $content): void
    {
        $position = (int) $item['position'];
        if ($moduleId !== (int) $item['module_id']) {
            // Al cambiar de módulo, el contenido pasa al final del módulo de destino
            $position = Ordering::next('items', 'module_id', $moduleId);
        }
        DB::exec(
            'UPDATE items SET module_id = ?, type = ?, title = ?, content = ?, position = ?, updated_at = ? WHERE id = ?',
            [$moduleId, $type, $title, $content, $position, DB::now(), (int) $item['id']]
        );
    }

    public static function delete(int $id): void
    {
        DB::exec('DELETE FROM items WHERE id = ?', [$id]);
    }

    public static function move(array $item, string $direction): void
    {
        Ordering::move('items', 'module_id', (int) $item['module_id'], (int) $item['id'], $direction);
    }
}
