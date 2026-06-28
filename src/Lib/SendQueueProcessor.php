<?php

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Mailer.php';

class SendQueueProcessor
{
    private \PDO $pdo;
    private Mailer $mailer;
    private int $batchSize = 100;
    private int $maxRetries = 3;
    private string $logFile;

    public function __construct(?\PDO $pdo = null, ?Mailer $mailer = null, ?string $logFile = null)
    {
        $this->pdo = $pdo ?? DB::getConnection();
        $this->mailer = $mailer ?? new Mailer();
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/send_cron.log';
        @mkdir(dirname($this->logFile), 0755, true);
    }

    public function run(?callable $logger = null): array
    {
        $messages = [];
        $this->log("=== Démarrage du cron d'envoi ===", $logger, $messages);

        $this->startScheduledNewsletters($logger, $messages);
        $processed = $this->sendPendingEmails($logger, $messages);

        $this->log("=== Cron terminé avec succès ===", $logger, $messages);

        return [
            'processed' => $processed,
            'pending' => $this->getPendingJobsCount(),
            'messages' => $messages,
        ];
    }

    private function startScheduledNewsletters(?callable $logger, array &$messages): void
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
            $this->log("$count newsletter(s) passée(s) à l'état 'sending'", $logger, $messages);
        }
    }

    public function prepareQueue(): array
    {
        $messages = [];
        $this->startScheduledNewsletters(null, $messages);
        $totalJobs = $this->getPendingJobsCount();

        return [
            'totalJobs' => $totalJobs,
            'pending' => $totalJobs,
            'messages' => $messages,
        ];
    }

    public function processBatch(?int $batchSize = null, ?callable $logger = null): array
    {
        $processed = 0;
        $messages = [];
        $batchSize = $batchSize ?: 10;

        $stmt = $this->pdo->prepare('
            SELECT sj.id, sj.newsletter_id, sj.subscriber_id, sj.attempts,
                   n.subject, n.content, n.plain_text,
                   s.email, s.token
            FROM send_jobs sj
            JOIN newsletters n ON sj.newsletter_id = n.id
            JOIN subscribers s ON sj.subscriber_id = s.id
            WHERE sj.status = :pending AND sj.attempts < :max_retries
            ORDER BY sj.id ASC
            LIMIT :batch_size
        ');
        $stmt->bindValue(':pending', 'pending', \PDO::PARAM_STR);
        $stmt->bindValue(':max_retries', $this->maxRetries, \PDO::PARAM_INT);
        $stmt->bindValue(':batch_size', $batchSize, \PDO::PARAM_INT);
        $stmt->execute();
        $jobs = $stmt->fetchAll();

        if (empty($jobs)) {
            return [
                'processed' => 0,
                'pending' => $this->getPendingJobsCount(),
                'messages' => $messages,
                'done' => true,
            ];
        }

        foreach ($jobs as $job) {
            $this->sendEmail($job, $logger, $messages);
            $processed++;
        }

        $this->log(count($jobs) . " email(s) traité(s)", $logger, $messages);

        return [
            'processed' => $processed,
            'pending' => $this->getPendingJobsCount(),
            'messages' => $messages,
            'done' => false,
        ];
    }

    private function sendEmail(array $job, ?callable $logger, array &$messages): void
    {
        $email = $job['email'];
        $subject = $job['subject'];
        $content = $job['content'];
        $plainText = $job['plain_text'];
        $jobId = $job['id'];

        $unsubscribeUrl = sprintf('%s/unsubscribe?token=%s', getenv('BASE_URL') ?: '', urlencode($job['token'] ?? ''));

        if (strpos($content, '{unsubscribe_link}') !== false) {
            $content = str_replace('{unsubscribe_link}', '<a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">Se désabonner</a>', $content);
        } else {
            $content .= "\n<p style=\"margin: 24px 0 0; font-size: 13px; color: #64748b;\"><a href=\"" . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . "\" style=\"color: #64748b; text-decoration: underline;\">Se désabonner</a></p>";
        }

        $content = $this->wrapEmailContent($content);

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
                $this->log("Email envoyé à $email (job: $jobId)", $logger, $messages);
            } else {
                $this->handleFailedEmail($jobId);
            }
        } catch (Exception $e) {
            $this->log("Erreur pour $email (job: $jobId): " . $e->getMessage(), $logger, $messages);
            $this->handleFailedEmail($jobId, $e->getMessage());
        }

        usleep(500000);
    }

    private function wrapEmailContent(string $content): string
    {
        $safeContent = trim($content);
        if ($safeContent === '') {
            $safeContent = '<p>Bonjour,</p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <title>Newsletter</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    Contenu de votre newsletter
  </div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7fb;margin:0;padding:0;width:100%;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;">
          <tr>
            <td style="padding:0;">
              {$safeContent}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
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

    private function log(string $message, ?callable $logger, array &$messages): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $line, FILE_APPEND);
        $messages[] = $message;

        if ($logger !== null) {
            $logger($message);
        }
    }

    private function getPendingJobsCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "pending"');
        return (int) $stmt->fetchColumn();
    }
}
