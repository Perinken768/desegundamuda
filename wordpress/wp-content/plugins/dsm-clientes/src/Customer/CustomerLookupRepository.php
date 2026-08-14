<?php

declare(strict_types=1);

namespace DSM\Clientes\Customer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Búsqueda ligera de clientes para integraciones internas.
 *
 * Está pensado para selectores administrativos de otros
 * módulos de DeSegundaMuda.
 */
final class CustomerLookupRepository
{
    private string $customersTable;

    private string $profilesTable;

    public function __construct()
    {
        global $wpdb;

        $this->customersTable =
            $wpdb->prefix
            . 'dsm_customers';

        $this->profilesTable =
            $wpdb->prefix
            . 'dsm_customer_profiles';
    }

    /**
     * Busca clientes activos por:
     *
     * - nombre;
     * - correo;
     * - teléfono;
     * - ID exacto.
     *
     * @return array<int, array{
     *     id: int,
     *     email: string,
     *     status: string,
     *     display_name: string,
     *     phone: string
     * }>
     */
    public function searchActive(
        string $search,
        int $limit = 10
    ): array {
        global $wpdb;

        $search =
            trim(
                sanitize_text_field(
                    $search
                )
            );

        if ($search === '') {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    20,
                    $limit
                )
            );

        $like =
            '%'
            . $wpdb->esc_like(
                $search
            )
            . '%';

        $numericId =
            ctype_digit($search)
                ? (int) $search
                : 0;

        if ($numericId > 0) {
            $sql =
                $wpdb->prepare(
                    "SELECT
                        c.id,
                        c.email,
                        c.status,
                        COALESCE(
                            p.display_name,
                            ''
                        ) AS display_name,
                        COALESCE(
                            p.phone,
                            ''
                        ) AS phone
                    FROM {$this->customersTable} c
                    LEFT JOIN {$this->profilesTable} p
                        ON p.customer_id = c.id
                    WHERE
                        c.status = %s
                        AND (
                            c.id = %d
                            OR c.email LIKE %s
                            OR p.display_name LIKE %s
                            OR p.phone LIKE %s
                        )
                    ORDER BY
                        CASE
                            WHEN c.id = %d
                            THEN 0
                            ELSE 1
                        END,
                        p.display_name ASC,
                        c.email ASC
                    LIMIT %d",
                    CustomerStatus::ACTIVE,
                    $numericId,
                    $like,
                    $like,
                    $like,
                    $numericId,
                    $limit
                );
        } else {
            $sql =
                $wpdb->prepare(
                    "SELECT
                        c.id,
                        c.email,
                        c.status,
                        COALESCE(
                            p.display_name,
                            ''
                        ) AS display_name,
                        COALESCE(
                            p.phone,
                            ''
                        ) AS phone
                    FROM {$this->customersTable} c
                    LEFT JOIN {$this->profilesTable} p
                        ON p.customer_id = c.id
                    WHERE
                        c.status = %s
                        AND (
                            c.email LIKE %s
                            OR p.display_name LIKE %s
                            OR p.phone LIKE %s
                        )
                    ORDER BY
                        p.display_name ASC,
                        c.email ASC
                    LIMIT %d",
                    CustomerStatus::ACTIVE,
                    $like,
                    $like,
                    $like,
                    $limit
                );
        }

        $rows =
            $wpdb->get_results(
                $sql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static function (
                array $row
            ): array {
                return [
                    'id' =>
                        (int) (
                            $row['id']
                            ?? 0
                        ),

                    'email' =>
                        (string) (
                            $row['email']
                            ?? ''
                        ),

                    'status' =>
                        (string) (
                            $row['status']
                            ?? ''
                        ),

                    'display_name' =>
                        (string) (
                            $row['display_name']
                            ?? ''
                        ),

                    'phone' =>
                        (string) (
                            $row['phone']
                            ?? ''
                        ),
                ];
            },
            $rows
        );
    }
}