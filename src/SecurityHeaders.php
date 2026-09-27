<?php
declare(strict_types=1);

namespace App;

/**
 * Cabeceras de seguridad HTTP, incluida una Content-Security-Policy con nonce
 * compatible con reCAPTCHA v3.
 */
final class SecurityHeaders
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(16));
    }

    public static function send(): void
    {
        $nonce = self::nonce();
        $csp = implode('; ', [
            "default-src 'self'",
            // 'strict-dynamic' permite que el script de reCAPTCHA (con nonce) cargue sus dependencias
            "script-src 'nonce-{$nonce}' 'strict-dynamic' 'self' https:",
            // reCAPTCHA inserta estilos en línea en su insignia
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "frame-src https://www.google.com https://recaptcha.google.com",
            "connect-src 'self' https://www.google.com",
            "object-src 'none'",
            "base-uri 'none'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        header_remove('X-Powered-By');
        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cache-Control: no-store, private');
        if (App::isHttps() && App::config('security.hsts', false)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
