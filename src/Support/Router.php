<?php

namespace App\Support;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, bool $auth = true, bool $csrf = true): self
    {
        return $this->add('GET', $path, $handler, $auth, $csrf);
    }

    public function post(string $path, callable|array $handler, bool $auth = true, bool $csrf = true): self
    {
        return $this->add('POST', $path, $handler, $auth, $csrf);
    }

    public function add(string $method, string $path, callable|array $handler, bool $auth = true, bool $csrf = true): self
    {
        $path = '/' . ltrim($path, '/');
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $this->compile($path),
            'handler' => $handler,
            'auth' => $auth,
            'csrf' => $csrf,
        ];
        return $this;
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['auth']) {
                Auth::requireLogin();
            }

            if ($method === 'POST' && $route['csrf'] && !csrf_verify(Request::input('_token'))) {
                abort(419, 'La sesión expiró o el token de seguridad es inválido. Vuelva a intentarlo.');
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->call($route['handler'], $params);
            return;
        }

        abort(404);
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->{$method}(...array_values($params));
            return;
        }

        $handler(...array_values($params));
    }
}
