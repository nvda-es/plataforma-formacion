<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;
use App\Support\Str;

final class Course
{
    private const SELECT = 'SELECT c.*, u.name AS teacher_name, u.email AS teacher_email
                            FROM courses c LEFT JOIN users u ON u.id = c.teacher_id';

    public static function find(int $id): ?array
    {
        return DB::one(self::SELECT . ' WHERE c.id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::one(self::SELECT . ' WHERE c.slug = ?', [$slug]);
    }

    public static function published(): array
    {
        return DB::all(self::SELECT . ' WHERE c.is_published = 1 ORDER BY c.title');
    }

    public static function all(): array
    {
        return DB::all(self::SELECT . ' ORDER BY c.title');
    }

    /** Listado para administración con recuentos. */
    public static function adminList(): array
    {
        return DB::all(
            'SELECT c.*, u.name AS teacher_name,
                (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) AS modules_count,
                (SELECT COUNT(*) FROM items i JOIN modules m ON m.id = i.module_id WHERE m.course_id = c.id) AS items_count,
                (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = c.id) AS enrollments_count
             FROM courses c LEFT JOIN users u ON u.id = c.teacher_id
             ORDER BY c.title'
        );
    }

    public static function create(array $data): int
    {
        $now = DB::now();
        return DB::insert(
            'INSERT INTO courses (slug, title, summary, teacher_id, is_published, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$data['slug'], $data['title'], $data['summary'], $data['teacher_id'], $data['is_published'] ? 1 : 0, $now, $now]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::exec(
            'UPDATE courses SET slug = ?, title = ?, summary = ?, teacher_id = ?, is_published = ?, updated_at = ? WHERE id = ?',
            [$data['slug'], $data['title'], $data['summary'], $data['teacher_id'], $data['is_published'] ? 1 : 0, DB::now(), $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::exec('DELETE FROM courses WHERE id = ?', [$id]);
    }

    public static function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return DB::value(
            'SELECT 1 FROM courses WHERE slug = ? AND id <> ?',
            [$slug, $ignoreId ?? 0]
        ) !== null;
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while (self::slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public static function modules(int $courseId): array
    {
        return DB::all('SELECT * FROM modules WHERE course_id = ? ORDER BY position, id', [$courseId]);
    }

    /** Contenidos del curso en orden de lectura. */
    public static function flatItems(int $courseId): array
    {
        return DB::all(
            'SELECT i.id, i.type, i.title, i.module_id
             FROM items i JOIN modules m ON m.id = i.module_id
             WHERE m.course_id = ?
             ORDER BY m.position, m.id, i.position, i.id',
            [$courseId]
        );
    }

    public static function findItem(int $courseId, int $itemId): ?array
    {
        return DB::one(
            'SELECT i.*, m.title AS module_title, m.course_id
             FROM items i JOIN modules m ON m.id = i.module_id
             WHERE i.id = ? AND m.course_id = ?',
            [$itemId, $courseId]
        );
    }

    /**
     * Módulos con sus contenidos. Si se indica un alumno, cada contenido incluye
     * si lo ha visto (viewed) y el estado de su último intento (last_status).
     */
    public static function outline(int $courseId, int $userId = 0): array
    {
        $modules = self::modules($courseId);
        $items = DB::all(
            'SELECT i.id, i.module_id, i.type, i.title, i.position,
                EXISTS(SELECT 1 FROM item_views v WHERE v.item_id = i.id AND v.user_id = ?) AS viewed,
                (SELECT s.status FROM submissions s WHERE s.item_id = i.id AND s.user_id = ? ORDER BY s.id DESC LIMIT 1) AS last_status
             FROM items i JOIN modules m ON m.id = i.module_id
             WHERE m.course_id = ?
             ORDER BY m.position, m.id, i.position, i.id',
            [$userId, $userId, $courseId]
        );
        $byModule = [];
        foreach ($items as $item) {
            $byModule[(int) $item['module_id']][] = $item;
        }
        foreach ($modules as &$module) {
            $module['items'] = $byModule[(int) $module['id']] ?? [];
        }
        unset($module);
        return $modules;
    }

    /** Un contenido está completado si es una unidad leída o un ejercicio aprobado. */
    public static function itemDone(array $item): bool
    {
        return $item['type'] === 'unit' ? (bool) $item['viewed'] : $item['last_status'] === 'approved';
    }
}
