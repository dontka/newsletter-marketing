<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S'abonner à notre newsletter</title>
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
                <li><a href="/subscribe" class="active">S'abonner</a></li>
                <li><a href="/admin/login">Admin</a></li>
            </ul>
        </div>
    </nav>

    <div class="page-hero">
        <div class="container-sm">
            <div class="card">
                <div class="card-header">
                    <h2>Abonnez-vous à notre newsletter</h2>
                    <p>Restez informé de nos dernières actualités et exclusivités</p>
                </div>

                <form method="post" action="/subscribe" class="card-body">
                    <div class="form-group">
                        <label for="email">Adresse email *</label>
                        <input type="email" id="email" name="email" required placeholder="vous@exemple.com">
                        <small>Nous respectons votre confidentialité. Pas de spam !</small>
                    </div>

                    <div class="form-group">
                        <label for="name">Nom complet (optionnel)</label>
                        <input type="text" id="name" name="name" placeholder="Votre nom">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">S'abonner</button>
                </form>

                <div class="card-footer">
                    <a href="/" class="btn btn-secondary btn-sm">← Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/assets/script.js"></script>
</body>
</html>
