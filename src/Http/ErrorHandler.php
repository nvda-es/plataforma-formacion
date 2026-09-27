<?php
declare(strict_types=1);

namespace App\Http;

use App\App;
use Throwable;

/**
 * Convierte excepciones no capturadas en páginas de error accesibles.
 * Los detalles técnicos solo se muestran con app.debug activado.
 */
final class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        $status = $e instanceof HttpException ? $e->status : 500;
        if ($status >= 500) {
            error_log((string) $e);
        }
        $message = $e instanceof HttpException && $e->getMessage() !== ''
            ? $e->getMessage()
            : self::defaultMessage($status);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            view('errors/error', [
                'title' => self::title($status),
                'status' => $status,
                'message' => $message,
                'exception' => App::debug() && $status >= 500 ? $e : null,
            ], $status);
        } catch (Throwable $inner) {
            error_log((string) $inner);
            if (!headers_sent()) {
                http_response_code($status);
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo $message;
        }
    }

    private static function title(int $status): string
    {
        return match ($status) {
            400 => 'Solicitud no válida',
            403 => 'Acceso denegado',
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            default => 'Error del servidor',
        };
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            403 => 'No tienes permiso para acceder a esta página.',
            404 => 'La página que buscas no existe o se ha movido.',
            default => 'Se ha producido un error inesperado. Inténtalo de nuevo más tarde.',
        };
    }
}
