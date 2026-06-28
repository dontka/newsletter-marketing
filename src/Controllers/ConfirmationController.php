<?php

require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/View.php';

class ConfirmationController
{
    public function confirm(): void
    {
        $token = $_GET['token'] ?? null;
        if (!$token) {
            View::render('message', ['message' => 'Token de confirmation manquant.']);
            return;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, status FROM subscribers WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $subscriber = $stmt->fetch();

        if (!$subscriber) {
            View::render('message', ['message' => 'Token invalide ou expiré.']);
            return;
        }

        $update = $pdo->prepare('UPDATE subscribers SET status = :status, confirmed_at = :confirmed_at WHERE id = :id');
        $update->execute([
            'status' => 'active',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'id' => $subscriber['id'],
        ]);

        View::render('message', ['message' => 'Votre abonnement a bien été confirmé.']);
    }
}
