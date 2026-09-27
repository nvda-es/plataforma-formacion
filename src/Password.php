<?php
declare(strict_types=1);

namespace App;

/**
 * Política y hash de contraseñas (Argon2id si está disponible; si no, bcrypt).
 */
final class Password
{
    public const MIN_LENGTH = 10;
    public const MAX_LENGTH = 128;

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }

    /**
     * Valida una contraseña nueva y su confirmación.
     *
     * @return array<string, string> errores por campo (password, password_confirmation)
     */
    public static function validate(string $password, string $confirmation): array
    {
        $errors = [];
        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH) {
            $errors['password'] = 'La contraseña debe tener al menos ' . self::MIN_LENGTH . ' caracteres.';
        } elseif ($length > self::MAX_LENGTH) {
            $errors['password'] = 'La contraseña no puede superar los ' . self::MAX_LENGTH . ' caracteres.';
        } elseif (self::algorithm() === PASSWORD_BCRYPT && strlen($password) > 72) {
            $errors['password'] = 'La contraseña es demasiado larga: usa como máximo 72 bytes (menos caracteres acentuados o símbolos).';
        } elseif (preg_match('/^(.)\1+$/u', $password)) {
            $errors['password'] = 'La contraseña no puede ser un único carácter repetido.';
        }

        if (!isset($errors['password']) && !hash_equals($password, $confirmation)) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }
        return $errors;
    }

    private static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
