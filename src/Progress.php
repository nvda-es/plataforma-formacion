<?php
declare(strict_types=1);

namespace App;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Seguimiento del progreso del alumno y finalización de cursos.
 *
 * Un curso está completado cuando el alumno ha leído todas las unidades
 * y tiene aprobados todos los ejercicios.
 */
final class Progress
{
    public static function markViewed(int $userId, int $itemId): void
    {
        DB::exec(
            'INSERT IGNORE INTO item_views (user_id, item_id, viewed_at) VALUES (?, ?, ?)',
            [$userId, $itemId, DB::now()]
        );
    }

    /**
     * @return array{units:int, exercises:int, units_read:int, exercises_approved:int, total:int, done:int, percent:int, complete:bool}
     */
    public static function summary(int $userId, int $courseId): array
    {
        $row = DB::one(
            "SELECT
                COALESCE(SUM(i.type = 'unit'), 0) AS units,
                COALESCE(SUM(i.type = 'exercise'), 0) AS exercises,
                COALESCE(SUM(i.type = 'unit' AND v.item_id IS NOT NULL), 0) AS units_read,
                COALESCE(SUM(i.type = 'exercise' AND EXISTS(
                    SELECT 1 FROM submissions s
                    WHERE s.item_id = i.id AND s.user_id = ? AND s.status = 'approved'
                )), 0) AS exercises_approved
             FROM items i
             JOIN modules m ON m.id = i.module_id
             LEFT JOIN item_views v ON v.item_id = i.id AND v.user_id = ?
             WHERE m.course_id = ?",
            [$userId, $userId, $courseId]
        ) ?? [];

        $units = (int) ($row['units'] ?? 0);
        $exercises = (int) ($row['exercises'] ?? 0);
        $unitsRead = (int) ($row['units_read'] ?? 0);
        $approved = (int) ($row['exercises_approved'] ?? 0);
        $total = $units + $exercises;
        $done = $unitsRead + $approved;

        return [
            'units' => $units,
            'exercises' => $exercises,
            'units_read' => $unitsRead,
            'exercises_approved' => $approved,
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? (int) floor($done * 100 / $total) : 0,
            'complete' => $total > 0 && $done >= $total,
        ];
    }

    /**
     * Marca el curso como completado si se cumplen los requisitos y envía
     * el correo de felicitación (solo una vez).
     */
    public static function checkCompletion(int $userId, int $courseId): bool
    {
        $enrollment = Enrollment::find($userId, $courseId);
        if ($enrollment === null || $enrollment['completed_at'] !== null) {
            return false;
        }
        if (!self::summary($userId, $courseId)['complete']) {
            return false;
        }
        // La condición "completed_at IS NULL" evita enviar el correo dos veces
        $updated = DB::exec(
            'UPDATE enrollments SET completed_at = ? WHERE id = ? AND completed_at IS NULL',
            [DB::now(), (int) $enrollment['id']]
        );
        if ($updated !== 1) {
            return false;
        }

        $user = User::find($userId);
        $course = Course::find($courseId);
        if ($user !== null && $course !== null) {
            Mailer::send($user['email'], $user['name'], 'course_completed', [
                'name' => $user['name'],
                'courseTitle' => $course['title'],
                'courseUrl' => absolute_url('/cursos/' . $course['slug']),
            ]);
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== '' && (int) $user['id'] === (int) (current_user()['id'] ?? 0)) {
                flash('success', '¡Enhorabuena! Has completado el curso «' . $course['title'] . '». Te hemos enviado un correo de felicitación.');
            }
        }
        return true;
    }
}
