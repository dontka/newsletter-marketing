<?php

class DB
{
    private static ?\PDO $instance = null;

    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
            $config = self::loadConfig();
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['DB_HOST'],
                $config['DB_NAME'],
                $config['DB_CHARSET']
            );

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instance = new \PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
        }

        return self::$instance;
    }

    private static function loadConfig(): array
    {
        $dotenvPath = __DIR__ . '/../../.env';
        if (file_exists($dotenvPath)) {
            $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }

                [$name, $value] = array_map('trim', explode('=', $line, 2) + [null, null]);
                if ($name !== null) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                }
            }
        }

        return [
            'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
            'DB_NAME' => getenv('DB_NAME') ?: 'newsletter',
            'DB_USER' => getenv('DB_USER') ?: 'root',
            'DB_PASS' => getenv('DB_PASS') ?: '',
            'DB_CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ];
    }
}
