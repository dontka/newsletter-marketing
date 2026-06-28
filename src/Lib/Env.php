<?php

class Env
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2) + [null, null]);
            if ($name === null) {
                continue;
            }

            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}
