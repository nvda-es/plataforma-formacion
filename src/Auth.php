<?php
declare(strict_types=1);

namespace App;

use App\Models\User;

/**
 * Autenticación basada en sesión y comprobación de roles.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /**
     * Usuario autenticado. Se recarga en cada petición para reflejar bajas o cambios de rol.
     * Si no hay sesión pero sí una cookie de "mantener la sesión iniciada" válida, se abre
     * una sesión nueva. public/index.php lo llama al principio de cada petición para que el
     * token CSRF no cambie a mitad del renderizado.
     */
    public static function user(): ?array
    {
        if (!self::$resolved) {
            self::$resolved = true;
            $id = $_SESSION['user_id'] ?? null;
            if (is_int($id)) {
                $user = User::find($id);
                if ($user !== null && (int) $user['is_active'] === 1 && $user['email_verified_at'] !== null) {
                    self::$user = $user;
                } else {
                    unset($_SESSION['user_id']);
                }
            }
            if (self::$user === null && session_status() === PHP_SESSION_ACTIVE) {
                $remembered = RememberMe::consume();
                if ($remembered !== null) {
                    self::login($remembered);
                }
            }
        }
        return self::$user;
    }

    public static function login(array $user, bool $remember = false): void
    {
        // Evita la fijación de sesión
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        Csrf::regenerate();
        self::$user = $user;
        self::$resolved = true;
        if ($remember) {
            RememberMe::issue((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        RememberMe::forget();
        $_SESSION = [];
        session_regenerate_id(true);
        Csrf::regenerate();
        self::$user = null;
        self::$resolved = true;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
                $_SESSION['_intended'] = current_path() . ($query ? '?' . $query : '');
            }
            flash('info', 'Inicia sesión para continuar.');
            redirect('/login');
        }
        return $user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
        return $user;
    }

    /** Ruta a la que volver tras iniciar sesión (solo rutas locales). */
    public static function pullIntended(): ?string
    {
        $path = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);
        if (!is_string($path) || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return null;
        }
        return $path;
    }

    public static function homeFor(array $user): string
    {
        return match ($user['role']) {
            'admin' => '/admin',
            'teacher' => '/profesor',
            default => '/mis-cursos',
        };
    }

    /** Administradores y el profesor asignado pueden ver el curso en modo vista previa. */
    public static function canManageCourse(?array $user, array $course): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user['role'] === 'admin') {
            return true;
        }
        return $user['role'] === 'teacher'
            && $course['teacher_id'] !== null
            && (int) $course['teacher_id'] === (int) $user['id'];
    }
}
