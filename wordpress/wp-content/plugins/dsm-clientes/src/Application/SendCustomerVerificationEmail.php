<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Authentication\EmailVerificationToken;
use DSM\Clientes\Customer\Customer;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SendCustomerVerificationEmail
{
    public function __construct(
        private readonly EmailVerificationRepository $repository
    ) {
    }

    /**
     * Devuelve el token sin hash para facilitar pruebas controladas.
     */
    public function execute(Customer $customer): string
    {
        if ($customer->getEmailVerifiedAt() !== null) {
            throw new RuntimeException(
                'El correo del cliente ya está verificado.'
            );
        }

        $token = EmailVerificationToken::generate();
        $tokenHash = EmailVerificationToken::hash($token);

        $this->repository->create(
            $customer->getId(),
            $tokenHash,
            DAY_IN_SECONDS
        );

        $verificationUrl = add_query_arg(
            [
                'action' => 'dsm_customer_verify_email',
                'token' => $token,
            ],
            admin_url('admin-post.php')
        );

        $subject = sprintf(
            '[%s] Verifica tu correo electrónico',
            wp_specialchars_decode(
                get_bloginfo('name'),
                ENT_QUOTES
            )
        );

        $message = sprintf(
            "Hola,\n\n"
            . "Confirma tu correo electrónico para activar tu cuenta de DeSegundaMuda.\n\n"
            . "Enlace de verificación:\n%s\n\n"
            . "Este enlace caduca en 24 horas.\n\n"
            . "Si no has creado esta cuenta, puedes ignorar este mensaje.",
            $verificationUrl
        );

        wp_mail(
            $customer->getEmail(),
            $subject,
            $message
        );

        return $token;
    }
}