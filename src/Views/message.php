<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message</title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">📧 Newsletter</div>
            <button class="menu-toggle" type="button" aria-label="Ouvrir le menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="navbar-menu">
                <li><a href="/">Accueil</a></li>
                <li><a href="/subscribe">S'abonner</a></li>
                <li><a href="/admin/login">Admin</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-section flex-center">
        <div class="container-sm">
            <div class="card text-center">
                <h2 class="mb-3">Message</h2>
                <p class="mb-4 message-text">
                    <?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </p>
                <a href="/" class="btn btn-primary">← Retour à l'accueil</a>
            </div>
        </div>
    </div>

    <script src="/public/assets/script.js"></script>
</body>
</html>
