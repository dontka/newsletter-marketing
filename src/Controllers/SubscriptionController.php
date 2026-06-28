<?php

require_once __DIR__ . '/../Lib/DB.php';
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/View.php';

class SubscriptionController
{
    public function showForm(): void
    {
        View::render('subscribe');
    }

    public function subscribe(): void
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $name = trim($_POST['name'] ?? '');

        if (!$email) {
            View::render('message', ['message' => 'Email invalide.']);
            return;
        }

        try {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare('SELECT id, status FROM subscribers WHERE email = :email');
            $stmt->execute(['email' => $email]);
            $subscriber = $stmt->fetch();

            if ($subscriber) {
                if ($subscriber['status'] === 'active') {
                    View::render('message', ['message' => 'Cet email est déjà inscrit et confirmé.']);
                    return;
                }

                $token = bin2hex(random_bytes(32));
                $update = $pdo->prepare('UPDATE subscribers SET name = :name, token = :token, status = :status WHERE id = :id');
                $update->execute([
                    'name' => $name,
                    'token' => $token,
                    'status' => 'pending',
                    'id' => $subscriber['id'],
                ]);
            } else {
                $token = bin2hex(random_bytes(32));
                $insert = $pdo->prepare('INSERT INTO subscribers (email, name, token, status, created_at) VALUES (:email, :name, :token, :status, :created_at)');
                $insert->execute([
                    'email' => $email,
                    'name' => $name ?: null,
                    'token' => $token,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->sendConfirmationEmail($email, $token);
            View::render('message', ['message' => 'Merci ! Un email de confirmation a été envoyé.']);
        } catch (Exception $e) {
            View::render('message', ['message' => 'Une erreur est survenue lors de l\'inscription.']);
        }
    }

    private function sendConfirmationEmail(string $email, string $token): void
    {
        $mailer = new Mailer();
        $confirmUrl = sprintf('%s/confirm?token=%s', getenv('BASE_URL') ?: '', urlencode($token));
        $subject = 'Confirmez votre abonnement';

        $text = "Bonjour !\n\nMerci pour votre inscription. Pour finaliser votre abonnement, veuillez confirmer votre adresse email en visitant ce lien :\n{$confirmUrl}";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmez votre abonnement</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fb; font-family:Arial, sans-serif; color:#2d3748;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fb; padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); padding:32px 24px; text-align:center; color:#ffffff;">
              <div style="font-size:28px; font-weight:bold; margin-bottom:8px;">📧 Newsletter</div>
              <div style="font-size:15px; opacity:0.95;">Confirmez votre inscription en un clic</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 24px;">
              <h2 style="margin:0 0 12px; font-size:24px; color:#2d3748;">Bonjour !</h2>
              <p style="margin:0 0 16px; font-size:16px; line-height:1.6; color:#4a5568;">Merci pour votre inscription. Pour finaliser votre abonnement et recevoir nos prochains contenus, veuillez confirmer votre adresse email.</p>
              <p style="margin:0 0 24px; text-align:center;">
                <a href="{$confirmUrl}" style="display:inline-block; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:999px; font-weight:bold;">Confirmer mon inscription</a>
              </p>
              <p style="margin:0; font-size:13px; color:#718096; line-height:1.5;">Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur : <br><span style="word-break:break-all;"> <a href="{$confirmUrl}" style="color:#667eea; text-decoration:underline;">{$confirmUrl}</a></span></p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px 24px; text-align:center; font-size:12px; color:#a0aec0; border-top:1px solid #edf2f7;">
              Cet email a été envoyé à partir de votre système de newsletter.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
        $mailer->send($email, $subject, $html, $text);
    }
}
