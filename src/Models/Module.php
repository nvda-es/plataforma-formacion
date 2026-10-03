<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;
use App\Support\Ordering;

final class Module
{
    public static function find(int $id): ?array
    {
        return DB::one(
            'SELECT m.*, c.title AS course_title, c.slug AS course_slug
             FROM modules m JOIN courses c ON c.id = m.course_id WHERE m.id = ?',
            [$id]
        );
    }

    /** Contenidos completos del módulo, en orden. */
    public static function items(int $moduleId): array
    {
        return DB::all('SELECT * FROM items WHERE module_id = ? ORDER BY position, id', [$moduleId]);
    }

    public static function create(int $courseId, string $title, string $description): int
    {
        $now = DB::now();
        return DB::insert(
            'INSERT INTO modules (course_id, title, description, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$courseId, $title, $description, Ordering::next('modules', 'course_id', $courseId), $now, $now]
        );
    }

    public static function update(int $id, string $title, string $description): void
    {
        DB::exec(
            'UPDATE modules SET title = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $description, DB::now(), $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::exec('DELETE FROM modules WHERE id = ?', [$id]);
    }

    public static function move(array $module, string $direction): void
    {
        Ordering::move('modules', 'course_id', (int) $module['course_id'], (int) $module['id'], $direction);
    }
}
