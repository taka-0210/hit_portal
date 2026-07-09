<?php

declare(strict_types=1);

namespace App\Platform\View;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require self::resolve($view);
        $content = ob_get_clean();

        require BASE_PATH . '/resources/layouts/' . $layout . '.php';
    }

    private static function resolve(string $view): string
    {
        $modulePath = BASE_PATH . '/app/Modules/' . $view . '.php';
        if (is_file($modulePath)) {
            return $modulePath;
        }

        return BASE_PATH . '/resources/views/' . $view . '.php';
    }
}
