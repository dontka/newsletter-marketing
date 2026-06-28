# Système de newsletter (PHP + MySQL) — Plan de développement complet

Objectif
- Construire un système de newsletter autonome en PHP et MySQL, sans framework ni service tiers. Fonctionnalités : abonnement/désabonnement, confirmation par email, interface d'administration, rédaction/envoi de newsletters, planification d'envoi, suivi basique (ouvertures/clics) et conformité RGPD.

Prérequis techniques
- PHP 7.4+ (ou 8.x)
- MySQL 5.7+ / MariaDB
- Serveur SMTP (ex: Postfix, Exim) ou compte SMTP (ex: SMTP de votre hébergeur)
- Outils : Git, Composer (pour libs PSR si besoin), cron

Arborescence proposée (architecture MVC)
- /
  - index.php (point d'entrée public, bootstrap de l'application)
  - public/ (fichiers accessibles web)
    - assets/ (CSS, JS, images)
    - .htaccess ou configuration serveur pour rediriger vers `/index.php`
  - src/
    - App.php (bootstrap, configuration, chargement du routeur)
    - Router.php (routeur simple)
    - Controllers/
      - SubscriptionController.php
      - AdminController.php
      - NewsletterController.php
    - Models/
      - Subscriber.php
      - Newsletter.php
      - SendJob.php
    - Views/
      - subscribe.php
      - admin.php
      - newsletter.php
      - message.php
    - Lib/
      - Mailer.php (wrapper SMTP)
      - DB.php (PDO wrapper)
      - Queue.php (simple file-based queue)
  - scripts/
    - migrate.sql
    - send_cron.php
  - tests/
  - README.md
  - .env.example (non commité)

Schéma MySQL recommandé (début)
- `subscribers`:
  - id INT PK AUTO_INCREMENT
  - email VARCHAR(255) UNIQUE NOT NULL
  - name VARCHAR(255) NULL
  - token VARCHAR(64) NOT NULL (confirmation / unsubscribe)
  - status ENUM('pending','active','unsubscribed','bounced') DEFAULT 'pending'
  - created_at DATETIME
  - confirmed_at DATETIME NULL
  - unsubscribed_at DATETIME NULL

- `newsletters`:
  - id INT PK AUTO_INCREMENT
  - subject VARCHAR(255)
  - content TEXT (HTML)
  - plain_text TEXT
  - created_by VARCHAR(100)
  - created_at DATETIME
  - scheduled_at DATETIME NULL
  - status ENUM('draft','scheduled','sending','sent','cancelled')

- `send_jobs`:
  - id INT PK AUTO_INCREMENT
  - newsletter_id INT FK
  - subscriber_id INT FK
  - status ENUM('pending','sent','failed') DEFAULT 'pending'
  - attempts INT DEFAULT 0
  - last_error TEXT NULL
  - sent_at DATETIME NULL

- `events` (optionnel pour tracking):
  - id INT PK
  - send_job_id INT
  - type ENUM('open','click')
  - meta JSON NULL
  - created_at DATETIME

Étapes détaillées de développement (pas-à-pas)

1) Spécifications & flux utilisateur
1) Spécifications & flux utilisateur — Terminé (0.5-1 jour)
- Écrire `migrate.sql` contenant les tables ci-dessus et index nécessaires (email UNIQUE, index sur scheduled_at, status).
- Prévoir intégrité référentielle et contraintes.

3) Initialiser le projet — Terminé
- `git init` + .gitignore (ignorer .env, vendor, logs)
- Fichier de configuration `.env` (DB credentials, SMTP, base_url, app_secret).
- Écrire `src/DB.php` : wrapper PDO avec connection via `.env`.

4) Infrastructure minimale — Terminé
- Bootstrap dans `index.php` et `src/App.php`.
- Router simple qui mappe routes vers controllers.
- Basic templating : `View::render($name, $data)`.
- `public/.htaccess` ou configuration serveur définie pour rediriger vers le front-controller.

5) Abonnement et confirmation — En cours
- L'utilisateur accède à `/` ou `/subscribe` (ou `/index.php` / `/subscribe.php`) pour voir le formulaire d'abonnement.
- `POST /subscribe` : valider email, créer entrée en `subscribers` avec `status='pending'` et `token` unique.
- Envoyer email de confirmation avec lien `/confirm?token=...` (ou `/confirm.php?token=...`).
- `GET /confirm` : vérifier token, passer `status='active'`, enregistrer `confirmed_at`.
- Gérer cas d'email déjà existant et réenvoi du mail de confirmation.

6) Désabonnement 
- Lien d'unsubscribe inclus dans chaque email avec token sécurisé.
- `unsubscribe.php` : marquer `status='unsubscribed'`, enregistrer `unsubscribed_at`.

7) Interface Admin (UI avancé) 
- Auth via API AfiaZone : login OAuth avec `app_id` et `app_secret`.
- Configuration `.env` enrichie : `AFIAZONE_APP_ID`, `AFIAZONE_APP_SECRET`, `AFIAZONE_REDIRECT_URI`, `AFIAZONE_API_BASE=https://afiazone.com/api`.
- Flux d’authentification :
  - Créer une app dans le dashboard AfiaZone.
  - Rediriger l’admin vers `https://afiazone.com/api/oauth?app_id=YOUR_APP_ID`.
  - Récupérer le `auth_key` sur l’URL de redirection.
  - Échanger `auth_key` contre `access_token` via `POST https://afiazone.com/api/authorize`.
  - Utiliser `access_token` pour récupérer `user_info` via `GET https://afiazone.com/api/get_user_info?access_token=...`.
- Écrans : lister abonnés, rechercher, exporter CSV, voir statistiques simples.
- CRUD pour `newsletters` : créer, éditer (WYSIWYG avancé), sauvegarder en draft.

8) Rédaction & planification de newsletters (1-2 jours)
- Formulaire de création de newsletter (subject, HTML content, plain text, schedule datetime).
- Sauvegarde et option `Send now` ou `Schedule`.
- Lors de la planification, créer `send_jobs` en masse pour chaque `subscriber` actif ou créer une job par newsletter et découper en batchs lors de l'envoi.

9) Moteur d'envoi SMTP 
- `src/Lib/Mailer.php` : wrapper PHPMailer ou implémentation simple via `stream_socket_client`/`mail()` selon vos choix.
- Gestion d'erreurs et logs.
- Respecter limites SMTP (throttling), ajouter délais entre envois si besoin.

10) File d'attente et exécution (cron) 
- `scripts/send_cron.php` : script cron exécuté toutes les minutes/5 minutes qui :
  - Récupère `send_jobs` pending par batch (ex: 100), envoie les emails, met à jour `status`, `sent_at`, `attempts`.
  - Implémente retry/backoff pour échecs.
- Option : implémenter queue simple (fichier ou table DB) ou utiliser Redis si disponible (mais on garde MySQL simple).

11) Tracking ouverture/clic
- Insertion d'une image 1x1 unique avec URL `open.php?job=HASH` pour enregistrer open.
- Redirection via `click.php?u=ID&job=HASH` pour compter clics puis rediriger vers lien final.
- Considérations vie privée : anonymisation, opt-out pour tracking.

12) Sécurité et validation (0.5-1 jour)
- Protéger contre injections SQL (toujours PDO + prepared statements).
- Validation stricte des entrées utilisateurs.
- CSRF tokens pour formulaires admin.
- Hachage mot de passe admin (bcrypt).
- Limiter tentative d'inscription automatique, ajouter reCAPTCHA si besoin (optionnel).

13) Tests et QA (1-2 jours)
- Tests unitaires pour fonctions critiques (validation email, génération token).
- Tests d'intégration : workflow abonnement->confirmation->envoi.
- Tests manuels : envoi réel sur environnement de test.

14) Documentation et README 
- Documenter installation, config `.env`, commandes cron, comment migrer la base (`scripts/migrate.sql`).
- Expliquer stratégies de rollback et sauvegarde.

15) Déploiement et sauvegardes (0.5 jour)
- Déployer sur serveur LAMP/LEMP.
- Mettre en place sauvegarde DB (dump régulier) et rotation de logs.
- Superviser la file d'envoi et surveiller erreurs SMTP.

Checklist de qualité avant livraison
- [ ] Double opt-in fonctionnel
- [ ] Désabonnement dans chaque email
- [ ] Logs d'envoi et erreurs
- [ ] Limites d'envoi et retries gérés
- [ ] Filtres et validation des emails
- [ ] Conformité RGPD documentée
- [ ] Tests unitaires et d'intégration basiques

Exemples de requêtes / routes
- POST `/subscribe.php` {email, name}
- GET `/confirm.php?token=...`
- GET `/unsubscribe.php?token=...`
- GET `/admin.php` (login)
- POST `/admin/newsletter/save.php`
- scripts/send_cron.php (cron: */5 * * * *)

Bonnes pratiques et recommandations
- Ne pas envoyer de mass-mailing directement depuis l'environnement local, tester avec un SMTP de test (MailHog/ Mailtrap) ou compte dédié.
- Mettre en place throttling pour éviter d'être banni par le fournisseur SMTP.
- Considérer l'utilisation d'un outil d'emailing externe pour grosse volumétrie seulement après avoir maîtrisé la base.

Prochaines actions que je peux faire pour vous
- Générer le fichier `migrate.sql` prêt à l'emploi.
- Scaffold des fichiers PHP de base (`DB.php`, `Mailer.php`, controllers).
- Écrire le script `send_cron.php` exemple.


---
Fichier créé : [README.md](README.md)
>>>>>>> c6feae0 (Initial commit)
