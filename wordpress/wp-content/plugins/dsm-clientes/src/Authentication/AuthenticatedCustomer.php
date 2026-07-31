<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class AuthenticatedCustomer
{
    public function __construct(
        private readonly CustomerSessionRepository $sessionRepository,
        private readonly CustomerRepository $customerRepository
    ) {
    }

    public function resolve(): ?Customer
    {
        $token = CustomerCookie::get();

        if ($token === null) {
            return null;
        }

        $session = $this->sessionRepository->findByTokenHash(
            SessionToken::hash($token)
        );

        if ($session === null || !$session->isValid()) {
            CustomerCookie::clear();

            return null;
        }

        $customer = $this->customerRepository->findById(
            $session->getCustomerId()
        );

        if ($customer === null) {
            $this->sessionRepository->revoke($session->getId());
            CustomerCookie::clear();

            return null;
        }

        $this->sessionRepository->touch($session->getId());

        return $customer;
    }
}