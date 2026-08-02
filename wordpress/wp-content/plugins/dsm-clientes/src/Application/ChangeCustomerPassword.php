<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ChangeCustomerPassword
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(
        Customer $customer,
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirmation
    ): void {
        if ($newPassword !== $newPasswordConfirmation) {
            throw new RuntimeException(
                'Las contraseñas nuevas no coinciden.'
            );
        }

        if (mb_strlen($newPassword) < 12) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 12 caracteres.'
            );
        }

        $credentials = $this->customerRepository
            ->findCredentialsByEmail(
                $customer->getEmail()
            );

        if (
            $credentials === null
            || !wp_check_password(
                $currentPassword,
                $credentials['password_hash']
            )
        ) {
            throw new RuntimeException(
                'La contraseña actual no es correcta.'
            );
        }

        if (
            wp_check_password(
                $newPassword,
                $credentials['password_hash']
            )
        ) {
            throw new RuntimeException(
                'La contraseña nueva debe ser diferente de la actual.'
            );
        }

        $this->customerRepository->updatePassword(
            $customer->getId(),
            $newPassword
        );

        $this->sessionRepository->revokeAllForCustomer(
            $customer->getId()
        );

        do_action(
            'dsm_customer_password_changed',
            $customer->getId()
        );

        do_action(
            'dsm_audit_event',
            'customer.password_changed',
            [
                'customer_id' => $customer->getId(),
                'actor_type' => 'customer',
            ]
        );
    }
}