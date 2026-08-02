<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use DSM\Core\Mail\MailerRegistry;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DeactivateCustomerAccount
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(
        int $customerId,
        string $password
    ): void {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $customer = $this->customerRepository->findById(
            $customerId
        );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró la cuenta del cliente.'
            );
        }

        $credentials = $this->customerRepository
            ->findCredentialsByEmail(
                $customer->getEmail()
            );

        if (
            $credentials === null
            || !wp_check_password(
                $password,
                $credentials['password_hash']
            )
        ) {
            throw new RuntimeException(
                'La contraseña introducida no es correcta.'
            );
        }

        if (
            $customer->getStatus()
            === CustomerStatus::DELETION_PENDING
        ) {
            throw new RuntimeException(
                'La cuenta ya tiene una eliminación pendiente.'
            );
        }

        if (
            CustomerStatus::isAdministrativeRestriction(
                $customer->getStatus()
            )
        ) {
            throw new RuntimeException(
                'Una cuenta restringida por administración no puede cerrarse desde el área privada.'
            );
        }

        $this->customerRepository->updateStatus(
            $customerId,
            CustomerStatus::INACTIVE
        );

        $this->sessionRepository->revokeAllForCustomer(
            $customerId
        );

        do_action(
            'dsm_customer_account_deactivated',
            $customerId
        );

        $subject = sprintf(
            '[%s] Tu cuenta se ha cerrado temporalmente',
            wp_specialchars_decode(
                get_bloginfo('name'),
                ENT_QUOTES
            )
        );

        $message =
            "Hola,\n\n"
            . "Tu cuenta de DeSegundaMuda se ha cerrado temporalmente.\n\n"
            . "Mientras permanezca inactiva no podrás iniciar sesión "
            . "ni utilizar las funciones asociadas a tu cuenta.\n\n"
            . "Tus datos no se han eliminado.\n\n"
            . "Si no has realizado esta acción, contacta con soporte.";

        MailerRegistry::get()->send(
            $customer->getEmail(),
            $subject,
            $message
        );
    }
}
