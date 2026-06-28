<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletters</title>
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
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/subscribers">Abonnés</a></li>
                <li><a href="/newsletter" class="active">Newsletters</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="flex-between mb-4">
            <h1>Newsletters</h1>
            <a href="/newsletter/create" class="btn btn-primary">+ Nouvelle Newsletter</a>
        </div>

        <?php if (empty($newsletters)): ?>
            <div class="card text-center empty-state">
                <p>Aucune newsletter trouvée.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Audience</th>
                            <th>Suivi</th>
                            <th>Créée le</th>
                            <th>Prévue le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsletters as $nl): ?>
                            <tr>
                                <td><?= htmlspecialchars($nl['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                        $statusClass = 'status-pill--draft';
                                        if (strtolower($nl['status']) === 'sent') {
                                            $statusClass = 'status-pill--sent';
                                        } elseif (strtolower($nl['status']) === 'scheduled') {
                                            $statusClass = 'status-pill--scheduled';
                                        }
                                    ?>
                                    <span class="status-pill <?= $statusClass; ?>"><?= htmlspecialchars($nl['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars(
                                    [
                                        'announcement' => 'Annonce',
                                        'promotion' => 'Promotion',
                                        'educational' => 'Éducation',
                                        'product' => 'Produit',
                                    ][strtolower($nl['campaign_type'] ?? 'announcement')] ?? 'Annonce',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?></td>
                                <td><?= htmlspecialchars(
                                    [
                                        'all' => 'Tous les abonnés',
                                        'active' => 'Abonnés actifs',
                                        'new' => 'Nouveaux abonnés',
                                        'vip' => 'VIP',
                                    ][strtolower($nl['audience'] ?? 'all')] ?? 'Tous les abonnés',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?></td>
                                <td><?= !empty($nl['tracking_enabled']) ? 'Activé' : 'Désactivé' ?></td>
                                <td><?= htmlspecialchars($nl['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($nl['scheduled_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="/public/assets/script.js"></script>
</body>
</html>
