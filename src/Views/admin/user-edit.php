<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer l'utilisateur</title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">📧 Newsletter Admin</div>
            <ul class="navbar-menu">
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/subscribers">Abonnés</a></li>
                <li><a href="/newsletter">Newsletters</a></li>
                <li><a href="/users" class="active">Utilisateurs</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="card">
            <div class="card-header">
                <div class="flex-between">
                    <div>
                        <p class="section-label">Gestion des accès</p>
                        <h2>Éditer l'utilisateur</h2>
                    </div>
                    <a href="/users" class="btn btn-secondary btn-sm">← Retour</a>
                </div>
            </div>

            <form method="post" action="/users/update" class="card-body">
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label for="name">Nom</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label for="role">Rôle</label>
                    <select id="role" name="role">
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Éditeur</option>
                        <option value="viewer" <?= $user['role'] === 'viewer' ? 'selected' : '' ?>>Lecteur</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="/users" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
