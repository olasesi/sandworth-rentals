<?php

namespace App\Core;

final class Router
{
    /**
     * @var array
     */
    private $routes = array();

    public function get($name, callable $action)
    {
        $this->addRoute('GET', $name, $action);
    }

    public function post($name, callable $action)
    {
        $this->addRoute('POST', $name, $action);
    }

    public function addRoute($method, $name, callable $action)
    {
        if (! isset($this->routes[$method])) {
            $this->routes[$method] = array();
        }

        $this->routes[$method][$name] = $action;
    }

    public function dispatch($method, $name)
    {
        $route = isset($this->routes[$method][$name]) ? $this->routes[$method][$name] : null;

        if ($route === null && $method === 'GET' && isset($this->routes['GET']['home'])) {
            $route = $this->routes['GET']['home'];
        }

        if ($route === null) {
            http_response_code(404);
            echo 'Route not found.';

            return;
        }

        $route();
    }
}
