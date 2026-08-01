<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Authentication\EmailVerificationToken;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifyCustomerEmail
{
    public function __construct(
        private readonly EmailVerificationRepository $verificationRepository,
        private readonly CustomerRepository $customerRepository
    ) {
    }

    public function execute(string $token): Customer
    {
        $token = strtolower(trim($token));

        if (!EmailVerificationToken::isValidFormat($token)) {
            throw new RuntimeException(
                'El token de verificación no es válido.'
            );
        }

        $tokenHash = EmailVerificationToken::hash($token);

        $verification =
            $this->verificationRepository
                ->findUsableByTokenHash($tokenHash);

        if ($verification === null) {
            throw new RuntimeException(
                'El enlace de verificación no existe o ha caducado.'
            );
        }

        $customer =
            $this->customerRepository
                ->markEmailAsVerified(
                    $verification['customer_id']
                );

        $this->verificationRepository->markAsUsed(
            $verification['id']
        );

        return $customer;
    }
}