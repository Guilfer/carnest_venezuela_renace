<?php

namespace App\Support;

use App\Kernel;

final class Layout
{
    private static ?array $data = null;

    public static function all(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $defaults = require Kernel::root() . '/config/layout.php';
        $override = Kernel::storage('layout.json');

        if (is_file($override)) {
            $custom = json_decode((string) file_get_contents($override), true);
            if (is_array($custom)) {
                $defaults = self::merge($defaults, $custom);
            }
        }

        return self::$data = $defaults;
    }

    public static function face(string $face): array
    {
        $all = self::all();
        return $all['faces'][$face] ?? ['background' => null, 'elements' => []];
    }

    public static function save(array $layout): void
    {
        $file = Kernel::storage('layout.json');
        file_put_contents($file, json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::$data = null;
    }

    public static function reset(): void
    {
        $file = Kernel::storage('layout.json');
        if (is_file($file)) {
            unlink($file);
        }
        self::$data = null;
    }

    private static function merge(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                if (array_is_list($value) && array_is_list($base[$key])) {
                    $base[$key] = self::mergeElements($base[$key], $value);
                } elseif (!array_is_list($value)) {
                    $base[$key] = self::merge($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    private static function mergeElements(array $baseItems, array $overItems): array
    {
        $baseById = [];
        foreach ($baseItems as $item) {
            $baseById[$item['id'] ?? '__' . count($baseById)] = $item;
        }

        $result = [];
        foreach ($overItems as $item) {
            $key = $item['id'] ?? '__' . count($result);
            $result[] = isset($baseById[$key]) && is_array($baseById[$key]) && is_array($item)
                ? self::merge($baseById[$key], $item)
                : $item;
        }
        return $result;
    }
}
