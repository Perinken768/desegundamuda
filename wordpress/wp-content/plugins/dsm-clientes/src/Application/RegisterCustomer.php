<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use DSM\Clientes\Profile\CustomerProfile;
use DSM\Clientes\Profile\CustomerProfileRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class RegisterCustomer
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerProfileRepository $profileRepository
    ) {
    }

    /**
     * @return array{
     *     customer: Customer,
     *     profile: CustomerProfile
     * }
     */
    public function execute(
        string $email,
        string $password,
        string $displayName
    ): array {
        global $wpdb;

        $email       = strtolower(trim($email));
        $displayName = trim($displayName);

        $this->validateEmail($email);
        $this->validatePassword($password);
        $this->validateDisplayName($displayName);

        if ($this->customerRepository->emailExists($email)) {
            throw new RuntimeException(
                'Ya existe una cuenta con este correo electrónico.'
            );
        }

        $passwordHash = wp_hash_password($password);

        $wpdb->query('START TRANSACTION');

        try {
            $customer = $this->customerRepository->create(
                $email,
                $passwordHash,
                CustomerStatus::PENDING
            );

            $profile = $this->profileRepository->create(
                $customer->getId(),
                $displayName
            );

            $wpdb->query('COMMIT');

            return [
                'customer' => $customer,
                'profile'  => $profile,
            ];
        } catch (Throwable $exception) {
            $wpdb->query('ROLLBACK');

            throw $exception;
        }
    }

    private function validateEmail(string $email): void
    {
        if (!is_email($email)) {
            throw new RuntimeException(
                'El correo electrónico no es válido.'
            );
        }
    }

    private function validatePassword(string $password): void
    {
        if (mb_strlen($password) < 10) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 10 caracteres.'
            );
        }
    }

    private function validateDisplayName(string $displayName): void
    {
        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible no puede estar vacío.'
            );
        }

        if (mb_strlen($displayName) > 150) {
            throw new RuntimeException(
                'El nombre visible es demasiado largo.'
            );
        }
    }
}