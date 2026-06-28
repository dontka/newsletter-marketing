<?php

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/View.php';

class Auth
{
    public static function getCurrentUser(): ?array
    {
        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        $accessToken = $_SESSION['afiazone_access_token'] ?? null;
        if (!$accessToken) {
            return null;
        }

        $user = self::getUserByAccessToken($accessToken);
        if ($user !== null) {
            $_SESSION['user'] = $user;
        }

        return $user;
    }

    public static function requireLogin(): void
    {
        if (self::getCurrentUser() === null) {
            header('Location: /admin/login');
            exit;
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $user = self::getCurrentUser();
        if ($user === null || !in_array($user['role'] ?? 'admin', $roles, true)) {
            View::render('message', ['message' => 'Accès refusé. Rôle insuffisant.']);
            exit;
        }
    }

    public static function ensureUserExists(array $userInfo, string $accessToken): array
    {
        self::ensureUsersTableExists();

        $afiazoneId = trim((string) ($userInfo['id'] ?? $userInfo['user_id'] ?? $userInfo['afiazone_id'] ?? $userInfo['user_id'] ?? ''));
        $email = trim((string) ($userInfo['email'] ?? $userInfo['user_email'] ?? $userInfo['mail'] ?? ''));
        $name = trim((string) ($userInfo['name'] ?? $userInfo['user_name'] ?? $userInfo['fullname'] ?? ''));

        if ($afiazoneId === '' && $email !== '') {
            $afiazoneId = $email;
        }

        if ($afiazoneId === '' || $email === '') {
            return [];
        }

        $now = date('Y-m-d H:i:s');
        $pdo = DB::getConnection();

        $stmt = $pdo->prepare('
            INSERT INTO users (afiazone_id, email, name, role, access_token, last_login, created_at, updated_at)
            VALUES (:afiazone_id, :email, :name, :role, :access_token, :last_login, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                name = VALUES(name),
                access_token = VALUES(access_token),
                last_login = VALUES(last_login),
                updated_at = VALUES(updated_at)
        ');

        $stmt->execute([
            'afiazone_id' => $afiazoneId,
            'email' => $email,
            'name' => $name,
            'role' => 'admin',
            'access_token' => $accessToken,
            'last_login' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $user = self::getUserByAfiazoneId($afiazoneId);
        if ($user !== null) {
            $_SESSION['user'] = $user;
        }

        return $user ?? [];
    }

    private static function ensureUsersTableExists(): void
    {
        $pdo = DB::getConnection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            afiazone_id VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            role ENUM("admin", "editor", "viewer") NOT NULL DEFAULT "admin",
            access_token VARCHAR(255) DEFAULT NULL,
            token_expires_at DATETIME DEFAULT NULL,
            last_login DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX ix_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public static function getUserByAfiazoneId(string $afiazoneId): ?array
    {
        $stmt = DB::getConnection()->prepare('SELECT * FROM users WHERE afiazone_id = :afiazone_id LIMIT 1');
        $stmt->execute(['afiazone_id' => $afiazoneId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function getUserByAccessToken(string $accessToken): ?array
    {
        $stmt = DB::getConnection()->prepare('SELECT * FROM users WHERE access_token = :access_token LIMIT 1');
        $stmt->execute(['access_token' => $accessToken]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function clearSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?: '/',
                $params['domain'] ?: '',
                $params['secure'],
                $params['httponly']
            );

            if (isset($_COOKIE[session_name()])) {
                unset($_COOKIE[session_name()]);
            }
        }

        session_regenerate_id(true);
        session_destroy();
        session_write_close();
    }
}
