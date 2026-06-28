<?php

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method)
    {
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $route['path'] === $path) {
                return call_user_func($route['handler']);
            }
        }

        http_response_code(404);
        echo "404 Not Found";
        return null;
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }
}
