<?php
declare(strict_types=1);

namespace App;

use App\Http\HttpException;

/**
 * Enrutador sencillo. Los parámetros {id} solo aceptan dígitos y {slug} solo
 * minúsculas, dígitos y guiones. Todas las peticiones POST validan el token CSRF.
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    private function add(array $methods, string $pattern, array $handler): void
    {
        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            static fn (array $m): string => $m[1] === 'slug' ? '([a-z0-9]+(?:-[a-z0-9]+)*)' : '(\d{1,10})',
            $pattern
        );
        $this->routes[] = ['methods' => $methods, 'regex' => '#^' . $regex . '$#', 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = App::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if (!in_array($method, $route['methods'], true)) {
                array_push($allowed, ...$route['methods']);
                continue;
            }
            if ($method === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
                throw new HttpException(
                    400,
                    'El formulario ha caducado o no es válido. Vuelve a cargar la página e inténtalo de nuevo.'
                );
            }
            array_shift($matches);
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$matches);
            return;
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            throw new HttpException(405, 'Método no permitido.');
        }
        throw new HttpException(404);
    }
}
