<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    /**
     * @var array<string,array<string,callable>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $path = $request->path();

        if (!isset($this->routes[$method][$path])) {
            Response::abort(404);
        }

        return ($this->routes[$method][$path])($request);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[$method][$normalized === '/' ? '/' : rtrim($normalized, '/')] = $handler;
    }
}
