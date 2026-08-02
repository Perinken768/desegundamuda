<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\PasswordResetRepository;
use DSM\Clientes\Authentication\PasswordResetToken;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerStatus;
use DSM\Core\Mail\MailerRegistry;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class RequestCustomerPasswordReset
{
    public function __construct(
        private readonly PasswordResetRepository $repository
    ) {
    }

    public function execute(Customer $customer): void
    {
        if (
            in_array(
                $customer->getStatus(),
                [
                    CustomerStatus::BLOCKED,
                    CustomerStatus::SUSPENDED,
                    CustomerStatus::DELETION_PENDING,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'La cuenta no está disponible para recuperar la contraseña.'
            );
        }

        $token = PasswordResetToken::generate();

        $requestId = $this->repository->create(
            $customer->getId(),
            PasswordResetToken::hash($token),
            HOUR_IN_SECONDS
        );

        try {
            $resetUrl = add_query_arg(
                'token',
                $token,
                home_url('/restablecer-contrasena/')
            );

            $subject = sprintf(
                '[%s] Restablece tu contraseña',
                wp_specialchars_decode(
                    get_bloginfo('name'),
                    ENT_QUOTES
                )
            );

            $message = sprintf(
                "Hola,\n\n"
                . "Hemos recibido una solicitud para restablecer "
                . "la contraseña de tu cuenta de DeSegundaMuda.\n\n"
                . "Pulsa el siguiente enlace:\n%s\n\n"
                . "El enlace caduca en 1 hora y solo puede utilizarse "
                . "una vez.\n\n"
                . "Si no has solicitado este cambio, ignora el mensaje.",
                $resetUrl
            );

            MailerRegistry::get()->send(
                $customer->getEmail(),
                $subject,
                $message
            );

            do_action(
                'dsm_audit_event',
                'customer.password_reset_requested',
                [
                    'customer_id' => $customer->getId(),
                    'actor_type' => 'customer',
                ]
            );
        } catch (Throwable $exception) {
            try {
                $this->repository->deleteById($requestId);
            } catch (Throwable) {
            }

            throw $exception;
        }
    }
}