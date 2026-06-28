<?php

require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/Xlsx.php';

class SubscribersController
{
    public function list(): void
    {
        Auth::requireRole('admin');

        $search = trim($_GET['q'] ?? '');
        $pdo = DB::getConnection();

        if ($search) {
            $stmt = $pdo->prepare('
                SELECT id, email, name, status, created_at, confirmed_at 
                FROM subscribers 
                WHERE email LIKE :search_email OR name LIKE :search_name 
                ORDER BY created_at DESC 
                LIMIT 100
            ');
            $stmt->execute([
                'search_email' => '%' . $search . '%',
                'search_name' => '%' . $search . '%',
            ]);
        } else {
            $stmt = $pdo->prepare('
                SELECT id, email, name, status, created_at, confirmed_at 
                FROM subscribers 
                ORDER BY created_at DESC 
                LIMIT 100
            ');
            $stmt->execute();
        }

        $subscribers = $stmt->fetchAll();
        View::render('subscribers/list', ['subscribers' => $subscribers, 'search' => $search]);
    }

    public function import(): void
    {
        Auth::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /subscribers');
            exit;
        }

        if (empty($_FILES['import_file']['tmp_name'])) {
            $_SESSION['subscriber_message'] = 'Aucun fichier envoyé.';
            header('Location: /subscribers');
            exit;
        }

        $pdo = DB::getConnection();
        $tmpFile = $_FILES['import_file']['tmp_name'];
        if (!is_uploaded_file($tmpFile)) {
            $_SESSION['subscriber_message'] = 'Fichier non valide.';
            header('Location: /subscribers');
            exit;
        }

        try {
            $rows = Xlsx::importSubscribers($tmpFile);
        } catch (Throwable $e) {
            $_SESSION['subscriber_message'] = 'Impossible de lire le fichier XLSX : ' . $e->getMessage();
            header('Location: /subscribers');
            exit;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            if (empty($row[0])) {
                continue;
            }

            $email = trim((string) $row[0]);
            $name = isset($row[1]) ? trim((string) $row[1]) : '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $existing = $pdo->prepare('SELECT id FROM subscribers WHERE email = :email LIMIT 1');
            $existing->execute(['email' => $email]);
            if ($existing->fetch()) {
                $skipped++;
                continue;
            }

            $token = bin2hex(random_bytes(32));
            $insert = $pdo->prepare('INSERT INTO subscribers (email, name, token, status, created_at) VALUES (:email, :name, :token, :status, :created_at)');
            $insert->execute([
                'email' => $email,
                'name' => $name,
                'token' => $token,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $imported++;
        }

        $_SESSION['subscriber_message'] = sprintf('Import XLSX terminé : %d abonnés ajoutés, %d ignorés.', $imported, $skipped);
        header('Location: /subscribers');
        exit;
    }

    public function export(): void
    {
        Auth::requireRole('admin');

        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT email, name, status, created_at FROM subscribers WHERE status = "active" ORDER BY created_at DESC');
        $subscribers = $stmt->fetchAll();

        Xlsx::exportSubscribers($subscribers, 'subscribers_' . date('Y-m-d_His') . '.xlsx');
    }

    public function template(): void
    {
        Auth::requireRole('admin');

        Xlsx::exportSubscribers([
            ['email' => 'contact@example.com', 'name' => 'Jean Dupont', 'status' => 'active', 'created_at' => date('Y-m-d H:i:s')],
        ], 'subscribers_template.xlsx');
    }

    public function edit(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['subscriber_message'] = 'Identifiant invalide.';
            header('Location: /subscribers');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, email, name, status, created_at, confirmed_at FROM subscribers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $subscriber = $stmt->fetch();

        if (!$subscriber) {
            $_SESSION['subscriber_message'] = 'Abonné introuvable.';
            header('Location: /subscribers');
            exit;
        }

        View::render('subscribers/edit', ['subscriber' => $subscriber]);
    }

    public function update(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $status = $this->normalizeStatus($_POST['status'] ?? 'pending');

        if ($id <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['subscriber_message'] = 'Email invalide.';
            header('Location: /subscribers');
            exit;
        }

        $pdo = DB::getConnection();
        $duplicateStmt = $pdo->prepare('SELECT id FROM subscribers WHERE email = :email AND id != :id LIMIT 1');
        $duplicateStmt->execute(['email' => $email, 'id' => $id]);
        if ($duplicateStmt->fetch()) {
            $_SESSION['subscriber_message'] = 'Cet email est déjà utilisé.';
            header('Location: /subscribers');
            exit;
        }

        $stmt = $pdo->prepare('UPDATE subscribers SET email = :email, name = :name, status = :status WHERE id = :id');
        $stmt->execute(['email' => $email, 'name' => $name, 'status' => $status, 'id' => $id]);

        $_SESSION['subscriber_message'] = 'Abonné mis à jour.';
        header('Location: /subscribers');
        exit;
    }

    public function delete(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['subscriber_message'] = 'Identifiant invalide.';
            header('Location: /subscribers');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM subscribers WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $_SESSION['subscriber_message'] = 'Abonné supprimé.';
        header('Location: /subscribers');
        exit;
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = ['pending', 'active', 'inactive', 'blocked'];
        $normalized = strtolower(trim($status));

        return in_array($normalized, $allowed, true) ? $normalized : 'pending';
    }
}
