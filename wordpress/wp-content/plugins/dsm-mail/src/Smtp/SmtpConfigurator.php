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
         * Diagnóstico SMTP únicamente en desarrollo.
         *
         * No registra la contraseña, pero sí la conversación
         * técnica necesaria para localizar fallos de conexión
         * o autenticación.
         */
        if (
            defined('WP_DEBUG')
            && WP_DEBUG
            && defined('WP_ENVIRONMENT_TYPE')
            && WP_ENVIRONMENT_TYPE === 'development'
        ) {
            $mailer->SMTPDebug = 2;

            $mailer->Debugoutput = static function (
                string $message,
                int $level
            ): void {
                error_log(
                    sprintf(
                        '[DSM Mail SMTP][Nivel %d] %s',
                        $level,
                        trim($message)
                    )
                );
            };
        }
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