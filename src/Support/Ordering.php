<?php
declare(strict_types=1);

namespace App\Support;

use App\DB;

/**
 * Reordenación de módulos y contenidos (subir / bajar una posición).
 */
final class Ordering
{
    /**
     * @param string $table  tabla con columnas id y position (modules o items)
     * @param string $parent columna que agrupa los elementos (course_id o module_id)
     */
    public static function move(string $table, string $parent, int $parentId, int $id, string $direction): void
    {
        if (!in_array($table, ['modules', 'items'], true) || !in_array($parent, ['course_id', 'module_id'], true)) {
            throw new \InvalidArgumentException('Tabla no permitida');
        }
        DB::transaction(static function () use ($table, $parent, $parentId, $id, $direction): void {
            $ids = array_map(
                'intval',
                array_column(DB::all("SELECT id FROM {$table} WHERE {$parent} = ? ORDER BY position, id FOR UPDATE", [$parentId]), 'id')
            );
            $index = array_search($id, $ids, true);
            if ($index === false) {
                return;
            }
            $target = $direction === 'up' ? $index - 1 : $index + 1;
            if ($target < 0 || $target >= count($ids)) {
                return;
            }
            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            foreach ($ids as $position => $rowId) {
                DB::exec("UPDATE {$table} SET position = ? WHERE id = ?", [$position + 1, $rowId]);
            }
        });
    }

    public static function next(string $table, string $parent, int $parentId): int
    {
        if (!in_array($table, ['modules', 'items'], true) || !in_array($parent, ['course_id', 'module_id'], true)) {
            throw new \InvalidArgumentException('Tabla no permitida');
        }
        return (int) DB::value("SELECT COALESCE(MAX(position), 0) + 1 FROM {$table} WHERE {$parent} = ?", [$parentId]);
    }
}
