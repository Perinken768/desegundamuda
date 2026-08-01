<?php

declare(strict_types=1);

namespace DSM\Clientes\Customer;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix . 'dsm_customers';
    }

    public function findById(int $id): ?Customer
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    email,
                    status,
                    email_verified_at,
                    last_login_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1",
                $id
            ),
            ARRAY_A
        );

        return $row === null
            ? null
            : $this->hydrate($row);
    }

    public function findByEmail(string $email): ?Customer
    {
        global $wpdb;

        $email = strtolower(trim($email));

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    email,
                    status,
                    email_verified_at,
                    last_login_at,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE email = %s
                LIMIT 1",
                $email
            ),
            ARRAY_A
        );

        return $row === null
            ? null
            : $this->hydrate($row);
    }

    public function emailExists(string $email): bool
    {
        global $wpdb;

        $email = strtolower(trim($email));

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM {$this->tableName}
                WHERE email = %s
                LIMIT 1",
                $email
            )
        );

        return $id !== null;
    }

    /**
     * @return array{
     *     id: int,
     *     password_hash: string,
     *     status: string
     * }|null
     */
    public function findCredentialsByEmail(
        string $email
    ): ?array {
        global $wpdb;

        $email = strtolower(trim($email));

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    password_hash,
                    status
                FROM {$this->tableName}
                WHERE email = %s
                LIMIT 1",
                $email
            ),
            ARRAY_A
        );

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'password_hash' =>
                (string) $row['password_hash'],
            'status' => (string) $row['status'],
        ];
    }

    public function create(
        string $email,
        string $passwordHash,
        string $status = CustomerStatus::PENDING
    ): Customer {
        global $wpdb;

        $email = strtolower(trim($email));

        if (!is_email($email)) {
            throw new RuntimeException(
                'El correo electrónico no es válido.'
            );
        }

        if (!CustomerStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado del cliente no es válido.'
            );
        }

        if ($this->emailExists($email)) {
            throw new RuntimeException(
                'Ya existe un cliente con este correo electrónico.'
            );
        }

        $now = current_time('mysql', true);

        $result = $wpdb->insert(
            $this->tableName,
            [
                'email' => $email,
                'password_hash' => $passwordHash,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el cliente.'
            );
        }

        return $this->requireCustomer(
            (int) $wpdb->insert_id,
            'El cliente fue creado pero no pudo recuperarse.'
        );
    }

    public function markEmailAsVerified(
        int $customerId
    ): Customer {
        global $wpdb;

        $this->assertValidCustomerId($customerId);

        $now = current_time('mysql', true);

        $result = $wpdb->update(
            $this->tableName,
            [
                'status' => CustomerStatus::ACTIVE,
                'email_verified_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $customerId,
            ],
            [
                '%s',
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo verificar el correo del cliente.'
            );
        }

        return $this->requireCustomer(
            $customerId,
            'El cliente verificado no pudo recuperarse.'
        );
    }

    public function updateStatus(
        int $customerId,
        string $status
    ): Customer {
        global $wpdb;

        $this->assertValidCustomerId($customerId);

        if (!CustomerStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado del cliente no es válido.'
            );
        }

        if ($this->findById($customerId) === null) {
            throw new RuntimeException(
                'No se encontró el cliente.'
            );
        }

        $result = $wpdb->update(
            $this->tableName,
            [
                'status' => $status,
                'updated_at' => current_time('mysql', true),
            ],
            [
                'id' => $customerId,
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar el estado del cliente.'
            );
        }

        return $this->requireCustomer(
            $customerId,
            'El cliente actualizado no pudo recuperarse.'
        );
    }

    public function updatePassword(
        int $customerId,
        string $plainPassword
    ): void {
        global $wpdb;

        $this->assertValidCustomerId($customerId);

        $plainPassword = trim($plainPassword);

        if (strlen($plainPassword) < 12) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 12 caracteres.'
            );
        }

        if ($this->findById($customerId) === null) {
            throw new RuntimeException(
                'No se encontró el cliente.'
            );
        }

        $result = $wpdb->update(
            $this->tableName,
            [
                'password_hash' =>
                    wp_hash_password($plainPassword),
                'updated_at' =>
                    current_time('mysql', true),
            ],
            [
                'id' => $customerId,
            ],
            [
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar la contraseña del cliente.'
            );
        }
    }

    private function assertValidCustomerId(
        int $customerId
    ): void {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }
    }

    private function requireCustomer(
        int $customerId,
        string $errorMessage
    ): Customer {
        $customer = $this->findById($customerId);

        if ($customer === null) {
            throw new RuntimeException($errorMessage);
        }

        return $customer;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Customer
    {
        return new Customer(
            (int) $row['id'],
            (string) $row['email'],
            (string) $row['status'],
            $row['email_verified_at'] !== null
                ? (string) $row['email_verified_at']
                : null,
            $row['last_login_at'] !== null
                ? (string) $row['last_login_at']
                : null,
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }
}