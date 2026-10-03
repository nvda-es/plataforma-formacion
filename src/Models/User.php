<?php
declare(strict_types=1);

namespace App\Models;

use App\DB;

final class User
{
    public const ROLES = [
        'student' => 'Alumno',
        'teacher' => 'Profesor',
        'admin' => 'Administrador',
    ];

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::one('SELECT * FROM users WHERE email = ?', [self::normalizeEmail($email)]);
    }

    public static function create(string $name, string $email, ?string $passwordHash, string $role, bool $verified): int
    {
        $now = DB::now();
        return DB::insert(
            'INSERT INTO users (name, email, password_hash, role, email_verified_at, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
            [$name, self::normalizeEmail($email), $passwordHash, $role, $verified ? $now : null, $now, $now]
        );
    }

    public static function update(int $id, string $name, string $email, string $role, bool $active, ?string $verifiedAt): void
    {
        DB::exec(
            'UPDATE users SET name = ?, email = ?, role = ?, is_active = ?, email_verified_at = ?, updated_at = ? WHERE id = ?',
            [$name, self::normalizeEmail($email), $role, $active ? 1 : 0, $verifiedAt, DB::now(), $id]
        );
    }

    public static function updatePassword(int $id, string $hash): void
    {
        DB::exec('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?', [$hash, DB::now(), $id]);
    }

    public static function markVerified(int $id): void
    {
        DB::exec(
            'UPDATE users SET email_verified_at = ?, updated_at = ? WHERE id = ? AND email_verified_at IS NULL',
            [DB::now(), DB::now(), $id]
        );
    }

    public static function activeByRole(string $role): array
    {
        return DB::all(
            'SELECT id, name, email FROM users WHERE role = ? AND is_active = 1 ORDER BY name',
            [$role]
        );
    }

    /** Roles que pueden figurar como profesor de un curso. */
    public const COURSE_TEACHER_ROLES = ['teacher', 'admin'];

    /** Profesores y administradores activos, que pueden asignarse como profesor de un curso. */
    public static function assignableTeachers(): array
    {
        return DB::all(
            "SELECT id, name, email, role FROM users
             WHERE role IN ('teacher', 'admin') AND is_active = 1
             ORDER BY role = 'admin', name"
        );
    }

    public static function canTeach(?array $user): bool
    {
        return $user !== null && in_array($user['role'], self::COURSE_TEACHER_ROLES, true);
    }

    /** Listado paginado con filtro por rol y búsqueda por nombre o correo. */
    public static function paginate(string $role, string $search, int $page, int $perPage = 25): array
    {
        $where = [];
        $params = [];
        if (isset(self::ROLES[$role])) {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        $sqlWhere = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) DB::value("SELECT COUNT(*) FROM users {$sqlWhere}", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = DB::all(
            "SELECT id, name, email, role, is_active, email_verified_at, created_at
             FROM users {$sqlWhere} ORDER BY name, id LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function countByRole(string $role): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM users WHERE role = ?', [$role]);
    }
}
