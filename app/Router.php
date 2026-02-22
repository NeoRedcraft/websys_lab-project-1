<?php

namespace App;

class Router
{
    private $routes = [];
    private $currentPath;
    private $currentMethod;

    public function __construct()
    {
        $this->currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->currentMethod = $_SERVER['REQUEST_METHOD'];
    }

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function patch($path, $handler)
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $this->pathToRegex($path),
            'handler' => $handler,
        ];
    }

    private function pathToRegex($path)
    {
        // Escape special regex characters except for { and }
        $path = preg_quote($path, '#');
        // Replace escaped braces with capture groups
        $path = preg_replace('/\\\{(\w+)\\\}/', '(?P<\1>[^/]+)', $path);
        // Use # as delimiter to avoid conflicts with forward slashes
        return '#^' . $path . '$#';
    }

    public function resolve()
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->currentMethod) {
                continue;
            }

            $matches = [];
            if (preg_match($route['pattern'], $this->currentPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Handle callable or string handler
                if (is_callable($route['handler'])) {
                    $output = call_user_func_array($route['handler'], $params);
                    echo $output;
                    return;
                } elseif (is_string($route['handler'])) {
                    [$controller, $method] = explode('@', $route['handler']);
                    $controllerClass = 'App\\Controllers\\' . $controller;
                    $instance = new $controllerClass();
                    $output = call_user_func_array([$instance, $method], [$params]);
                    echo $output;
                    return;
                }
            }
        }

        return $this->notFound();
    }

    private function notFound()
    {
        http_response_code(404);
        echo view('error/404');
    }
}
