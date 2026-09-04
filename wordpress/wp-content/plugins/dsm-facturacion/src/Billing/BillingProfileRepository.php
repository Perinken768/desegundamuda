<?php

declare(strict_types=1);

namespace DSM\Facturacion\Billing;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingProfileRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_billing_profiles';
    }

    public function findByCustomerId(
        int $customerId
    ): ?BillingProfile {
        global $wpdb;

        if ($customerId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE customer_id = %d
                    LIMIT 1
                    ",
                    $customerId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(
        int $customerId,
        array $data
    ): BillingProfile {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El cliente no es válido.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $existing =
            $this->findByCustomerId(
                $customerId
            );

        $values = [
            'customer_id' =>
                $customerId,

            'customer_type' =>
                (string) (
                    $data[
                        'customer_type'
                    ]
                    ?? 'individual'
                ),

            'fiscal_name' =>
                (string) (
                    $data[
                        'fiscal_name'
                    ]
                    ?? ''
                ),

            'tax_id' =>
                self::nullable(
                    $data[
                        'tax_id'
                    ]
                    ?? null
                ),

            'address_line_1' =>
                self::nullable(
                    $data[
                        'address_line_1'
                    ]
                    ?? null
                ),

            'address_line_2' =>
                self::nullable(
                    $data[
                        'address_line_2'
                    ]
                    ?? null
                ),

            'postal_code' =>
                self::nullable(
                    $data[
                        'postal_code'
                    ]
                    ?? null
                ),

            'city' =>
                self::nullable(
                    $data[
                        'city'
                    ]
                    ?? null
                ),

            'province' =>
                self::nullable(
                    $data[
                        'province'
                    ]
                    ?? null
                ),

            'country_code' =>
                (string) (
                    $data[
                        'country_code'
                    ]
                    ?? 'ES'
                ),

            'billing_email' =>
                self::nullable(
                    $data[
                        'billing_email'
                    ]
                    ?? null
                ),

            'updated_at' =>
                $now,
        ];

        if ($existing === null) {
            $values['created_at'] =
                $now;

            $result =
                $wpdb->insert(
                    $this->table,
                    $values
                );
        } else {
            $result =
                $wpdb->update(
                    $this->table,
                    $values,
                    [
                        'customer_id' =>
                            $customerId,
                    ]
                );
        }

        if ($result === false) {
            throw new RuntimeException(
                'No se pudieron guardar los datos fiscales del cliente: '
                . $wpdb->last_error
            );
        }

        $profile =
            $this->findByCustomerId(
                $customerId
            );

        if ($profile === null) {
            throw new RuntimeException(
                'No se pudo recuperar el perfil fiscal guardado.'
            );
        }

        return $profile;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): BillingProfile {
        return new BillingProfile(
            id:
                (int) $row['id'],

            customerId:
                (int) $row['customer_id'],

            customerType:
                (string) $row['customer_type'],

            fiscalName:
                (string) $row['fiscal_name'],

            taxId:
                self::nullable(
                    $row['tax_id']
                    ?? null
                ),

            addressLine1:
                self::nullable(
                    $row['address_line_1']
                    ?? null
                ),

            addressLine2:
                self::nullable(
                    $row['address_line_2']
                    ?? null
                ),

            postalCode:
                self::nullable(
                    $row['postal_code']
                    ?? null
                ),

            city:
                self::nullable(
                    $row['city']
                    ?? null
                ),

            province:
                self::nullable(
                    $row['province']
                    ?? null
                ),

            countryCode:
                (string) $row['country_code'],

            billingEmail:
                self::nullable(
                    $row['billing_email']
                    ?? null
                ),

            createdAt:
                (string) $row['created_at'],

            updatedAt:
                (string) $row['updated_at']
        );
    }

    private static function nullable(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}
