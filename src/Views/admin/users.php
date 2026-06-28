<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs</title>
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
        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="flex-between mb-4">
            <h1>Utilisateurs</h1>
        </div>

        <?php if (empty($users)): ?>
            <div class="card text-center empty-state">
                <p>Aucun utilisateur trouvé.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Nom</th>
                            <th>Rôle</th>
                            <th>Créé le</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($user['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($user['last_login'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/users/edit?id=<?= (int) $user['id'] ?>" class="btn btn-secondary btn-sm">Éditer</a>
                                        <form method="post" action="/users/update-role" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                            <select name="role">
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Éditeur</option>
                                                <option value="viewer" <?= $user['role'] === 'viewer' ? 'selected' : '' ?>>Lecteur</option>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm">Enregistrer</button>
                                        </form>
                                        <form method="post" action="/users/delete" class="inline-form" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
