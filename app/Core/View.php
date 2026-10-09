<?php

namespace App\Core;

class View
{
    protected static string $viewsPath = __DIR__ . '/../../views/';

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $viewFile = static::$viewsPath . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$viewFile}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = static::$viewsPath . str_replace('.', '/', $layout) . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout file not found: {$layoutFile}");
            }
            ob_start();
            require $layoutFile;
            return ob_get_clean();
        }

        return $content;
    }
}
