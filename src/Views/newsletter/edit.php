<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la newsletter</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container navbar-container">
        <div class="navbar-brand">📧 Newsletter Admin</div>
        <ul class="navbar-menu">
            <li><a href="/admin/dashboard">Dashboard</a></li>
            <li><a href="/subscribers">Abonnés</a></li>
            <li><a href="/newsletter" class="active">Newsletters</a></li>
            <li><a href="/admin/logout">Déconnexion</a></li>
        </ul>
    </div>
</nav>

<div class="container page-section">
    <div class="card">
        <div class="card-header">
            <div class="flex-between">
                <div>
                    <p class="section-label">Campagne marketing</p>
                    <h2>Modifier la newsletter</h2>
                </div>
                <a href="/newsletter" class="btn btn-secondary btn-sm">← Retour</a>
            </div>
        </div>

        <form method="post" action="/newsletter/update" class="card-body">
            <input type="hidden" name="id" value="<?= (int) $newsletter['id'] ?>">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="subject">Sujet</label>
                        <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($newsletter['subject'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="draft" <?= $newsletter['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="scheduled" <?= $newsletter['status'] === 'scheduled' ? 'selected' : '' ?>>Programmé</option>
                            <option value="sending" <?= $newsletter['status'] === 'sending' ? 'selected' : '' ?>>Envoi</option>
                            <option value="sent" <?= $newsletter['status'] === 'sent' ? 'selected' : '' ?>>Envoyé</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="campaign_type">Type</label>
                        <select id="campaign_type" name="campaign_type">
                            <option value="announcement" <?= $newsletter['campaign_type'] === 'announcement' ? 'selected' : '' ?>>Annonce</option>
                            <option value="promotion" <?= $newsletter['campaign_type'] === 'promotion' ? 'selected' : '' ?>>Promotion</option>
                            <option value="educational" <?= $newsletter['campaign_type'] === 'educational' ? 'selected' : '' ?>>Éducation</option>
                            <option value="product" <?= $newsletter['campaign_type'] === 'product' ? 'selected' : '' ?>>Produit</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="audience">Audience</label>
                        <select id="audience" name="audience">
                            <option value="all" <?= $newsletter['audience'] === 'all' ? 'selected' : '' ?>>Tous</option>
                            <option value="active" <?= $newsletter['audience'] === 'active' ? 'selected' : '' ?>>Actifs</option>
                            <option value="new" <?= $newsletter['audience'] === 'new' ? 'selected' : '' ?>>Nouveaux</option>
                            <option value="vip" <?= $newsletter['audience'] === 'vip' ? 'selected' : '' ?>>VIP</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="scheduled_at">Planification</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= htmlspecialchars($newsletter['scheduled_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($newsletter['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
                <label for="plain_text">Version texte</label>
                <textarea id="plain_text" name="plain_text" rows="6"><?= htmlspecialchars($newsletter['plain_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
                <label for="tracking">Suivi</label>
                <select id="tracking" name="tracking">
                    <option value="1" <?= !empty($newsletter['tracking_enabled']) ? 'selected' : '' ?>>Activé</option>
                    <option value="0" <?= empty($newsletter['tracking_enabled']) ? 'selected' : '' ?>>Désactivé</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="/newsletter" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
