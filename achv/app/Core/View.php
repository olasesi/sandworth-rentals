<?php

namespace App\Core;

final class View
{
    /**
     * @var array
     */
    private static $shared = array();

    public static function share($key, $value)
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array $data
     */
    public static function render($view, array $data = array())
    {
        $payload = array_merge(self::$shared, $data);

        extract($payload, EXTR_SKIP);

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        $layoutPath = dirname(__DIR__) . '/Views/layout.php';

        if (! file_exists($viewPath)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        require $layoutPath;
    }
}
