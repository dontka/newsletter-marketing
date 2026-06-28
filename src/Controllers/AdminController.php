<?php

require_once __DIR__ . '/../Lib/AfiaZoneOAuth.php';
require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/SendQueueProcessor.php';

class AdminController
{
    private AfiaZoneOAuth $oauth;

    public function __construct()
    {
        $this->oauth = new AfiaZoneOAuth();
    }

    public function login(): void
    {
        header('Location: ' . $this->oauth->getAuthorizeUrl());
        exit;
    }

    public function callback(): void
    {
        $authKey = $_GET['auth_key'] ?? null;
        if (!$authKey) {
            View::render('message', ['message' => 'Auth key manquante.']);
            return;
        }

        $accessToken = $this->oauth->exchangeAuthKey($authKey);
        if (!$accessToken) {
            View::render('message', ['message' => 'Impossible de récupérer le token AfiaZone.']);
            return;
        }

        $userInfo = $this->oauth->getUserInfo($accessToken);
        if ($userInfo === null) {
            View::render('message', ['message' => 'Impossible de récupérer les informations utilisateur AfiaZone.']);
            return;
        }

        $_SESSION['afiazone_access_token'] = $accessToken;
        $_SESSION['afiazone_user_info'] = $userInfo;

        $user = Auth::ensureUserExists($userInfo, $accessToken);
        if (empty($user)) {
            View::render('message', ['message' => 'Impossible d\'enregistrer l\'utilisateur.']);
            return;
        }

        header('Location: /admin/dashboard');
        exit;
    }

    public function dashboard(): void
    {
        Auth::requireLogin();

        $userInfo = Auth::getCurrentUser();
        View::render('admin', ['user' => $userInfo]);
    }

    public function users(): void
    {
        Auth::requireRole('admin');

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, afiazone_id, email, name, role, created_at, last_login FROM users ORDER BY created_at DESC');
        $stmt->execute();
        $users = $stmt->fetchAll();

        View::render('admin/users', ['users' => $users]);
    }

    public function editUser(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_message'] = 'Identifiant invalide.';
            header('Location: /users');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, afiazone_id, email, name, role, created_at, last_login FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_message'] = 'Utilisateur introuvable.';
            header('Location: /users');
            exit;
        }

        View::render('admin/user-edit', ['user' => $user]);
    }

    public function updateUser(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $role = $_POST['role'] ?? 'admin';

        if ($id <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin', 'editor', 'viewer'], true)) {
            $_SESSION['flash_message'] = 'Données invalides.';
            header('Location: /users');
            exit;
        }

        $pdo = DB::getConnection();
        $duplicateStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $duplicateStmt->execute(['email' => $email, 'id' => $id]);
        if ($duplicateStmt->fetch()) {
            $_SESSION['flash_message'] = 'Cet email est déjà utilisé.';
            header('Location: /users');
            exit;
        }

        $stmt = $pdo->prepare('UPDATE users SET email = :email, name = :name, role = :role WHERE id = :id');
        $stmt->execute(['email' => $email, 'name' => $name, 'role' => $role, 'id' => $id]);

        $_SESSION['flash_message'] = 'Utilisateur mis à jour.';
        header('Location: /users');
        exit;
    }

    public function updateUserRole(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'admin';
        $allowedRoles = ['admin', 'editor', 'viewer'];
        if ($id <= 0 || !in_array($role, $allowedRoles, true)) {
            $_SESSION['flash_message'] = 'Rôle invalide.';
            header('Location: /users');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);

        $_SESSION['flash_message'] = 'Rôle mis à jour.';
        header('Location: /users');
        exit;
    }

    public function deleteUser(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_message'] = 'Identifiant invalide.';
            header('Location: /users');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $_SESSION['flash_message'] = 'Utilisateur supprimé.';
        header('Location: /users');
        exit;
    }

    public function queue(): void
    {
        Auth::requireRole('admin');

        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "pending"');
        $pendingJobs = (int) $stmt->fetchColumn();

        View::render('admin/queue', ['pendingJobs' => $pendingJobs]);
    }

    public function processQueue(): void
    {
        $user = Auth::getCurrentUser();
        if ($user === null || $user['role'] !== 'admin') {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(403);
            }
            echo json_encode(['error' => 'unauthorized']);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        ob_start();

        $processor = new SendQueueProcessor();
        $initial = isset($_POST['initial']) && $_POST['initial'] === '1';

        if ($initial) {
            $queueInfo = $processor->prepareQueue();
            $batchResult = $processor->processBatch();

            $response = [
                'status' => $batchResult['done'] ? 'done' : 'running',
                'totalJobs' => $queueInfo['totalJobs'],
                'pending' => $batchResult['pending'],
                'processed' => $batchResult['processed'],
                'messages' => array_merge($queueInfo['messages'], $batchResult['messages']),
                'done' => $batchResult['done'],
            ];
        } else {
            $batchResult = $processor->processBatch();
            $response = [
                'status' => $batchResult['done'] ? 'done' : 'running',
                'pending' => $batchResult['pending'],
                'processed' => $batchResult['processed'],
                'messages' => $batchResult['messages'],
                'done' => $batchResult['done'],
            ];
        }

        if (ob_get_length() > 0) {
            ob_clean();
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    public function statusQueue(): void
    {
        $user = Auth::getCurrentUser();
        if ($user === null || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'unauthorized']);
            return;
        }

        $token = $_GET['token'] ?? '';
        $statusFile = __DIR__ . '/../../storage/queue_' . $token . '.json';
        $logFile = __DIR__ . '/../../storage/queue_' . $token . '.log';

        if (!is_file($statusFile)) {
            echo json_encode(['status' => 'idle']);
            return;
        }

        $data = json_decode((string) file_get_contents($statusFile), true) ?? [];
        $messages = [];
        if (is_file($logFile)) {
            $messages = array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -40);
        }

        $data['messages'] = $messages;
        echo json_encode($data);
    }

    private function getPendingJobsCount(): int
    {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "pending"');
        return (int) $stmt->fetchColumn();
    }

    public function logout(): void
    {
        Auth::clearSession();
        header('Location: /');
        exit;
    }
}
