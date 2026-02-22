<?php
declare(strict_types=1);

namespace App\Core;
class Router {
    private array $routes = [];
    /*
    |--------------------------------------------------------------------------
    | Enregistrer des routes GET et POST
    |--------------------------------------------------------------------------
    */
    public function get(string $uri, array $action, array $options = []): void
    {
        $this->addRoute('GET', $uri, $action, $options);
    }
    public function post(string $uri, array $action, array $options = []): void
    {
        $this->addRoute('POST', $uri, $action, $options);
    }
    private function addRoute(string $method, string $uri, array $action, array $options): void
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'options' => $options
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dispatch
    |--------------------------------------------------------------------------
    */
    public function dispatch(): void {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if (
                $route['uri'] === $requestUri &&
                $route['method'] === $requestMethod
            ) {
                $this->runMiddleware($route['options']);

                //[$controller, $method, $params] = $route['action'];
                $controller = $route['action'][0];
                $method = $route['action'][1];
                $params = $route['action'][2] ?? [];
                (new $controller())->$method(...$params);
                return;
            }
        }

        http_response_code(404);
        echo "Page non trouvée.";
    }

    private function runMiddleware(array $options): void {
        if (!isset($options['middleware'])) {
            return;
        }

        $middleware = $options['middleware'];

        if (str_starts_with($middleware, 'permission:')) {
            $permission = explode(':', $middleware)[1];
            \App\Middlewares\PermissionMiddleware::handle($permission);
        }

        if ($middleware === 'auth') {
            \App\Middlewares\AuthMiddleware::handle();
        }
    }
}
