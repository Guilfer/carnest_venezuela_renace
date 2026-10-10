<?php

namespace App\Support;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::partial($view, $data);

        if ($layout === null) {
            return $content;
        }

        $data['content'] = $content;
        return self::partial($layout, $data);
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = \App\Kernel::root() . '/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
