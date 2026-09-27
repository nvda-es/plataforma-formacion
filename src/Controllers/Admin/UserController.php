<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\DB;
use App\Mailer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Password;
use App\Progress;
use App\Tokens;
use App\Validator;

/**
 * Alta y gestión manual de alumnos, profesores y administradores.
 */
final class UserController
{
    private const INVITE_TTL = 604800; // 7 días
    private const VERIFY_TTL = 172800; // 48 horas

    public function index(): void
    {
        Auth::requireRole('admin');
        $role = query_param('rol');
        $search = trim(query_param('q'));
        $result = User::paginate($role, mb_substr($search, 0, 100), max(1, (int) query_param('pagina', '1')));

        view('admin/users/index', [
            'title' => 'Usuarios',
            'result' => $result,
            'role' => $role,
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        view('admin/users/form', [
            'title' => 'Nuevo usuario',
            'user' => null,
            'old' => ['role' => query_param('rol', 'student'), 'verified' => true, 'is_active' => true],
        ]);
    }

    public function store(): void
    {
        $admin = Auth::requireRole('admin');
        [$data, $errors, $password] = $this->validated(null, $admin);
        if ($errors !== []) {
            view('admin/users/form', ['title' => 'Nuevo usuario', 'user' => null, 'old' => $data, 'errors' => $errors], 422);
            return;
        }

        try {
            $id = User::create(
                $data['name'],
                $data['email'],
                $password !== '' ? Password::hash($password) : null,
                $data['role'],
                $data['verified']
            );
        } catch (\PDOException $e) {
            if (!DB::isDuplicateKey($e)) {
                throw $e;
            }
            $errors = ['email' => 'Ya existe un usuario con ese correo electrónico.'];
            view('admin/users/form', ['title' => 'Nuevo usuario', 'user' => null, 'old' => $data, 'errors' => $errors], 422);
            return;
        }

        $notice = $this->notifyNewUser(User::find($id), $password !== '');
        flash('success', 'Usuario «' . $data['name'] . '» creado. ' . $notice);
        redirect('/admin/usuarios/' . $id . '/editar');
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $user = User::find((int) $id) ?? abort(404, 'El usuario no existe.');
        $this->renderEdit($user, [
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'verified' => $user['email_verified_at'] !== null,
            'is_active' => (int) $user['is_active'] === 1,
        ]);
    }

    public function update(string $id): void
    {
        $admin = Auth::requireRole('admin');
        $user = User::find((int) $id) ?? abort(404, 'El usuario no existe.');
        [$data, $errors, $password] = $this->validated($user, $admin);
        if ($errors !== []) {
            $this->renderEdit($user, $data, $errors, 422);
            return;
        }

        $verifiedAt = $data['verified'] ? ($user['email_verified_at'] ?? DB::now()) : null;
        User::update((int) $user['id'], $data['name'], $data['email'], $data['role'], $data['is_active'], $verifiedAt);
        if ($password !== '') {
            User::updatePassword((int) $user['id'], Password::hash($password));
        }
        flash('success', 'Los cambios de «' . $data['name'] . '» se han guardado.');
        redirect('/admin/usuarios/' . $user['id'] . '/editar');
    }

    /** Envía al usuario un enlace para establecer (o restablecer) su contraseña. */
    public function sendAccess(string $id): void
    {
        Auth::requireRole('admin');
        $user = User::find((int) $id) ?? abort(404, 'El usuario no existe.');
        $this->sendInvitation($user);
        flash('success', 'Se ha enviado a ' . $user['email'] . ' un enlace para establecer su contraseña (válido 7 días).');
        redirect('/admin/usuarios/' . $user['id'] . '/editar');
    }

    public function enroll(string $id): void
    {
        Auth::requireRole('admin');
        $user = User::find((int) $id) ?? abort(404, 'El usuario no existe.');
        $course = Course::find((int) input('course_id'));
        if ($user['role'] !== 'student') {
            flash('error', 'Solo se puede matricular a usuarios con rol de alumno.');
        } elseif ($course === null) {
            flash('error', 'Selecciona un curso para matricular al alumno.');
        } elseif (Enrollment::enroll((int) $user['id'], (int) $course['id'])) {
            // Por si el alumno ya había completado todo el contenido en una matrícula anterior
            Progress::checkCompletion((int) $user['id'], (int) $course['id']);
            flash('success', $user['name'] . ' se ha matriculado en «' . $course['title'] . '».');
        } else {
            flash('info', $user['name'] . ' ya estaba matriculado en «' . $course['title'] . '».');
        }
        redirect('/admin/usuarios/' . $user['id'] . '/editar#matriculas');
    }

    public function unenroll(string $id, string $courseId): void
    {
        Auth::requireRole('admin');
        $user = User::find((int) $id) ?? abort(404, 'El usuario no existe.');
        $course = Course::find((int) $courseId) ?? abort(404, 'El curso no existe.');
        Enrollment::remove((int) $user['id'], (int) $course['id']);
        flash('success', $user['name'] . ' se ha dado de baja de «' . $course['title'] . '».');
        redirect('/admin/usuarios/' . $user['id'] . '/editar#matriculas');
    }

    /**
     * @return array{0: array, 1: array, 2: string} datos, errores y contraseña nueva (o cadena vacía)
     */
    private function validated(?array $existing, array $admin): array
    {
        $data = [
            'name' => trim(input('name')),
            'email' => User::normalizeEmail(input('email')),
            'role' => input('role'),
            'verified' => checkbox('verified'),
            'is_active' => $existing === null ? true : checkbox('is_active'),
        ];

        $v = (new Validator($data))
            ->required('name', 'Nombre y apellidos')->maxLength('name', 'Nombre y apellidos', 120)
            ->required('email', 'Correo electrónico')->email('email', 'Correo electrónico')
            ->maxLength('email', 'Correo electrónico', 190)
            ->in('role', 'Rol', array_keys(User::ROLES));

        $other = $data['email'] !== '' ? User::findByEmail($data['email']) : null;
        if ($other !== null && ($existing === null || (int) $other['id'] !== (int) $existing['id'])) {
            $v->addError('email', 'Ya existe un usuario con ese correo electrónico.');
        }

        $password = input('password');
        if ($password !== '' || input('password_confirmation') !== '') {
            foreach (Password::validate($password, input('password_confirmation')) as $field => $message) {
                $v->addError($field, $message);
            }
        }

        // Evita que un administrador se bloquee a sí mismo
        if ($existing !== null && (int) $existing['id'] === (int) $admin['id']) {
            if ($data['role'] !== 'admin') {
                $v->addError('role', 'No puedes quitarte a ti mismo el rol de administrador.');
            }
            if (!$data['is_active']) {
                $v->addError('is_active', 'No puedes desactivar tu propia cuenta.');
            }
        }
        return [$data, $v->errors(), $password];
    }

    private function renderEdit(array $user, array $old, array $errors = [], int $status = 200): void
    {
        $enrollments = [];
        $availableCourses = [];
        if ($user['role'] === 'student') {
            $enrollments = Enrollment::forUser((int) $user['id']);
            foreach ($enrollments as &$enrollment) {
                $enrollment['progress'] = Progress::summary((int) $user['id'], (int) $enrollment['course_id']);
            }
            unset($enrollment);
            $enrolledIds = array_map('intval', array_column($enrollments, 'course_id'));
            $availableCourses = array_values(array_filter(
                Course::all(),
                static fn (array $c): bool => !in_array((int) $c['id'], $enrolledIds, true)
            ));
        }

        view('admin/users/form', [
            'title' => 'Editar usuario: ' . $user['name'],
            'user' => $user,
            'old' => $old,
            'errors' => $errors,
            'enrollments' => $enrollments,
            'availableCourses' => $availableCourses,
        ], $status);
    }

    /** Correo de bienvenida según cómo se haya creado la cuenta. Devuelve un aviso para el administrador. */
    private function notifyNewUser(array $user, bool $hasPassword): string
    {
        if (!$hasPassword) {
            $this->sendInvitation($user);
            return 'Se le ha enviado un correo para que establezca su contraseña.';
        }
        if ($user['email_verified_at'] === null) {
            $token = Tokens::create((int) $user['id'], Tokens::EMAIL_VERIFICATION, self::VERIFY_TTL);
            Mailer::send($user['email'], $user['name'], 'verify_email', [
                'name' => $user['name'],
                'url' => absolute_url('/verificar-email', ['token' => $token]),
                'hours' => (int) (self::VERIFY_TTL / 3600),
            ]);
            return 'Se le ha enviado un correo para que verifique su dirección.';
        }
        Mailer::send($user['email'], $user['name'], 'account_created', [
            'name' => $user['name'],
            'roleLabel' => role_label($user['role']),
            'setPasswordUrl' => null,
            'loginUrl' => absolute_url('/login'),
            'days' => 0,
        ]);
        return 'Se le ha enviado un correo de bienvenida.';
    }

    private function sendInvitation(array $user): void
    {
        $token = Tokens::create((int) $user['id'], Tokens::PASSWORD_RESET, self::INVITE_TTL);
        Mailer::send($user['email'], $user['name'], 'account_created', [
            'name' => $user['name'],
            'roleLabel' => role_label($user['role']),
            'setPasswordUrl' => absolute_url('/restablecer-contrasena', ['token' => $token]),
            'loginUrl' => absolute_url('/login'),
            'days' => (int) (self::INVITE_TTL / 86400),
        ]);
    }
}
