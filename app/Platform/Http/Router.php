<?php

declare(strict_types=1);

namespace App\Platform\Http;

use App\Platform\Auth\AuthService;

final class Router
{
    private array $routes = [];
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function get(string $name, array $handler, bool $requiresAuth = false, $role = null): void
    {
        $this->routes['GET'][$name] = compact('handler', 'requiresAuth', 'role');
    }

    public function post(string $name, array $handler, bool $requiresAuth = false, $role = null): void
    {
        $this->routes['POST'][$name] = compact('handler', 'requiresAuth', 'role');
    }

    public function dispatch(string $route): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $definition = $this->routes[$method][$route] ?? null;

        if ($definition === null) {
            http_response_code(404);
            echo 'Route not found.';
            return;
        }

        if ($definition['requiresAuth'] && !$this->auth->check()) {
            redirect('login');
        }

        if ($definition['role'] !== null && !$this->auth->hasAnyRole((array) $definition['role'])) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        [$class, $methodName] = $definition['handler'];
        (new $class())->{$methodName}();
    }
}
