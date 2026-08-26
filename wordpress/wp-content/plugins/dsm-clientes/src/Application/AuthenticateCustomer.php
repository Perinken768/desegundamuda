<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AuthenticateCustomer
{
    public function __construct(
        private readonly CustomerRepository $customerRepository
    ) {
    }

    public function execute(
        string $email,
        string $password
    ): Customer {
        $email =
            strtolower(
                trim(
                    $email
                )
            );

        if (!is_email($email)) {
            throw new RuntimeException(
                'Las credenciales no son válidas.'
            );
        }

        $credentials =
            $this->customerRepository
                ->findCredentialsByEmail(
                    $email
                );

        if (
            $credentials === null
            || !wp_check_password(
                $password,
                $credentials[
                    'password_hash'
                ]
            )
        ) {
            throw new RuntimeException(
                'Las credenciales no son válidas.'
            );
        }

        if (
            !CustomerStatus::canAuthenticate(
                $credentials[
                    'status'
                ]
            )
        ) {
            throw new RuntimeException(
                'La cuenta no está disponible para iniciar sesión.'
            );
        }

        $customer =
            $this->customerRepository
                ->findById(
                    $credentials[
                        'id'
                    ]
                );

        if ($customer === null) {
            throw new RuntimeException(
                'No se pudo recuperar la cuenta.'
            );
        }

        return $customer;
    }
}
