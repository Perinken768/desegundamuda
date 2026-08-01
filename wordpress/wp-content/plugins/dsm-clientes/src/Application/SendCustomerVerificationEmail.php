<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Authentication\EmailVerificationToken;
use DSM\Clientes\Customer\Customer;
use DSM\Core\Mail\MailerRegistry;
use RuntimeException;
use Throwable;

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
     * Genera un token de verificación y solicita su envío.
     *
     * El token sin hash se devuelve únicamente para facilitar
     * pruebas controladas durante el desarrollo.
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

        $tokenId = $this->repository->create(
            $customer->getId(),
            $tokenHash,
            DAY_IN_SECONDS
        );

        try {
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
                . "Confirma tu correo electrónico para activar tu cuenta "
                . "de DeSegundaMuda.\n\n"
                . "Enlace de verificación:\n%s\n\n"
                . "Este enlace caduca en 24 horas.\n\n"
                . "Si no has creado esta cuenta, puedes ignorar este mensaje.",
                $verificationUrl
            );

            $sent = MailerRegistry::get()->send(
                $customer->getEmail(),
                $subject,
                $message
            );

            if (!$sent) {
                throw new RuntimeException(
                    'No se pudo enviar el correo de verificación.'
                );
            }

            /*
             * Solo después de enviar correctamente el nuevo correo
             * invalidamos los enlaces anteriores.
             */
            $this->repository->revokeOtherPendingForCustomer(
                $customer->getId(),
                $tokenId
            );

            return $token;
        } catch (Throwable $exception) {
            /*
             * Si el correo no se envía, eliminamos el token recién
             * creado y conservamos cualquier enlace anterior.
             */
            try {
                $this->repository->deleteById($tokenId);
            } catch (Throwable) {
                /*
                 * Conservamos la excepción original del envío.
                 */
            }

            throw $exception;
        }
    }
}