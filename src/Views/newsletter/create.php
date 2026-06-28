<?php
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Newsletter</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
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
        <div class="card">
            <div class="card-header">
                <div class="flex-between">
                    <div>
                        <p class="section-label">Campagne marketing</p>
                        <h2>Créer une nouvelle newsletter</h2>
                    </div>
                    <a href="/newsletter" class="btn btn-secondary btn-sm">← Retour</a>
                </div>
            </div>

            <form method="post" action="/newsletter/save" class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="subject">Sujet de la campagne</label>
                            <input type="text" id="subject" name="subject" placeholder="Ex : Découvrez nos nouveautés du mois" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="campaign_type">Type de campagne</label>
                            <select id="campaign_type" name="campaign_type">
                                <option value="announcement">Annonce</option>
                                <option value="promotion">Promotion</option>
                                <option value="educational">Éducation / Tips</option>
                                <option value="product">Produit / Service</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="audience">Audience ciblée</label>
                            <select id="audience" name="audience">
                                <option value="all">Tous les abonnés</option>
                                <option value="active">Abonnés actifs</option>
                                <option value="new">Nouveaux abonnés</option>
                                <option value="vip">VIP / clients fidèles</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="scheduled_at">Planification</label>
                            <input type="datetime-local" id="scheduled_at" name="scheduled_at">
                        </div>
                    </div>
                </div>

                <div class="builder-shell">
                    <div class="builder-sidebar">
                        <div class="builder-sidebar-card">
                            <h3>Blocs marketing</h3>
                            <p>Ajoutez rapidement des sections prêtes à l’emploi.</p>
                            <div class="block-list">
                                <button type="button" class="block-chip" data-insert-block="hero">Hero / lancement</button>
                                <button type="button" class="block-chip" data-insert-block="features">Bénéfices</button>
                                <button type="button" class="block-chip" data-insert-block="cta">Appel à l’action</button>
                                <button type="button" class="block-chip" data-insert-block="testimonial">Témoignage</button>
                            </div>
                        </div>

                        <div class="builder-sidebar-card">
                            <h3>Modèles enregistrés</h3>
                            <p>Charger un modèle existant depuis la base de données.</p>
                            <?php if (!empty($templates)): ?>
                                <div class="block-list scrollable-templates">
                                    <?php foreach ($templates as $template): ?>
                                        <button type="button" class="block-chip block-chip--template" data-template-id="<?= htmlspecialchars($template['id'], ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($template['category'] . ' – ' . $template['name'], ENT_QUOTES) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Aucun modèle enregistré disponible.</p>
                            <?php endif; ?>
                        </div>

                        <div class="builder-sidebar-card">
                            <h3>Images & médias</h3>
                            <p>Insérez une image dans votre newsletter avec un lien direct ou un upload.</p>
                            <div class="form-group">
                                <label for="imageSourceUrl">URL de l'image</label>
                                <input type="url" id="imageSourceUrl" class="full-width" placeholder="https://example.com/image.jpg">
                            </div>
                            <div class="block-list">
                                <button type="button" class="block-chip" data-action="insert-image-url">Insérer l'image</button>
                                <button type="button" class="block-chip" data-action="upload-image">Uploader une image</button>
                                <input type="file" id="imageFileInput" accept="image/*" hidden>
                            </div>
                        </div>
                    </div>

                    <div class="builder-main">
                        <div class="editor-toolbar">
                            <div>
                                <p class="section-label">Éditeur visuel</p>
                                <h3>Construisez votre message</h3>
                            </div>
                            <div class="builder-actions">
                                <button type="button" class="btn btn-secondary btn-sm" data-action="reset">Réinitialiser</button>
                            </div>
                        </div>

                        <div class="content-editor-wrap">
                            <div id="quillToolbar" class="ql-toolbar ql-snow">
                                <span class="ql-formats">
                                    <button class="ql-bold"></button>
                                    <button class="ql-italic"></button>
                                    <button class="ql-underline"></button>
                                    <button class="ql-link"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered"></button>
                                    <button class="ql-list" value="bullet"></button>
                                    <button class="ql-header" value="2"></button>
                                    <button class="ql-header" value="3"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-align" value=""></button>
                                    <button class="ql-align" value="center"></button>
                                    <button class="ql-align" value="right"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-image"></button>
                                </span>
                            </div>
                            <div id="imageEditControls" class="image-edit-controls" hidden>
                                <label for="imageWidthRange">Largeur de l’image</label>
                                <input type="range" id="imageWidthRange" min="20" max="100" step="5" value="100">
                                <div class="image-align-group">
                                    <button type="button" class="image-align-btn" data-image-align="left" title="Aligner à gauche">←</button>
                                    <button type="button" class="image-align-btn" data-image-align="center" title="Centrer">↔</button>
                                    <button type="button" class="image-align-btn" data-image-align="right" title="Aligner à droite">→</button>
                                </div>
                            </div>
                            <div id="contentEditor" class="content-editor"></div>
                            <input type="hidden" id="content" name="content" required>
                        </div>

                        <div class="preview-card">
                            <div class="preview-header">
                                <h3>Aperçu en direct</h3>
                                <span class="preview-badge">Live</span>
                            </div>
                            <iframe id="previewFrame" class="preview-frame" title="Aperçu de votre newsletter"></iframe>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="plain_text">Version texte (optionnel)</label>
                    <textarea id="plain_text" name="plain_text" rows="6" placeholder="Version texte lisible pour les clients mail sans HTML"></textarea>
                </div>

                <div class="card" style="background:var(--light-bg); margin-top:1.5rem;">
                    <div class="card-header">
                        <h3>Template & modèle</h3>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label" for="save_as_template">
                            <input type="checkbox" id="save_as_template" name="save_as_template" value="1">
                            Enregistrer cette newsletter comme modèle
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="template_name">Nom du modèle</label>
                        <input type="text" id="template_name" name="template_name" placeholder="Ex : Offre été 2026">
                    </div>
                </div>

                <div class="card" style="background:var(--light-bg); margin-top:1.5rem;">
                    <div class="card-header">
                        <h3>Options d’envoi</h3>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">Enregistrer en brouillon</button>
                        <button type="submit" name="action" value="send_now" class="btn btn-primary">Envoyer maintenant</button>
                        <button type="submit" name="action" value="schedule" class="btn btn-secondary">Programmer</button>
                    </div>
                    <div class="form-group mb-0">
                        <label for="tracking">Suivi activé</label>
                        <select id="tracking" name="tracking">
                            <option value="1">Oui, activer les ouvertures et clics</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.savedEmailTemplates = <?php echo json_encode($templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <script src="/public/assets/script.js"></script>
</body>
</html>
