<?php

class Mailer
{
    private string $fromEmail;
    private string $fromName;
    private string $host;
    private int $port;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->fromEmail = getenv('SMTP_FROM') ?: 'info@example.com';
        $this->fromName = getenv('SMTP_FROM_NAME') ?: 'Newsletter';
        $this->host = getenv('SMTP_HOST') ?: '';
        $this->port = (int)(getenv('SMTP_PORT') ?: 25);
        $this->username = getenv('SMTP_USER') ?: '';
        $this->password = getenv('SMTP_PASS') ?: '';
    }

    public function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = sprintf('From: %s <%s>', $this->fromName, $this->fromEmail);
        $headers[] = sprintf('To: %s', $to);
        $headers[] = sprintf('Subject: %s', $this->encodeHeader($subject));

        $message = $html;
        if ($text !== '') {
            $boundary = 'boundary_' . md5(uniqid('', true));
            $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";

            $message = "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=utf-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $text . "\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=utf-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $html . "\r\n\r\n";
            $message .= "--$boundary--";
        } else {
            $headers[] = 'Content-type: text/html; charset=utf-8';
        }

        $headerString = implode("\r\n", $headers);

        if ($this->host !== '') {
            return $this->sendViaSmtp($to, $subject, $headerString, $message);
        }

        return mail($to, $subject, $message, $headerString);
    }

    private function sendViaSmtp(string $to, string $subject, string $headers, string $body): bool
    {
        $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$connection) {
            return false;
        }

        $response = $this->readSmtpResponse($connection);
        if (strpos($response, '220') !== 0) {
            fclose($connection);
            return false;
        }

        $this->sendSmtpCommand($connection, 'EHLO localhost');
        $this->readSmtpResponse($connection);

        if ($this->username !== '') {
            $this->sendSmtpCommand($connection, 'AUTH LOGIN');
            $this->readSmtpResponse($connection, 334);
            $this->sendSmtpCommand($connection, base64_encode($this->username));
            $this->readSmtpResponse($connection, 334);
            $this->sendSmtpCommand($connection, base64_encode($this->password));
            $this->readSmtpResponse($connection, 235);
        }

        $this->sendSmtpCommand($connection, 'MAIL FROM:<' . $this->fromEmail . '>');
        $this->readSmtpResponse($connection, 250);

        $this->sendSmtpCommand($connection, 'RCPT TO:<' . $to . '>');
        $this->readSmtpResponse($connection, 250);

        $this->sendSmtpCommand($connection, 'DATA');
        $this->readSmtpResponse($connection, 354);

        $data = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $data .= "To: {$to}\r\n";
        $data .= "Subject: {$this->encodeHeader($subject)}\r\n";
        $data .= $headers . "\r\n";
        $data .= "\r\n";
        $data .= $body . "\r\n.";

        fwrite($connection, $data . "\r\n");
        $this->readSmtpResponse($connection, 250);

        $this->sendSmtpCommand($connection, 'QUIT');
        fclose($connection);

        return true;
    }

    private function sendSmtpCommand($connection, string $command): void
    {
        fwrite($connection, $command . "\r\n");
    }

    private function readSmtpResponse($connection, int $expectedCode = 0): string
    {
        $response = '';
        while (!feof($connection)) {
            $line = fgets($connection, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if ($expectedCode > 0 && strpos($line, (string) $expectedCode) === 0) {
                break;
            }
            if ($expectedCode === 0 && strlen($line) >= 3 && substr($line, 3, 1) === ' ') {
                break;
            }
        }

        return $response;
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
