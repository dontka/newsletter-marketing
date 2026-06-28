# Installation du système de newsletter

## Prérequis
- PHP 7.4+
- MySQL 5.7+ ou MariaDB
- Accès en ligne de commande
- Accès SSH ou cron jobs

## Étapes d'installation

### 1. Copier et configurer le fichier `.env`

```bash
cp .env.example .env
```

Éditer le `.env` et configurer:
- **DB_HOST**: Hôte MySQL (ex: 127.0.0.1)
- **DB_NAME**: Nom de la base de données
- **DB_USER**: Utilisateur MySQL
- **DB_PASS**: Mot de passe MySQL
- **SMTP_FROM**: Email d'envoi
- **SMTP_HOST**: Serveur SMTP
- **SMTP_PORT**: Port SMTP (25, 587, 465)
- **BASE_URL**: URL publique de votre app (ex: http://example.com/newsletter)
- **AFIAZONE_APP_ID**: ID de votre app AfiaZone
- **AFIAZONE_APP_SECRET**: Secret de votre app
- **AFIAZONE_REDIRECT_URI**: URL de callback (ex: http://example.com/newsletter/admin/callback)

### 2. Créer les dossiers de logs

```bash
mkdir -p logs
chmod 755 logs
```

### 3. Initialiser la base de données

```bash
mysql -u root -p < scripts/migrate.sql
```

Ou importer le fichier SQL via phpMyAdmin.

### 4. Configurer le cron pour l'envoi

Ajouter cette ligne à votre crontab:

```bash
*/5 * * * * php /chemin/vers/newsletter/scripts/send_cron.php
```

Vérifier le cron:
```bash
crontab -l
```

### 5. (Optionnel) Apache / Nginx

**Pour Apache**, s'assurer que `mod_rewrite` est activé et que `.htaccess` est bien placé dans `public/`.

**Pour Nginx**, configurer le rewrite:

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^/(.*)$ /index.php?uri=$1 last;
    }
}
```

## Utilisation

### Pour les visiteurs
- Accéder à `http://example.com/newsletter/` pour s'abonner

### Pour les administrateurs
- Accéder à `http://example.com/newsletter/admin/login` pour se connecter via AfiaZone
- Gérer les abonnés, créer/envoyer des newsletters

## Dépannage

### Les emails ne sont pas envoyés
- Vérifier les logs: `tail -f logs/send_cron.log`
- Vérifier la configuration SMTP dans `.env`
- S'assurer que le cron s'exécute: `grep CRON /var/log/syslog`

### La base de données n'est pas accessible
- Vérifier les credentials dans `.env`
- Vérifier que MySQL est en cours d'exécution
- Tester la connexion: `mysql -u root -p -h 127.0.0.1 newsletter`

### AfiaZone OAuth ne fonctionne pas
- Vérifier l'APP_ID et APP_SECRET dans `.env`
- S'assurer que AFIAZONE_REDIRECT_URI correspond à celle enregistrée dans AfiaZone
- Vérifier les logs des erreurs: `logs/send_cron.log`

## Sécurité

- Jamais de commit du fichier `.env` en production
- Utiliser HTTPS en production
- Mots de passe admin forts dans AfiaZone
- Régulièrement sauvegarder la base de données

## Support

Consulter le README.md pour le plan de développement complet.
