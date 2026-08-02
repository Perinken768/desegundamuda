<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\PasswordResetRepository;
use DSM\Clientes\Authentication\PasswordResetToken;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ResetCustomerPassword
{
    public function __construct(
        private readonly PasswordResetRepository $resetRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(
        string $token,
        string $password,
        string $passwordConfirmation
    ): void {
        $token = trim($token);

        if (!PasswordResetToken::isValidFormat($token)) {
            throw new RuntimeException(
                'El enlace de recuperación no es válido.'
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException(
                'Las contraseñas no coinciden.'
            );
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 12 caracteres.'
            );
        }

        $request = $this->resetRepository
            ->findPendingByTokenHash(
                PasswordResetToken::hash($token)
            );

        if ($request === null) {
            throw new RuntimeException(
                'El enlace de recuperación no es válido o ha caducado.'
            );
        }

        $customer = $this->customerRepository->findById(
            $request['customer_id']
        );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró la cuenta.'
            );
        }

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
                'La cuenta no está disponible.'
            );
        }

        $this->customerRepository->updatePassword(
            $customer->getId(),
            $password
        );

        $this->sessionRepository->revokeAllForCustomer(
            $customer->getId()
        );

        $this->resetRepository->markAsUsed(
            $request['id']
        );

        do_action(
            'dsm_customer_password_reset',
            $customer->getId()
        );

        do_action(
            'dsm_audit_event',
            'customer.password_reset',
            [
                'customer_id' => $customer->getId(),
                'actor_type' => 'customer',
            ]
        );
    }
}