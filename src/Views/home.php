<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Newsletter</title>
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

    <div class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="hero-badge">⚡ Outils de campagne modernes</div>
                <h1>Faites grandir votre audience avec une newsletter professionnelle</h1>
                <p>Centralisez l'abonnement, la confirmation, l'envoi et le suivi dans une interface simple, élégante et prête à utiliser.</p>
                <div class="hero-buttons">
                    <a href="/subscribe" class="btn btn-primary btn-lg">S'abonner à la newsletter</a>
                    <a href="/admin/login" class="btn btn-secondary btn-lg">Espace Admin</a>
                </div>
            </div>
            <div class="hero-panel">
                <div class="hero-panel-card">
                    <h3>Ce que vous pouvez faire</h3>
                    <ul>
                        <li>Créer des campagnes en quelques secondes</li>
                        <li>Gérer vos abonnés en toute simplicité</li>
                        <li>Suivre l'activité et améliorer vos envois</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container content-section">
        <div class="intro-section">
            <div>
                <p class="section-label">Pourquoi choisir cette solution ?</p>
                <h2>Un espace simple pour communiquer mieux</h2>
            </div>
            <p>Des workflows clairs, une expérience moderne et un tableau de bord pensé pour les équipes qui veulent gagner du temps.</p>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">✉️</div>
                <h3>Abonnement facile</h3>
                <p>Formulaire d'abonnement simple avec double confirmation par email</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Gestion complète</h3>
                <p>Gérez vos abonnés, recherchez, filtrez et exportez en CSV</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📨</div>
                <h3>Envoi planifié</h3>
                <p>Créez et programmez vos newsletters pour l'envoi optimal</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Sécurisé</h3>
                <p>Authentification OAuth via AfiaZone pour les administrateurs</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚙️</div>
                <h3>Automatisé</h3>
                <p>Envoi automatique et fiable via cron jobs</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Responsive</h3>
                <p>Interface optimisée pour tous les appareils</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>À propos</h4>
                    <p>Système de newsletter professionnel pour gérer vos campagnes d'email marketing.</p>
                </div>
                <div class="footer-section">
                    <h4>Liens</h4>
                    <ul>
                        <li><a href="/">Accueil</a></li>
                        <li><a href="/subscribe">S'abonner</a></li>
                        <li><a href="/admin/login">Admin</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <p>Besoin d'aide ? Consultez la documentation ou contactez-nous.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Système de Newsletter - Tous droits réservés</p>
            </div>
        </div>
    </footer>

    <script src="/public/assets/script.js"></script>
</body>
</html>
