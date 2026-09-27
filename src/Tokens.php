<?php
declare(strict_types=1);

namespace App;

/**
 * Tokens aleatorios de un solo uso. En la base de datos solo se guarda su hash.
 */
final class Tokens
{
    public const EMAIL_VERIFICATION = 'email_verification';
    public const PASSWORD_RESET = 'password_reset';

    /** Crea un token nuevo e invalida los anteriores del mismo tipo para ese usuario. */
    public static function create(int $userId, string $type, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        DB::transaction(static function () use ($userId, $type, $ttlSeconds, $token): void {
            DB::exec('DELETE FROM user_tokens WHERE user_id = ? AND type = ?', [$userId, $type]);
            DB::insert(
                'INSERT INTO user_tokens (user_id, type, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?)',
                [$userId, $type, hash('sha256', $token), date('Y-m-d H:i:s', time() + $ttlSeconds), DB::now()]
            );
        });
        return $token;
    }

    public static function isValid(string $token, string $type): bool
    {
        return self::findValid($token, $type) !== null;
    }

    /** Consume el token y devuelve el id del usuario, o null si no es válido. */
    public static function consume(string $token, string $type): ?int
    {
        $row = self::findValid($token, $type);
        if ($row === null) {
            return null;
        }
        // El borrado condicional garantiza un único uso aunque haya peticiones simultáneas
        $deleted = DB::exec('DELETE FROM user_tokens WHERE id = ?', [$row['id']]);
        return $deleted === 1 ? (int) $row['user_id'] : null;
    }

    private static function findValid(string $token, string $type): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return DB::one(
            'SELECT id, user_id FROM user_tokens WHERE token_hash = ? AND type = ? AND expires_at > ?',
            [hash('sha256', $token), $type, DB::now()]
        );
    }
}
