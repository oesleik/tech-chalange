<?php

declare(strict_types=1);

namespace App\Core\Email;

use App\Core\Config\EmailConfig;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService {
    public function __construct(private readonly EmailConfig $emailConfig) {}

    /**
     * @param array<int, array{email: string, name?: string}> $to
     */
    public function send(
        array $to,
        string $subject,
        string $body,
        bool $isHtml = true,
        ?string $altBody = null,
    ): bool {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host       = $this->emailConfig->getHost();
            $mailer->Port       = $this->emailConfig->getPort();
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $this->emailConfig->getUsername();
            $mailer->Password   = $this->emailConfig->getPassword();
            $mailer->SMTPSecure = $this->emailConfig->getEncryption();
            $mailer->CharSet    = PHPMailer::CHARSET_UTF8;

            $mailer->setFrom(
                $this->emailConfig->getFromAddress(),
                $this->emailConfig->getFromName(),
            );

            foreach ($to as $recipient) {
                $mailer->addAddress($recipient['email'], $recipient['name'] ?? '');
            }

            $mailer->isHTML($isHtml);
            $mailer->Subject = $subject;
            $mailer->Body    = $body;

            if ($altBody !== null) {
                $mailer->AltBody = $altBody;
            }

            return $mailer->send();
        } catch (PHPMailerException $e) {
            throw new EmailException("Falha ao enviar e-mail: {$mailer->ErrorInfo}");
        }
    }
}