<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use DSM\Clientes\Customer\Customer;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginResult
{
    public function __construct(
        private readonly Customer $customer,
        private readonly CustomerSession $session,
        private readonly string $token
    ) {
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getSession(): CustomerSession
    {
        return $this->session;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}