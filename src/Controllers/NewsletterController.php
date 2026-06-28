<?php

require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/View.php';
require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/EmailTemplateCatalog.php';

class NewsletterController
{
    public function list(): void
    {
        Auth::requireRole('admin');

        $pdo = DB::getConnection();
        $this->ensureNewsletterColumns($pdo);
        $stmt = $pdo->prepare('SELECT id, subject, status, created_at, scheduled_at, campaign_type, audience, tracking_enabled FROM newsletters ORDER BY created_at DESC LIMIT 50');
        $stmt->execute();
        $newsletters = $stmt->fetchAll();

        View::render('newsletter/list', ['newsletters' => $newsletters]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');

        $pdo = DB::getConnection();
        $this->ensureNewsletterColumns($pdo);
        $this->ensureEmailTemplateTable($pdo);
        $templates = $this->getEmailTemplates($pdo);

        View::render('newsletter/create', ['templates' => $templates]);
    }

    public function save(): void
    {
        Auth::requireRole('admin');

        $subject = trim($_POST['subject'] ?? '');
        $content = $_POST['content'] ?? '';
        $plainText = $_POST['plain_text'] ?? '';
        $saveAsTemplate = isset($_POST['save_as_template']);
        $templateName = trim($_POST['template_name'] ?? '');
        $action = $_POST['action'] ?? 'draft';
        $scheduledAt = $_POST['scheduled_at'] ?? null;
        $campaignType = $this->normalizeOption($_POST['campaign_type'] ?? 'announcement', 'announcement', ['announcement', 'promotion', 'educational', 'product']);
        $audience = $this->normalizeOption($_POST['audience'] ?? 'all', 'all', ['all', 'active', 'new', 'vip']);
        $trackingEnabled = filter_input(INPUT_POST, 'tracking', FILTER_VALIDATE_INT);
        if ($trackingEnabled === false || $trackingEnabled === null) {
            $trackingEnabled = 1;
        }
        $trackingEnabled = (int) $trackingEnabled;

        if (empty($subject) || empty($content)) {
            View::render('message', ['message' => 'Subject et contenu sont obligatoires.']);
            return;
        }

        $userInfo = $_SESSION['afiazone_user_info'] ?? [];
        $createdBy = $userInfo['user_email'] ?? 'unknown';

        try {
            $pdo = DB::getConnection();
            $this->ensureNewsletterColumns($pdo);
            $status = 'draft';
            if ($action === 'send_now') {
                $status = 'sending';
            } elseif ($action === 'schedule' && $scheduledAt) {
                $status = 'scheduled';
            }

            $newsletterColumns = $this->getExistingColumns($pdo, 'newsletters');
            $insertFields = ['subject', 'content', 'plain_text', 'created_by', 'scheduled_at', 'status', 'campaign_type', 'audience', 'tracking_enabled'];
            if (in_array('created_at', $newsletterColumns, true)) {
                $insertFields[] = 'created_at';
            }

            $insertValues = [
                'subject' => $subject,
                'content' => $content,
                'plain_text' => $plainText,
                'created_by' => $createdBy,
                'scheduled_at' => $scheduledAt ?: null,
                'status' => $status,
                'campaign_type' => $campaignType,
                'audience' => $audience,
                'tracking_enabled' => $trackingEnabled,
            ];

            if (in_array('created_at', $newsletterColumns, true)) {
                $insertValues['created_at'] = date('Y-m-d H:i:s');
            }

            $insert = $pdo->prepare('INSERT INTO newsletters (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', array_map(static fn (string $field): string => ':' . $field, $insertFields)) . ')');
            $insert->execute($insertValues);

            $newsletterId = $pdo->lastInsertId();

            if ($saveAsTemplate && $templateName !== '') {
                $this->saveEmailTemplate($pdo, $templateName, $campaignType, $subject, $content, $plainText, $createdBy);
            }

            if ($status === 'sending' || $status === 'scheduled') {
                $this->createSendJobs($pdo, $newsletterId, $audience);
            }

            $_SESSION['flash_message'] = 'Newsletter créée avec succès.';
            header('Location: /newsletter');
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Erreur lors de la création : ' . $e->getMessage();
            header('Location: /newsletter');
            exit;
        }
    }

    public function edit(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_GET['id'] ?? 0);
        $pdo = DB::getConnection();
        $this->ensureNewsletterColumns($pdo);
        $newsletter = $this->getNewsletterById($pdo, $id);

        if ($newsletter === null) {
            $_SESSION['flash_message'] = 'Newsletter introuvable.';
            header('Location: /newsletter');
            exit;
        }

        $this->ensureEmailTemplateTable($pdo);
        $templates = $this->getEmailTemplates($pdo);

        View::render('newsletter/edit', ['newsletter' => $newsletter, 'templates' => $templates]);
    }

    public function update(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $content = $_POST['content'] ?? '';
        $plainText = $_POST['plain_text'] ?? '';
        $status = $this->normalizeOption($_POST['status'] ?? 'draft', 'draft', ['draft', 'scheduled', 'sending', 'sent']);
        $scheduledAt = $_POST['scheduled_at'] ?? null;
        $campaignType = $this->normalizeOption($_POST['campaign_type'] ?? 'announcement', 'announcement', ['announcement', 'promotion', 'educational', 'product']);
        $audience = $this->normalizeOption($_POST['audience'] ?? 'all', 'all', ['all', 'active', 'new', 'vip']);
        $trackingEnabled = filter_input(INPUT_POST, 'tracking', FILTER_VALIDATE_INT);
        if ($trackingEnabled === false || $trackingEnabled === null) {
            $trackingEnabled = 1;
        }

        if ($id <= 0 || $subject === '' || $content === '') {
            $_SESSION['flash_message'] = 'Sujet et contenu sont obligatoires.';
            header('Location: /newsletter');
            exit;
        }

        $pdo = DB::getConnection();
        $this->ensureNewsletterColumns($pdo);
        $stmt = $pdo->prepare('UPDATE newsletters SET subject = :subject, content = :content, plain_text = :plain_text, status = :status, scheduled_at = :scheduled_at, campaign_type = :campaign_type, audience = :audience, tracking_enabled = :tracking_enabled WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'subject' => $subject,
            'content' => $content,
            'plain_text' => $plainText,
            'status' => $status,
            'scheduled_at' => $scheduledAt ?: null,
            'campaign_type' => $campaignType,
            'audience' => $audience,
            'tracking_enabled' => (int) $trackingEnabled,
        ]);

        $_SESSION['flash_message'] = 'Newsletter mise à jour.';
        header('Location: /newsletter');
        exit;
    }

    public function delete(): void
    {
        Auth::requireRole('admin');

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_message'] = 'Identifiant invalide.';
            header('Location: /newsletter');
            exit;
        }

        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM newsletters WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $_SESSION['flash_message'] = 'Newsletter supprimée.';
        header('Location: /newsletter');
        exit;
    }

    private function getNewsletterById(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT id, subject, content, plain_text, status, scheduled_at, campaign_type, audience, tracking_enabled, created_at, created_by FROM newsletters WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $newsletter = $stmt->fetch();

        return $newsletter ?: null;
    }

    private function createSendJobs(\PDO $pdo, int $newsletterId, string $audience): void
    {
        $query = 'SELECT id FROM subscribers WHERE status IN ("active", "pending")';
        if ($audience === 'new') {
            $query .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        } elseif ($audience === 'vip') {
            $query .= ' AND name IS NOT NULL AND name <> ""';
        }

        $subscribers = $pdo->query($query)->fetchAll();

        $sendJobColumns = $this->getExistingColumns($pdo, 'send_jobs');
        foreach ($subscribers as $subscriber) {
            $insertFields = ['newsletter_id', 'subscriber_id', 'status'];
            $insertValues = [
                'newsletter_id' => $newsletterId,
                'subscriber_id' => $subscriber['id'],
                'status' => 'pending',
            ];

            if (in_array('created_at', $sendJobColumns, true)) {
                $insertFields[] = 'created_at';
                $insertValues['created_at'] = date('Y-m-d H:i:s');
            }

            $insert = $pdo->prepare('INSERT INTO send_jobs (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', array_map(static fn (string $field): string => ':' . $field, $insertFields)) . ')');
            $insert->execute($insertValues);
        }
    }

    private function saveEmailTemplate(\PDO $pdo, string $name, string $category, string $subject, string $content, string $plainText, string $createdBy): void
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare('INSERT INTO email_templates (name, category, subject, content, plain_text, created_by, created_at, updated_at) VALUES (:name, :category, :subject, :content, :plain_text, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'name' => $name,
            'category' => $category,
            'subject' => $subject,
            'content' => $content,
            'plain_text' => $plainText,
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getEmailTemplates(\PDO $pdo): array
    {
        $this->ensureEmailTemplateTable($pdo);
        $this->seedDefaultEmailTemplates($pdo);

        $stmt = $pdo->prepare('SELECT id, name, category, subject, content, plain_text FROM email_templates ORDER BY category, name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function ensureEmailTemplateTable(\PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT "general",
            subject VARCHAR(255) NOT NULL,
            content MEDIUMTEXT NOT NULL,
            plain_text TEXT DEFAULT NULL,
            created_by VARCHAR(100) NOT NULL DEFAULT "system",
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX ix_email_templates_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->ensureMediumTextColumn($pdo, 'email_templates', 'content');
    }

    private function seedDefaultEmailTemplates(\PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');
        $defaultTemplates = EmailTemplateCatalog::getDefaultTemplates();
        $select = $pdo->prepare('SELECT COUNT(*) FROM email_templates WHERE name = :name');
        $insert = $pdo->prepare('INSERT INTO email_templates (name, category, subject, content, plain_text, created_by, created_at, updated_at) VALUES (:name, :category, :subject, :content, :plain_text, :created_by, :created_at, :updated_at)');

        foreach ($defaultTemplates as $template) {
            $select->execute(['name' => $template['name']]);
            $count = (int) $select->fetchColumn();
            if ($count > 0) {
                continue;
            }

            $insert->execute([
                'name' => $template['name'],
                'category' => $template['category'],
                'subject' => $template['subject'],
                'content' => $template['content'],
                'plain_text' => $template['plain_text'],
                'created_by' => 'system',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function ensureNewsletterColumns(\PDO $pdo): void
    {
        $columns = $this->getExistingColumns($pdo, 'newsletters');

        if (!in_array('subject', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN subject VARCHAR(255) NOT NULL");
        }

        if (!in_array('content', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN content MEDIUMTEXT NOT NULL");
        }

        if (!in_array('plain_text', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN plain_text TEXT DEFAULT NULL");
        }

        if (!in_array('created_by', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN created_by VARCHAR(100) NOT NULL DEFAULT 'unknown'");
        }

        if (!in_array('created_at', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN created_at DATETIME NOT NULL");
        }

        if (!in_array('scheduled_at', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN scheduled_at DATETIME DEFAULT NULL");
        }

        if (!in_array('status', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'");
        }

        if (!in_array('campaign_type', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN campaign_type VARCHAR(50) NOT NULL DEFAULT 'announcement'");
        }

        if (!in_array('audience', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN audience VARCHAR(50) NOT NULL DEFAULT 'all'");
        }

        if (!in_array('tracking_enabled', $columns, true)) {
            $pdo->exec("ALTER TABLE newsletters ADD COLUMN tracking_enabled TINYINT(1) NOT NULL DEFAULT 1");
        }

        $this->ensureMediumTextColumn($pdo, 'newsletters', 'content');
    }

    private function ensureMediumTextColumn(\PDO $pdo, string $table, string $column): void
    {
        $stmt = $pdo->prepare('SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        $columnInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$columnInfo) {
            return;
        }

        $dataType = strtolower($columnInfo['DATA_TYPE'] ?? '');
        if ($dataType !== 'mediumtext' && $dataType !== 'longtext') {
            $pdo->exec("ALTER TABLE {$table} MODIFY COLUMN {$column} MEDIUMTEXT NOT NULL");
        }
    }

    private function getExistingColumns(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return array_map('strtolower', $columns);
    }

    private function normalizeOption(string $value, string $default, array $allowed): string
    {
        $normalized = strtolower(trim($value));
        if (!in_array($normalized, $allowed, true)) {
            return $default;
        }

        return $normalized;
    }
}
