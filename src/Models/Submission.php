<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;

/** Intentos de respuesta de los alumnos a los ejercicios. */
final class Submission
{
    public const STATUSES = ['pending', 'approved', 'failed'];

    public static function latest(int $userId, int $itemId): ?array
    {
        return DB::one(
            'SELECT * FROM submissions WHERE user_id = ? AND item_id = ? ORDER BY id DESC LIMIT 1',
            [$userId, $itemId]
        );
    }

    /** Todos los intentos, del más reciente al más antiguo. */
    public static function history(int $userId, int $itemId): array
    {
        return DB::all(
            'SELECT s.*, g.name AS grader_name
             FROM submissions s LEFT JOIN users g ON g.id = s.graded_by
             WHERE s.user_id = ? AND s.item_id = ?
             ORDER BY s.id DESC',
            [$userId, $itemId]
        );
    }

    public static function create(int $userId, int $itemId, string $answer): int
    {
        return DB::insert(
            'INSERT INTO submissions (item_id, user_id, answer, status, submitted_at) VALUES (?, ?, ?, ?, ?)',
            [$itemId, $userId, $answer, 'pending', DB::now()]
        );
    }

    public static function findDetailed(int $id): ?array
    {
        return DB::one(
            'SELECT s.*, i.title AS item_title, i.content AS item_content,
                m.title AS module_title,
                c.id AS course_id, c.title AS course_title, c.slug AS course_slug, c.teacher_id,
                st.name AS student_name, st.email AS student_email,
                g.name AS grader_name
             FROM submissions s
             JOIN items i ON i.id = s.item_id
             JOIN modules m ON m.id = i.module_id
             JOIN courses c ON c.id = m.course_id
             JOIN users st ON st.id = s.user_id
             LEFT JOIN users g ON g.id = s.graded_by
             WHERE s.id = ?',
            [$id]
        );
    }

    /**
     * Entregas visibles para quien corrige: el administrador ve todas;
     * el profesor, solo las de sus cursos.
     */
    public static function listForGrader(array $user, string $status): array
    {
        $where = [];
        $params = [];
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        if ($user['role'] !== 'admin') {
            $where[] = 'c.teacher_id = ?';
            $params[] = (int) $user['id'];
        }
        $sqlWhere = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = $status === 'pending' ? 's.submitted_at ASC' : 's.submitted_at DESC';

        return DB::all(
            "SELECT s.id, s.status, s.submitted_at, s.graded_at,
                i.title AS item_title, c.title AS course_title, st.name AS student_name
             FROM submissions s
             JOIN items i ON i.id = s.item_id
             JOIN modules m ON m.id = i.module_id
             JOIN courses c ON c.id = m.course_id
             JOIN users st ON st.id = s.user_id
             {$sqlWhere}
             ORDER BY {$order}
             LIMIT 200",
            $params
        );
    }

    /** Califica una entrega pendiente. Devuelve false si ya estaba calificada. */
    public static function grade(int $id, string $status, string $feedback, int $graderId): bool
    {
        return DB::exec(
            "UPDATE submissions SET status = ?, feedback = ?, graded_at = ?, graded_by = ?
             WHERE id = ? AND status = 'pending'",
            [$status, $feedback, DB::now(), $graderId, $id]
        ) === 1;
    }

    public static function countPending(?int $teacherId = null): int
    {
        if ($teacherId === null) {
            return (int) DB::value("SELECT COUNT(*) FROM submissions WHERE status = 'pending'");
        }
        return (int) DB::value(
            "SELECT COUNT(*) FROM submissions s
             JOIN items i ON i.id = s.item_id JOIN modules m ON m.id = i.module_id JOIN courses c ON c.id = m.course_id
             WHERE s.status = 'pending' AND c.teacher_id = ?",
            [$teacherId]
        );
    }
}
