<?php

require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/View.php';

class UnsubscribeController
{
    public function unsubscribe(): void
    {
        $token = $_GET['token'] ?? null;
        if (!$token) {
            View::render('message', ['message' => 'Token d\'unsubscribe manquant.']);
            return;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM subscribers WHERE token = :token AND status != :status');
        $stmt->execute(['token' => $token, 'status' => 'unsubscribed']);
        $subscriber = $stmt->fetch();

        if (!$subscriber) {
            View::render('message', ['message' => 'Token invalide ou déjà désabonné.']);
            return;
        }

        $update = $pdo->prepare('UPDATE subscribers SET status = :status, unsubscribed_at = :unsubscribed_at WHERE id = :id');
        $update->execute([
            'status' => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s'),
            'id' => $subscriber['id'],
        ]);

        View::render('message', ['message' => 'Vous avez été désinscrit avec succès.']);
    }
}
