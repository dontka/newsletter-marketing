<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer l'abonné</title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">📧 Newsletter Admin</div>
            <ul class="navbar-menu">
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/subscribers" class="active">Abonnés</a></li>
                <li><a href="/newsletter">Newsletters</a></li>
                <li><a href="/users">Utilisateurs</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="card">
            <div class="card-header">
                <div class="flex-between">
                    <div>
                        <p class="section-label">Gestion des contacts</p>
                        <h2>Éditer l'abonné</h2>
                    </div>
                    <a href="/subscribers" class="btn btn-secondary btn-sm">← Retour</a>
                </div>
            </div>

            <form method="post" action="/subscribers/update" class="card-body">
                <input type="hidden" name="id" value="<?= (int) $subscriber['id'] ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($subscriber['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($subscriber['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="pending" <?= $subscriber['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                        <option value="active" <?= $subscriber['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
                        <option value="inactive" <?= $subscriber['status'] === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                        <option value="blocked" <?= $subscriber['status'] === 'blocked' ? 'selected' : '' ?>>Bloqué</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/subscribers" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
