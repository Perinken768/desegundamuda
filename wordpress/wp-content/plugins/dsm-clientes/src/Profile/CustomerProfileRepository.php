<?php

declare(strict_types=1);

namespace DSM\Clientes\Profile;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerProfileRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName = $wpdb->prefix . 'dsm_customer_profiles';
    }

    public function findById(int $id): ?CustomerProfile
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    customer_id,
                    display_name,
                    phone,
                    whatsapp_phone,
                    avatar_attachment_id,
                    bio,
                    island_id,
                    municipality_id,
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

    public function findByCustomerId(int $customerId): ?CustomerProfile
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    id,
                    customer_id,
                    display_name,
                    phone,
                    whatsapp_phone,
                    avatar_attachment_id,
                    bio,
                    island_id,
                    municipality_id,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE customer_id = %d
                LIMIT 1",
                $customerId
            ),
            ARRAY_A
        );

        return $row === null
            ? null
            : $this->hydrate($row);
    }

    public function create(
        int $customerId,
        string $displayName
    ): CustomerProfile {
        global $wpdb;

        $displayName = trim($displayName);

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible del perfil no puede estar vacío.'
            );
        }

        if ($this->findByCustomerId($customerId) !== null) {
            throw new RuntimeException(
                'El cliente ya tiene un perfil asociado.'
            );
        }

        $now = current_time('mysql', true);

        $result = $wpdb->insert(
            $this->tableName,
            [
                'customer_id' => $customerId,
                'display_name' => $displayName,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el perfil del cliente.'
            );
        }

        $profile = $this->findById(
            (int) $wpdb->insert_id
        );

        if ($profile === null) {
            throw new RuntimeException(
                'El perfil fue creado pero no pudo recuperarse.'
            );
        }

        return $profile;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): CustomerProfile
    {
        return new CustomerProfile(
            (int) $row['id'],
            (int) $row['customer_id'],
            (string) $row['display_name'],
            $row['phone'] !== null
                ? (string) $row['phone']
                : null,
            $row['whatsapp_phone'] !== null
                ? (string) $row['whatsapp_phone']
                : null,
            $row['avatar_attachment_id'] !== null
                ? (int) $row['avatar_attachment_id']
                : null,
            $row['bio'] !== null
                ? (string) $row['bio']
                : null,
            $row['island_id'] !== null
                ? (int) $row['island_id']
                : null,
            $row['municipality_id'] !== null
                ? (int) $row['municipality_id']
                : null,
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }
}