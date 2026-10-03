<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Mailer;
use App\Models\User;
use App\Password;
use App\RateLimiter;
use App\Recaptcha;
use App\RememberMe;
use App\Tokens;
use App\Validator;

/**
 * Registro, verificación de correo, inicio/cierre de sesión y recuperación de contraseña.
 *
 * Para no revelar qué correos están registrados, las respuestas de registro,
 * reenvío de verificación y recuperación de contraseña son siempre las mismas.
 */
final class AuthController
{
    private const VERIFY_TTL = 172800; // 48 horas
    private const RESET_TTL = 3600;    // 1 hora

    public function showRegister(): void
    {
        $this->redirectIfAuthenticated();
        view('auth/register', ['title' => 'Crear una cuenta', 'recaptcha' => true]);
    }

    public function register(): void
    {
        $this->redirectIfAuthenticated();
        $old = [
            'name' => trim(input('name')),
            'email' => User::normalizeEmail(input('email')),
        ];
        $password = input('password');

        $v = new Validator($old);
        if (!Recaptcha::verify(input('g-recaptcha-response'), 'register')) {
            $v->addError('_form', Recaptcha::ERROR_MESSAGE);
        } elseif (RateLimiter::tooManyAttempts('register', client_ip(), 10, 3600)) {
            $v->addError('_form', 'Se han creado demasiadas cuentas desde esta conexión. Inténtalo de nuevo más tarde.');
        }
        $v->required('name', 'Nombre y apellidos')->maxLength('name', 'Nombre y apellidos', 120)
            ->required('email', 'Correo electrónico')->email('email', 'Correo electrónico')
            ->maxLength('email', 'Correo electrónico', 190);
        foreach (Password::validate($password, input('password_confirmation')) as $field => $message) {
            $v->addError($field, $message);
        }

        if ($v->fails()) {
            view('auth/register', [
                'title' => 'Crear una cuenta',
                'recaptcha' => true,
                'old' => $old,
                'errors' => $v->errors(),
            ], 422);
            return;
        }

        RateLimiter::hit('register', client_ip());
        $existing = User::findByEmail($old['email']);
        if ($existing !== null) {
            // Se avisa al titular en lugar de revelar en pantalla que la cuenta existe
            Mailer::send($existing['email'], $existing['name'], 'account_exists', [
                'name' => $existing['name'],
                'loginUrl' => absolute_url('/login'),
                'resetUrl' => absolute_url('/recuperar-contrasena'),
            ]);
        } else {
            try {
                $id = User::create($old['name'], $old['email'], Password::hash($password), 'student', false);
                $this->sendVerification($id, $old['name'], $old['email']);
            } catch (\PDOException $e) {
                if (!\App\DB::isDuplicateKey($e)) {
                    throw $e;
                }
            }
        }

        flash('success', 'Te hemos enviado un correo electrónico a ' . $old['email']
            . '. Abre el enlace que contiene para activar tu cuenta. Si no lo encuentras, revisa la carpeta de correo no deseado.');
        redirect('/login');
    }

    public function verify(): void
    {
        $userId = Tokens::consume(query_param('token'), Tokens::EMAIL_VERIFICATION);
        if ($userId === null) {
            view('auth/token_invalid', [
                'title' => 'Enlace de activación no válido',
                'message' => 'El enlace de activación no es válido, ya se ha utilizado o ha caducado.',
                'actionUrl' => '/reenviar-verificacion',
                'actionLabel' => 'Solicitar un nuevo enlace de activación',
            ], 400);
            return;
        }
        User::markVerified($userId);
        flash('success', 'Tu correo electrónico se ha verificado y tu cuenta ya está activa. Ya puedes iniciar sesión.');
        redirect('/login');
    }

    public function showLogin(): void
    {
        $this->redirectIfAuthenticated();
        view('auth/login', ['title' => 'Iniciar sesión', 'recaptcha' => true]);
    }

    public function login(): void
    {
        $this->redirectIfAuthenticated();
        $email = User::normalizeEmail(input('email'));
        $password = input('password');
        $ip = client_ip();

        $fail = static function (string $message, int $status = 422, bool $unverified = false) use ($email): void {
            view('auth/login', [
                'title' => 'Iniciar sesión',
                'recaptcha' => true,
                'old' => ['email' => $email, 'remember' => checkbox('remember')],
                'errors' => ['_form' => $message],
                'unverified' => $unverified,
            ], $status);
        };

        if (RateLimiter::tooManyAttempts('login_ip', $ip, 20, 900)
            || RateLimiter::tooManyAttempts('login_email', $email, 5, 900)) {
            $fail('Demasiados intentos fallidos. Espera 15 minutos antes de volver a intentarlo.', 429);
            return;
        }
        if (!Recaptcha::verify(input('g-recaptcha-response'), 'login')) {
            $fail(Recaptcha::ERROR_MESSAGE);
            return;
        }
        if ($email === '' || $password === '') {
            $fail('Introduce tu correo electrónico y tu contraseña.');
            return;
        }

        $user = mb_strlen($password) <= Password::MAX_LENGTH ? User::findByEmail($email) : null;
        if ($user !== null && $user['password_hash'] !== null) {
            $valid = password_verify($password, $user['password_hash']);
        } else {
            // Iguala el tiempo de respuesta para no revelar si el correo existe
            Password::hash(substr($password, 0, Password::MAX_LENGTH));
            $valid = false;
        }

        if (!$valid) {
            RateLimiter::hit('login_ip', $ip);
            RateLimiter::hit('login_email', $email);
            $fail('El correo electrónico o la contraseña no son correctos.');
            return;
        }
        if ((int) $user['is_active'] !== 1) {
            $fail('Tu cuenta está desactivada. Ponte en contacto con la administración de la plataforma.', 403);
            return;
        }
        if ($user['email_verified_at'] === null) {
            $fail('Todavía no has verificado tu correo electrónico. Abre el enlace que te enviamos al registrarte para activar la cuenta.', 403, true);
            return;
        }

        if (Password::needsRehash($user['password_hash'])) {
            User::updatePassword((int) $user['id'], Password::hash($password));
        }
        RateLimiter::clear('login_email', $email);
        Auth::login($user, checkbox('remember'));
        flash('success', 'Hola, ' . $user['name'] . '. Has iniciado sesión.');

        $intended = Auth::pullIntended();
        redirect($intended ?? Auth::homeFor($user));
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Has cerrado la sesión.');
        redirect('/');
    }

    public function showResend(): void
    {
        view('auth/resend', ['title' => 'Reenviar enlace de activación', 'recaptcha' => true]);
    }

    public function resend(): void
    {
        $old = ['email' => User::normalizeEmail(input('email'))];
        $v = $this->validateEmailRequest($old, 'resend_verification', 'resend');
        if ($v->fails()) {
            view('auth/resend', [
                'title' => 'Reenviar enlace de activación',
                'recaptcha' => true,
                'old' => $old,
                'errors' => $v->errors(),
            ], 422);
            return;
        }

        $user = User::findByEmail($old['email']);
        if ($user !== null && (int) $user['is_active'] === 1 && $user['email_verified_at'] === null) {
            $this->sendVerification((int) $user['id'], $user['name'], $user['email']);
        }
        flash('success', 'Si hay una cuenta pendiente de activar con ese correo, te hemos enviado un nuevo enlace de activación.');
        redirect('/login');
    }

    public function showForgot(): void
    {
        view('auth/forgot', ['title' => 'Recuperar la contraseña', 'recaptcha' => true]);
    }

    public function forgot(): void
    {
        $old = ['email' => User::normalizeEmail(input('email'))];
        $v = $this->validateEmailRequest($old, 'forgot_password', 'forgot');
        if ($v->fails()) {
            view('auth/forgot', [
                'title' => 'Recuperar la contraseña',
                'recaptcha' => true,
                'old' => $old,
                'errors' => $v->errors(),
            ], 422);
            return;
        }

        $user = User::findByEmail($old['email']);
        if ($user !== null && (int) $user['is_active'] === 1) {
            $token = Tokens::create((int) $user['id'], Tokens::PASSWORD_RESET, self::RESET_TTL);
            Mailer::send($user['email'], $user['name'], 'password_reset', [
                'name' => $user['name'],
                'url' => absolute_url('/restablecer-contrasena', ['token' => $token]),
                'minutes' => (int) (self::RESET_TTL / 60),
            ]);
        }
        flash('success', 'Si hay una cuenta con ese correo, te hemos enviado un enlace para crear una contraseña nueva. El enlace caduca en 1 hora.');
        redirect('/login');
    }

    public function showReset(): void
    {
        $token = query_param('token');
        if (!Tokens::isValid($token, Tokens::PASSWORD_RESET)) {
            $this->invalidResetToken();
            return;
        }
        view('auth/reset', ['title' => 'Crear una contraseña nueva', 'token' => $token]);
    }

    public function reset(): void
    {
        $token = input('token');
        if (!Tokens::isValid($token, Tokens::PASSWORD_RESET)) {
            $this->invalidResetToken();
            return;
        }
        $password = input('password');
        $errors = Password::validate($password, input('password_confirmation'));
        if ($errors !== []) {
            view('auth/reset', ['title' => 'Crear una contraseña nueva', 'token' => $token, 'errors' => $errors], 422);
            return;
        }

        $userId = Tokens::consume($token, Tokens::PASSWORD_RESET);
        $user = $userId !== null ? User::find($userId) : null;
        if ($user === null) {
            $this->invalidResetToken();
            return;
        }
        User::updatePassword($userId, Password::hash($password));
        // Quien usa el enlace demuestra que controla el correo
        User::markVerified($userId);
        // Una contraseña nueva cierra las sesiones recordadas en otros navegadores
        RememberMe::forgetUser($userId);
        RateLimiter::clear('login_email', $user['email']);

        flash('success', 'Tu contraseña se ha guardado. Ya puedes iniciar sesión con ella.');
        redirect('/login');
    }

    private function validateEmailRequest(array $old, string $recaptchaAction, string $bucket): Validator
    {
        $v = new Validator($old);
        if (!Recaptcha::verify(input('g-recaptcha-response'), $recaptchaAction)) {
            $v->addError('_form', Recaptcha::ERROR_MESSAGE);
        } elseif (RateLimiter::tooManyAttempts($bucket, client_ip(), 5, 3600)) {
            $v->addError('_form', 'Has hecho demasiadas solicitudes. Inténtalo de nuevo dentro de una hora.');
        }
        $v->required('email', 'Correo electrónico')->email('email', 'Correo electrónico');
        if (!$v->fails()) {
            RateLimiter::hit($bucket, client_ip());
        }
        return $v;
    }

    private function sendVerification(int $userId, string $name, string $email): void
    {
        $token = Tokens::create($userId, Tokens::EMAIL_VERIFICATION, self::VERIFY_TTL);
        Mailer::send($email, $name, 'verify_email', [
            'name' => $name,
            'url' => absolute_url('/verificar-email', ['token' => $token]),
            'hours' => (int) (self::VERIFY_TTL / 3600),
        ]);
    }

    private function invalidResetToken(): void
    {
        view('auth/token_invalid', [
            'title' => 'Enlace no válido',
            'message' => 'El enlace para crear la contraseña no es válido, ya se ha utilizado o ha caducado.',
            'actionUrl' => '/recuperar-contrasena',
            'actionLabel' => 'Solicitar un enlace nuevo',
        ], 400);
    }

    private function redirectIfAuthenticated(): void
    {
        $user = Auth::user();
        if ($user !== null) {
            redirect(Auth::homeFor($user));
        }
    }
}
