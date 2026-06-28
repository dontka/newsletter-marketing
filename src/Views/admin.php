<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">📧 Newsletter Admin</div>
            <button class="menu-toggle" type="button" aria-label="Ouvrir le menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="navbar-menu">
                <li><a href="/admin/dashboard" class="active">Dashboard</a></li>
                <li><a href="/subscribers">Abonnés</a></li>
                <li><a href="/newsletter">Newsletters</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="card mb-3">
            <div class="card-header">
                <h2>Bienvenue au tableau de bord</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($user)): ?>
                    <div class="mb-3">
                        <p><strong>Connecté en tant que :</strong> <?= htmlspecialchars($user['user_name'] ?? $user['user_email'] ?? 'utilisateur', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Email :</strong> <?= htmlspecialchars($user['user_email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                <?php else: ?>
                    <p>Connecté via AfiaZone</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Abonnés</h3>
                <p>Gérez et consultez la liste de vos abonnés</p>
                <a href="/subscribers" class="btn btn-primary btn-sm">Accéder →</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📨</div>
                <h3>Newsletters</h3>
                <p>Créez et envoyez vos newsletters</p>
                <a href="/newsletter" class="btn btn-primary btn-sm">Accéder →</a>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚙️</div>
                <h3>Queue d’envoi</h3>
                <p>Traitez les jobs d’envoi directement depuis l’interface</p>
                <a href="/admin/queue" class="btn btn-primary btn-sm">Accéder →</a>
            </div>
        </div>
    </div>

    <script src="/public/assets/script.js"></script>
</body>
</html>
