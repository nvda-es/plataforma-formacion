<?php
declare(strict_types=1);

use App\App;
use App\Auth;
use App\Csrf;
use App\Http\HttpException;
use App\Markdown;
use App\Models\User;
use App\Recaptcha;
use App\SecurityHeaders;

function config(string $key, mixed $default = null): mixed
{
    return App::config($key, $default);
}

/** Escapa cualquier valor para imprimirlo en HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL relativa a la raíz de la aplicación (tiene en cuenta el subdirectorio). */
function url(string $path = '/', array $query = []): string
{
    $url = App::basePath() . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }
    return $url;
}

/** URL absoluta basada en app.url (para correos). Nunca usa la cabecera Host. */
function absolute_url(string $path = '/', array $query = []): string
{
    $origin = (string) preg_replace('#^(https?://[^/]+).*$#i', '$1', (string) config('app.url', ''));
    return $origin . url($path, $query);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url('/assets/' . $path, ['v' => $version]);
}

/** Ruta actual relativa a la raíz de la aplicación, sin barra final. */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = App::basePath();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    return $path === '/' ? '/' : rtrim($path, '/');
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query), true, 303);
    exit;
}

/** Envía un archivo de texto para descargar. El nombre se reduce a caracteres seguros. */
function download(string $filename, string $content, string $contentType = 'text/markdown'): never
{
    $safe = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
    header('Content-Type: ' . $contentType . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safe . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($messages) ? $messages : [];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function csp_nonce(): string
{
    return SecurityHeaders::nonce();
}

/** Atributos de un formulario protegido con reCAPTCHA v3 (vacío si está desactivado). */
function recaptcha_attrs(string $action): string
{
    if (!Recaptcha::enabled()) {
        return '';
    }
    return ' data-recaptcha-action="' . e($action) . '" data-recaptcha-sitekey="' . e(Recaptcha::siteKey()) . '"';
}

/** Valor de texto enviado por POST (nunca un array). */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

/** Valor de texto de la query string (nunca un array). */
function query_param(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function checkbox(string $key): bool
{
    return ($_POST[$key] ?? null) === '1';
}

/**
 * IP del cliente. Si security.trust_forwarded_for está activo, se usa la primera IP válida
 * de X-Forwarded-For (el cliente original); el entorno de despliegue garantiza que esa
 * cabecera la fija un proxy de confianza. Si no hay ninguna válida, se usa REMOTE_ADDR.
 */
function client_ip(): string
{
    if ((bool) config('security.trust_forwarded_for', true)) {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        foreach (explode(',', is_string($forwarded) ? $forwarded : '') as $candidate) {
            $ip = normalize_ip(trim($candidate));
            if ($ip !== null) {
                return $ip;
            }
        }
    }
    return normalize_ip((string) ($_SERVER['REMOTE_ADDR'] ?? '')) ?? '0.0.0.0';
}

/** Valida una IP y quita el puerto que añaden algunos proxies ("1.2.3.4:5678", "[2001:db8::1]:443"). */
function normalize_ip(string $value): ?string
{
    if (preg_match('/^\[([0-9a-f:.]+)\](?::\d+)?$/i', $value, $m)) {
        $value = $m[1];
    } elseif (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $value, $m)) {
        $value = $m[1];
    }
    return filter_var($value, FILTER_VALIDATE_IP) !== false ? $value : null;
}

function current_user(): ?array
{
    return Auth::user();
}

function render_template(string $__template, array $__data = []): string
{
    $__file = BASE_PATH . '/templates/' . $__template . '.php';
    if (!is_file($__file)) {
        throw new RuntimeException('Plantilla no encontrada: ' . $__template);
    }
    extract($__data, EXTR_SKIP);
    ob_start();
    try {
        include $__file;
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
    return (string) ob_get_clean();
}

function partial(string $name, array $data = []): string
{
    return render_template('partials/' . $name, $data);
}

/** Renderiza una plantilla dentro del layout y la envía. */
function view(string $template, array $data = [], int $status = 200): void
{
    $data += ['errors' => [], 'old' => []];
    $content = render_template($template, $data);
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo render_template('layout', ['content' => $content] + $data);
}

function markdown(?string $text, int $topLevel = 2): string
{
    return Markdown::toHtml((string) $text, $topLevel);
}

function old(array $old, string $key, string $default = ''): string
{
    $value = $old[$key] ?? $default;
    return is_scalar($value) ? (string) $value : $default;
}

/** Atributos ARIA de un campo: aria-invalid y aria-describedby (ayuda + error). */
function field_attrs(array $errors, string $name, ?string $hintId = null): string
{
    $describedBy = [];
    if ($hintId !== null) {
        $describedBy[] = $hintId;
    }
    $attrs = '';
    if (isset($errors[$name])) {
        $describedBy[] = $name . '-error';
        $attrs .= ' aria-invalid="true"';
    }
    if ($describedBy !== []) {
        $attrs .= ' aria-describedby="' . e(implode(' ', $describedBy)) . '"';
    }
    return $attrs;
}

function field_error(array $errors, string $name): string
{
    if (!isset($errors[$name])) {
        return '';
    }
    return '<p class="field-error" id="' . e($name) . '-error"><span class="visually-hidden">Error: </span>'
        . e($errors[$name]) . '</p>';
}

function selected(bool $condition): string
{
    return $condition ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}

function format_date(?string $value, bool $withTime = true): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return (new DateTimeImmutable($value))->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
}

/** Fecha legible dentro de un elemento <time> con su valor ISO. */
function time_tag(?string $value, bool $withTime = true): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $date = new DateTimeImmutable($value);
    return '<time datetime="' . e($date->format(DATE_ATOM)) . '">' . e(format_date($value, $withTime)) . '</time>';
}

function role_label(string $role): string
{
    return User::ROLES[$role] ?? $role;
}

function submission_status_label(?string $status): string
{
    return match ($status) {
        'pending' => 'Pendiente de corrección',
        'approved' => 'Aprobado',
        'failed' => 'Suspendido',
        default => 'Sin entregar',
    };
}

/** Devuelve aria-current="page" si la ruta actual coincide con el prefijo. */
function nav_current(string $prefix): string
{
    $path = current_path();
    $match = $prefix === '/'
        ? $path === '/'
        : ($path === $prefix || str_starts_with($path, $prefix . '/'));
    return $match ? ' aria-current="page"' : '';
}
