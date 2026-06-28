<?php
/*
 * Cron script pour envoyer les newsletters planifiées.
 * Exemples de planification cron :
 * 5 * * * * php /path/to/scripts/send_cron.php
 */

require_once __DIR__ . '/../src/Lib/Env.php';
Env::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../src/Lib/DB.php';
require_once __DIR__ . '/../src/Lib/Mailer.php';

class SendCron
{
    private \PDO $pdo;
    private Mailer $mailer;
    private int $batchSize = 100;
    private int $maxRetries = 3;
    private string $logFile;

    public function __construct()
    {
        $this->pdo = DB::getConnection();
        $this->mailer = new Mailer();
        $this->logFile = __DIR__ . '/../logs/send_cron.log';
        @mkdir(dirname($this->logFile), 0755, true);
    }

    public function run(): void
    {
        $this->log("=== Démarrage du cron d'envoi ===");

        try {
            // Passer les newsletters "scheduled" à "sending" si la date est dépassée
            $this->startScheduledNewsletters();

            // Envoyer les emails "pending"
            $this->sendPendingEmails();

            $this->log("=== Cron terminé avec succès ===");
        } catch (Exception $e) {
            $this->log("Erreur: " . $e->getMessage());
        }
    }

    private function startScheduledNewsletters(): void
    {
        $now = date('Y-m-d H:i:s');
        $update = $this->pdo->prepare('
            UPDATE newsletters 
            SET status = :sending 
            WHERE status = :scheduled AND scheduled_at <= :now
        ');
        $update->execute([
            'sending' => 'sending',
            'scheduled' => 'scheduled',
            'now' => $now,
        ]);

        $count = $update->rowCount();
        if ($count > 0) {
            $this->log("$count newsletter(s) passée(s) à l'état 'sending'");
        }
    }

    private function sendPendingEmails(): void
    {
        while (true) {
            $stmt = $this->pdo->prepare('
                SELECT sj.id, sj.newsletter_id, sj.subscriber_id, sj.attempts,
                       n.subject, n.content, n.plain_text,
                       s.email
                FROM send_jobs sj
                JOIN newsletters n ON sj.newsletter_id = n.id
                JOIN subscribers s ON sj.subscriber_id = s.id
                WHERE sj.status = :pending AND sj.attempts < :max_retries
                ORDER BY sj.id ASC
                LIMIT :batch_size
            ');
            $stmt->bindValue(':pending', 'pending', \PDO::PARAM_STR);
            $stmt->bindValue(':max_retries', $this->maxRetries, \PDO::PARAM_INT);
            $stmt->bindValue(':batch_size', $this->batchSize, \PDO::PARAM_INT);
            $stmt->execute();
            $jobs = $stmt->fetchAll();

            if (empty($jobs)) {
                break;
            }

            foreach ($jobs as $job) {
                $this->sendEmail($job);
            }

            $this->log(count($jobs) . " email(s) traité(s)");
        }
    }

    private function sendEmail(array $job): void
    {
        $email = $job['email'];
        $subject = $job['subject'];
        $content = $job['content'];
        $plainText = $job['plain_text'];
        $jobId = $job['id'];

        // Ajouter lien de désabonnement
        $unsubscribeUrl = sprintf('%s/unsubscribe?token=%s', getenv('BASE_URL') ?: '', urlencode($job['token'] ?? ''));

        // Améliorer le contenu avec le lien de désabonnement
        if (strpos($content, '{unsubscribe_link}') !== false) {
            $content = str_replace('{unsubscribe_link}', '<a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">Se désabonner</a>', $content);
        } else {
            $content .= "\n<p><a href=\"" . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . "\">Se désabonner</a></p>";
        }

        try {
            $success = $this->mailer->send($email, $subject, $content, $plainText);

            if ($success) {
                $update = $this->pdo->prepare('
                    UPDATE send_jobs 
                    SET status = :sent, sent_at = :sent_at 
                    WHERE id = :id
                ');
                $update->execute([
                    'sent' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                    'id' => $jobId,
                ]);
                $this->log("Email envoyé à $email (job: $jobId)");
            } else {
                $this->handleFailedEmail($jobId);
            }
        } catch (Exception $e) {
            $this->log("Erreur pour $email (job: $jobId): " . $e->getMessage());
            $this->handleFailedEmail($jobId, $e->getMessage());
        }

        // Throttling pour respecter les limites SMTP
        usleep(500000); // 0.5 secondes
    }

    private function handleFailedEmail(int $jobId, string $error = ''): void
    {
        $update = $this->pdo->prepare('
            UPDATE send_jobs 
            SET attempts = attempts + 1, last_error = :error 
            WHERE id = :id
        ');
        $update->execute([
            'error' => $error,
            'id' => $jobId,
        ]);
    }

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $line, FILE_APPEND);
        echo $line;
    }
}

$cron = new SendCron();
$cron->run();
