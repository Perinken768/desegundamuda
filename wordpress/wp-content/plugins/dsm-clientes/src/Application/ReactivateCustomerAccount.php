<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\AccountReactivationRepository;
use DSM\Clientes\Authentication\AccountReactivationToken;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ReactivateCustomerAccount
{
    public function __construct(
        private readonly AccountReactivationRepository $reactivationRepository,
        private readonly CustomerRepository $customerRepository
    ) {
    }

    public function execute(string $token): Customer
    {
        $token = trim($token);

        if (
            !AccountReactivationToken::isValidFormat(
                $token
            )
        ) {
            throw new RuntimeException(
                'El token de reactivación no es válido.'
            );
        }

        $reactivation =
            $this->reactivationRepository
                ->findPendingByTokenHash(
                    AccountReactivationToken::hash(
                        $token
                    )
                );

        if ($reactivation === null) {
            throw new RuntimeException(
                'El enlace de reactivación no es válido o ha caducado.'
            );
        }

        $customer =
            $this->customerRepository->findById(
                $reactivation['customer_id']
            );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró la cuenta.'
            );
        }

        if (
            $customer->getStatus()
            !== CustomerStatus::INACTIVE
        ) {
            throw new RuntimeException(
                'La cuenta no está disponible para reactivación.'
            );
        }

        $customer =
            $this->customerRepository->updateStatus(
                $customer->getId(),
                CustomerStatus::ACTIVE
            );

        $this->reactivationRepository->markAsUsed(
            $reactivation['id']
        );

        do_action(
            'dsm_customer_account_reactivated',
            $customer->getId()
        );

        do_action(
            'dsm_audit_event',
            'customer.reactivated',
            [
                'customer_id' => $customer->getId(),
                'actor_type' => 'customer',
            ]
        );

        return $customer;
    }
}
