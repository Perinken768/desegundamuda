<?php

declare(strict_types=1);

namespace DSM\Mfa\Mail;

use DSM\Core\Mail\MailerRegistry;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class MfaCodeMailer
{
    public function send(
        string $email,
        string $code
    ): void {
        $email =
            sanitize_email(
                $email
            );

        if (
            $email === ''
            || !is_email(
                $email
            )
        ) {
            throw new RuntimeException(
                'La dirección de correo del desafío MFA no es válida.'
            );
        }

        if (
            !preg_match(
                '/^\d{6}$/',
                $code
            )
        ) {
            throw new RuntimeException(
                'El código MFA debe contener exactamente 6 dígitos.'
            );
        }

        if (
            !MailerRegistry::has()
        ) {
            throw new RuntimeException(
                'El servicio de correo de DeSegundaMuda no está disponible.'
            );
        }

        $subject =
            'Tu código de acceso a DeSegundaMuda';

        $message =
            "Tu código de verificación es:\n\n"
            . $code
            . "\n\n"
            . "Este código caduca en 10 minutos.\n\n"
            . "Si no has intentado iniciar sesión en "
            . "DeSegundaMuda, puedes ignorar este correo.\n\n"
            . "DeSegundaMuda";

        MailerRegistry::get()->send(
            $email,
            $subject,
            $message
        );
    }

    public function __construct()
    {
    }
}
