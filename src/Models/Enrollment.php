<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;

final class Enrollment
{
    public static function find(int $userId, int $courseId): ?array
    {
        return DB::one('SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    }

    /** @return bool true si se ha creado la matrícula, false si ya existía */
    public static function enroll(int $userId, int $courseId): bool
    {
        return DB::exec(
            'INSERT IGNORE INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, ?)',
            [$userId, $courseId, DB::now()]
        ) === 1;
    }

    public static function remove(int $userId, int $courseId): void
    {
        DB::exec('DELETE FROM enrollments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    }

    public static function forUser(int $userId): array
    {
        return DB::all(
            'SELECT e.*, c.title, c.slug, c.is_published
             FROM enrollments e JOIN courses c ON c.id = e.course_id
             WHERE e.user_id = ?
             ORDER BY e.completed_at IS NOT NULL, c.title',
            [$userId]
        );
    }

    /** Ids de los cursos en los que está matriculado el alumno. */
    public static function courseIds(int $userId): array
    {
        return array_map('intval', array_column(
            DB::all('SELECT course_id FROM enrollments WHERE user_id = ?', [$userId]),
            'course_id'
        ));
    }
}
