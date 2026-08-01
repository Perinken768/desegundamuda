<?php

declare(strict_types=1);

namespace DSM\Mail\Smtp;

use DSM\Mail\Config\MailSettings;
use PHPMailer\PHPMailer\PHPMailer;

if (!defined('ABSPATH')) {
    exit;
}

final class SmtpConfigurator
{
    public function __construct(
        private readonly MailSettings $settings
    ) {
    }

    public function register(): void
    {
        add_action(
            'phpmailer_init',
            [$this, 'configure'],
            10,
            1
        );

        add_filter(
            'wp_mail_from',
            [$this, 'filterFromEmail']
        );

        add_filter(
            'wp_mail_from_name',
            [$this, 'filterFromName']
        );
    }

    public function configure(PHPMailer $mailer): void
    {
        $mailer->isSMTP();

        $mailer->Host = $this->settings->getHost();
        $mailer->Port = $this->settings->getPort();

        $mailer->SMTPAuth =
            $this->settings->isAuthenticationEnabled();

        if ($mailer->SMTPAuth) {
            $mailer->Username =
                $this->settings->getUsername();

            $mailer->Password =
                $this->settings->getPassword();
        }

        switch ($this->settings->getEncryption()) {
            case 'tls':
                $mailer->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;

                $mailer->SMTPAutoTLS = true;
                break;

            case 'ssl':
                $mailer->SMTPSecure =
                    PHPMailer::ENCRYPTION_SMTPS;

                $mailer->SMTPAutoTLS = false;
                break;

            default:
                $mailer->SMTPSecure = '';
                $mailer->SMTPAutoTLS = false;
                break;
        }

        $mailer->CharSet = 'UTF-8';
        $mailer->Timeout = 20;

        /*
         * La depuración SMTP detallada no debe permanecer activa
         * durante los envíos normales, porque puede saturar la
         * respuesta FastCGI y provocar un error 502 en Nginx.
         */
        $mailer->SMTPDebug = 0;
        $mailer->Debugoutput = 'error_log';
    }

    public function filterFromEmail(string $email): string
    {
        return $this->settings->getFromEmail();
    }

    public function filterFromName(string $name): string
    {
        return $this->settings->getFromName();
    }
}