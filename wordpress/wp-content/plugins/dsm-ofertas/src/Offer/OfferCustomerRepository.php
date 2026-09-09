<?php

declare(strict_types=1);

namespace DSM\Ofertas\Offer;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferCustomerRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_offer_customers';
    }

    /**
     * @return array<int, object>
     */
    public function findByOfferId(
        int $offerId
    ): array {
        global $wpdb;

        if ($offerId <= 0) {
            return [];
        }

        $customersTable =
            $wpdb->prefix
            . 'dsm_customers';

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        oc.id,
                        oc.offer_id,
                        oc.customer_id,
                        oc.status,
                        oc.internal_note,
                        oc.created_at,
                        oc.updated_at,

                        c.email,
                        c.status AS customer_status

                    FROM {$this->table} oc

                    INNER JOIN {$customersTable} c
                        ON c.id = oc.customer_id

                    WHERE oc.offer_id = %d

                    ORDER BY
                        CASE
                            WHEN oc.status = 'assigned'
                                THEN 0
                            ELSE 1
                        END ASC,
                        c.email ASC,
                        oc.id ASC
                    ",
                    $offerId
                )
            );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function findAssignment(
        int $offerId,
        int $customerId
    ): ?object {
        global $wpdb;

        if (
            $offerId <= 0
            || $customerId <= 0
        ) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->table}
                    WHERE offer_id = %d
                      AND customer_id = %d
                    LIMIT 1
                    ",
                    $offerId,
                    $customerId
                )
            );

        return is_object($row)
            ? $row
            : null;
    }

    public function assign(
        int $offerId,
        int $customerId,
        ?string $internalNote = null
    ): int {
        global $wpdb;

        if (
            $offerId <= 0
            || $customerId <= 0
        ) {
            throw new RuntimeException(
                'La oferta o el cliente no son válidos.'
            );
        }

        $internalNote =
            $internalNote !== null
                ? sanitize_textarea_field(
                    $internalNote
                )
                : null;

        $existing =
            $this->findAssignment(
                $offerId,
                $customerId
            );

        $now =
            current_time(
                'mysql',
                true
            );

        if ($existing !== null) {
            $updated =
                $wpdb->update(
                    $this->table,
                    [
                        'status' =>
                            'assigned',

                        'internal_note' =>
                            $internalNote,

                        'updated_at' =>
                            $now,
                    ],
                    [
                        'id' =>
                            (int) $existing->id,
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'No se pudo actualizar la asignación: '
                    . $wpdb->last_error
                );
            }

            return (int) $existing->id;
        }

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'offer_id' =>
                        $offerId,

                    'customer_id' =>
                        $customerId,

                    'status' =>
                        'assigned',

                    'internal_note' =>
                        $internalNote,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo asignar la oferta al cliente: '
                . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }

    public function revoke(
        int $offerId,
        int $customerId
    ): void {
        global $wpdb;

        if (
            $offerId <= 0
            || $customerId <= 0
        ) {
            throw new RuntimeException(
                'La oferta o el cliente no son válidos.'
            );
        }

        $updated =
            $wpdb->update(
                $this->table,
                [
                    'status' =>
                        'revoked',

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'offer_id' =>
                        $offerId,

                    'customer_id' =>
                        $customerId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                'No se pudo revocar la asignación: '
                . $wpdb->last_error
            );
        }
    }
}
