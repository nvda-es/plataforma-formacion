<?php
declare(strict_types=1);

namespace App;

/**
 * Lectura segura de archivos Markdown subidos. El archivo nunca se guarda en disco:
 * solo se lee su contenido como texto UTF-8.
 */
final class Upload
{
    public const MAX_BYTES = 1048576; // 1 MB

    /**
     * @return string|null contenido del archivo, o null si no se ha enviado ninguno o hay error
     */
    public static function markdown(string $field, ?string &$error): ?string
    {
        $error = null;
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
            return null;
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            $error = 'No se ha podido subir el archivo. Comprueba que no supera 1 MB e inténtalo de nuevo.';
            return null;
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            $error = 'El archivo no puede superar 1 MB.';
            return null;
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['md', 'markdown', 'txt'], true)) {
            $error = 'El archivo debe tener extensión .md, .markdown o .txt.';
            return null;
        }
        $content = file_get_contents((string) $file['tmp_name']);
        if ($content === false || str_contains($content, "\0") || !mb_check_encoding($content, 'UTF-8')) {
            $error = 'El archivo debe ser de texto y estar codificado en UTF-8.';
            return null;
        }
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}
