<?php

namespace App;

use App\Support\Database;

final class Kernel
{
    private static array $config = [];
    private static bool $booted = false;

    public static function boot(array $config): void
    {
        self::$config = $config;
        self::$booted = true;

        date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

        $debug = (bool) ($config['app']['debug'] ?? false);
        error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', $debug ? '1' : '0');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        Database::init($config['db'] ?? []);
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }

        $value = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function root(): string
    {
        return dirname(__DIR__);
    }

    public static function storage(string $path = ''): string
    {
        return self::root() . '/storage' . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public static function templates(string $path = ''): string
    {
        return self::root() . '/templates' . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}
