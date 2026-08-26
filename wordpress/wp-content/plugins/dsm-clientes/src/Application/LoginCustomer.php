<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\LoginResult;
use DSM\Clientes\Customer\CustomerRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginCustomer
{
    private AuthenticateCustomer $authenticateCustomer;

    private CreateCustomerLoginSession $createSession;

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerSessionRepository $sessionRepository
    ) {
        $this->authenticateCustomer =
            new AuthenticateCustomer(
                $customerRepository
            );

        $this->createSession =
            new CreateCustomerLoginSession(
                $sessionRepository
            );
    }

    public function execute(
        string $email,
        string $password,
        ?string $ipAddress,
        ?string $userAgent
    ): LoginResult {
        $customer =
            $this->authenticateCustomer->execute(
                $email,
                $password
            );

        return $this->createSession->execute(
            $customer,
            $ipAddress,
            $userAgent
        );
    }
}
