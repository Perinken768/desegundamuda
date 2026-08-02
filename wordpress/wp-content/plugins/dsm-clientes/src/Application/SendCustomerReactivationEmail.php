<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\AccountReactivationRepository;
use DSM\Clientes\Authentication\AccountReactivationToken;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerStatus;
use DSM\Core\Mail\MailerRegistry;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class SendCustomerReactivationEmail
{
    public function __construct(
        private readonly AccountReactivationRepository $repository
    ) {
    }

    public function execute(Customer $customer): void
    {
        if (
            $customer->getStatus()
            !== CustomerStatus::INACTIVE
        ) {
            throw new RuntimeException(
                'La cuenta no está cerrada temporalmente.'
            );
        }

        $token = AccountReactivationToken::generate();

        $tokenId = $this->repository->create(
            $customer->getId(),
            AccountReactivationToken::hash($token),
            DAY_IN_SECONDS
        );

        try {
            $reactivationUrl = add_query_arg(
                [
                    'action' =>
                        'dsm_customer_confirm_reactivation',
                    'token' => $token,
                ],
                admin_url('admin-post.php')
            );

            $subject = sprintf(
                '[%s] Reactiva tu cuenta',
                wp_specialchars_decode(
                    get_bloginfo('name'),
                    ENT_QUOTES
                )
            );

            $message = sprintf(
                "Hola,\n\n"
                . "Hemos recibido una solicitud para reactivar "
                . "tu cuenta de DeSegundaMuda.\n\n"
                . "Pulsa el siguiente enlace:\n%s\n\n"
                . "El enlace caduca en 24 horas.\n\n"
                . "Si no has solicitado la reactivación, "
                . "puedes ignorar este mensaje.",
                $reactivationUrl
            );

            MailerRegistry::get()->send(
                $customer->getEmail(),
                $subject,
                $message
            );

            do_action(
                'dsm_audit_event',
                'customer.reactivation_requested',
                [
                    'customer_id' =>
                        $customer->getId(),
                    'actor_type' => 'customer',
                ]
            );
        } catch (Throwable $exception) {
            try {
                $this->repository->deleteById(
                    $tokenId
                );
            } catch (Throwable) {
            }

            throw $exception;
        }
    }
}
