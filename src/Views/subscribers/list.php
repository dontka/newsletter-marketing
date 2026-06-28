<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnés</title>
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
                <li><a href="/subscribers" class="active">Abonnés</a></li>
                <li><a href="/newsletter">Newsletters</a></li>
                <li><a href="/admin/logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <div class="container page-section">
        <div class="flex-between mb-4">
            <h1>Abonnés</h1>
            <div class="action-stack">
                <a href="/subscribers/template" class="btn btn-secondary">Télécharger modèle</a>
                <a href="/subscribers/export" class="btn btn-primary">Exporter XLSX</a>
            </div>
        </div>

        <div class="card import-panel">
            <div class="import-panel__header">
                <div>
                    <p class="section-label">Import de contacts</p>
                    <h3>Importez facilement vos abonnés depuis un CSV</h3>
                </div>
            </div>
            <form method="post" action="/subscribers/import" enctype="multipart/form-data" class="import-form">
                <label class="upload-zone" for="import_file">
                    <span class="upload-zone__icon">⬆️</span>
                    <span class="upload-zone__title">Glissez-déposez votre fichier XLSX ici</span>
                    <span class="upload-zone__hint">ou cliquez pour parcourir</span>
                    <input id="import_file" type="file" name="import_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </label>
                <div class="import-form__meta">
                    <p>Format attendu : une colonne Email, optionnellement une colonne Nom. Le modèle peut être téléchargé ci-dessus.</p>
                    <button type="submit" class="btn btn-primary">Importer les contacts</button>
                </div>
            </form>
        </div>

        <?php if (!empty($_SESSION['subscriber_message'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['subscriber_message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['subscriber_message']); ?>
        <?php endif; ?>

        <div class="card">
            <form method="get" class="form-row">
                <div class="col">
                    <div class="form-group">
                        <label for="q">Recherche</label>
                        <input type="search" id="q" name="q" placeholder="Rechercher par email ou nom..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col self-end">
                    <button type="submit" class="btn btn-secondary">Rechercher</button>
                </div>
            </form>
        </div>

        <?php if (empty($subscribers)): ?>
            <div class="card text-center empty-state">
                <p>Aucun abonné trouvé.</p>
            </div>
        <?php else: ?>
            <div class="card mb-3">
                <form method="post" action="/subscribers/bulk" class="form-row align-items-end">
                    <div class="col">
                        <div class="form-group">
                            <label for="bulk_action">Action groupée</label>
                            <select id="bulk_action" name="bulk_action">
                                <option value="activate">Activer</option>
                                <option value="deactivate">Désactiver</option>
                                <option value="pending">Mettre en attente</option>
                                <option value="blocked">Bloquer</option>
                                <option value="delete">Supprimer</option>
                            </select>
                        </div>
                    </div>
                    <div class="col self-end">
                        <button type="submit" class="btn btn-primary">Appliquer à la sélection</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-subscribers" aria-label="Sélectionner tous les abonnés"></th>
                            <th>Email</th>
                            <th>Nom</th>
                            <th>Status</th>
                            <th>Inscrit le</th>
                            <th>Confirmé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                            <tr>
                                <td><input type="checkbox" name="subscriber_ids[]" value="<?= (int) $sub['id'] ?>" class="subscriber-checkbox"></td>
                                <td><?= htmlspecialchars($sub['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($sub['name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($sub['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($sub['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($sub['confirmed_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/subscribers/edit?id=<?= (int) $sub['id'] ?>" class="btn btn-secondary btn-sm">Éditer</a>
                                        <form method="post" action="/subscribers/delete" class="inline-form" onsubmit="return confirm('Supprimer cet abonné ?');">
                                            <input type="hidden" name="id" value="<?= (int) $sub['id'] ?>">
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

    <script src="/public/assets/script.js"></script>
</body>
</html>
