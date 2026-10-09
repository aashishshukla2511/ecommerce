<?php

namespace App\Helpers;

class Url
{
    public static function base(string $path = ''): string
    {
        $baseUrl = rtrim($_ENV['APP_BASE_URL'] ?? '', '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        $url = self::base($path);
        header("Location: {$url}");
        exit;
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
