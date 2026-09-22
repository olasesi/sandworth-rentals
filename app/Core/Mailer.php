<?php

namespace App\Core;

final class Mailer
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromConfig()
    {
        $configPath = dirname(dirname(__DIR__)) . '/config/mail.php';

        if (! file_exists($configPath)) {
            return new self(array());
        }

        $config = require $configPath;

        return new self(is_array($config) ? $config : array());
    }

    public function enabled()
    {
        if (! isset($this->config['enabled']) || ! $this->config['enabled']) {
            return false;
        }

        $host = isset($this->config['host']) ? trim((string) $this->config['host']) : '';
        $port = isset($this->config['port']) ? (int) $this->config['port'] : 0;

        return $host !== '' && $port > 0;
    }

    public function send($toEmail, $toName, $subject, $bodyHtml)
    {
        $toEmail = trim((string) $toEmail);
        $toName = trim((string) $toName);
        $subject = trim((string) $subject);

        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return array(false, 'Recipient email address is not valid.');
        }

        if ($subject === '') {
            return array(false, 'Email subject is empty.');
        }

        if (! $this->enabled()) {
            return array(false, 'SMTP is not configured. Set SANDWORTH_MAIL_ENABLED=true and SMTP credentials.');
        }

        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

        if (! file_exists($vendorAutoload)) {
            return array(false, 'Composer autoload not found. Run composer install.');
        }

        require_once $vendorAutoload;

        if (! class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return array(false, 'PHPMailer class not available.');
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = (int) $this->config['port'];
            $mail->CharSet = 'UTF-8';

            $username = isset($this->config['username']) ? trim((string) $this->config['username']) : '';

            if ($username !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = isset($this->config['password']) ? (string) $this->config['password'] : '';
            }

            $encryption = isset($this->config['encryption']) ? strtolower(trim((string) $this->config['encryption'])) : '';

            if ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            $fromEmail = isset($this->config['from_email']) ? trim((string) $this->config['from_email']) : 'no-reply@sandworthliving.test';
            $fromName = isset($this->config['from_name']) ? trim((string) $this->config['from_name']) : 'Sandworth Homes';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);
            $mail->send();

            return array(true, '');
        } catch (\Exception $exception) {
            return array(false, $exception->getMessage());
        }
    }
}
