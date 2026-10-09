<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        $uri = $request->getUri();
        $method = $request->getMethod();

        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $pathMatched = true;

                if ($route['method'] !== $method) {
                    continue;
                }

                // CSRF Validation for POST requests
                if ($method === 'POST') {
                    $token = $request->post('_csrf_token');
                    if (!Csrf::validate($token)) {
                        throw new HttpException(403, "Invalid CSRF token.");
                    }
                }

                // Execute middleware
                foreach ($route['middleware'] as $mw) {
                    if (is_string($mw) && class_exists($mw)) {
                        $mwInstance = new $mw();
                        $mwInstance->handle($request);
                    } elseif (is_callable($mw)) {
                        $mw($request);
                    }
                }

                // Filter named parameters from regex matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Execute handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$controllerClass, $action] = $handler;
                    $controller = new $controllerClass();
                    $result = call_user_func_array([$controller, $action], array_merge([$request], array_values($params)));
                } elseif (is_callable($handler)) {
                    $result = call_user_func_array($handler, array_merge([$request], array_values($params)));
                } else {
                    throw new HttpException(500, "Invalid route handler.");
                }

                if ($result instanceof Response) {
                    return $result;
                }

                $response = new Response();
                if (is_string($result)) {
                    $response->setContent($result);
                }
                return $response;
            }
        }

        if ($pathMatched) {
            throw new HttpException(405, "Method {$method} not allowed for {$uri}.");
        }

        throw new HttpException(404, "Page not found: {$uri}");
    }
}
