<?php

declare(strict_types=1);

namespace DSM\Directos\Direct;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'dsm_live_streams';
    }

    /**
     * Devuelve el único espacio de directo del cliente.
     *
     * @return array<string, mixed>|null
     */
    public function findByCustomerId(
        int $customerId
    ): ?array {
        global $wpdb;

        if ($customerId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        title,
                        slug,
                        description,
                        platform,
                        platform_url,
                        status,
                        starts_at,
                        ended_at,
                        created_at,
                        updated_at
                    FROM {$this->table}
                    WHERE customer_id = %d
                    LIMIT 1
                    ",
                    $customerId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(
        string $slug
    ): ?array {
        global $wpdb;

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        title,
                        slug,
                        description,
                        platform,
                        platform_url,
                        status,
                        starts_at,
                        ended_at,
                        created_at,
                        updated_at
                    FROM {$this->table}
                    WHERE slug = %s
                    LIMIT 1
                    ",
                    $slug
                ),
                ARRAY_A
            );

        return is_array($row)
            ? $row
            : null;
    }

    /**
     * Emisiones que están abiertas actualmente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findLive(
        int $limit = 100
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    250,
                    $limit
                )
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        title,
                        slug,
                        description,
                        platform,
                        platform_url,
                        status,
                        starts_at,
                        ended_at,
                        created_at,
                        updated_at
                    FROM {$this->table}
                    WHERE status = 'live'
                    ORDER BY
                        starts_at DESC,
                        id DESC
                    LIMIT %d
                    ",
                    $limit
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }


    /**
     * Todos los espacios públicos de directo.
     *
     * Se excluyen los que todavía están únicamente en
     * configuración inicial.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPublic(
        int $limit = 250
    ): array {
        global $wpdb;

        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT
                        id,
                        customer_id,
                        title,
                        slug,
                        description,
                        platform,
                        platform_url,
                        status,
                        starts_at,
                        ended_at,
                        created_at,
                        updated_at
                    FROM {$this->table}
                    WHERE status IN (
                        'live',
                        'closed'
                    )
                    ORDER BY
                        CASE
                            WHEN status = 'live'
                                THEN 0
                            ELSE 1
                        END ASC,
                        updated_at DESC,
                        id DESC
                    LIMIT %d
                    ",
                    $limit
                ),
                ARRAY_A
            );

        return is_array($rows)
            ? $rows
            : [];
    }


    /**
     * Crea o actualiza el espacio permanente de directo.
     */
    public function saveConfiguration(
        int $customerId,
        string $title,
        string $description,
        string $platform,
        string $platformUrl
    ): int {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al propietario del directo.'
            );
        }

        $title =
            trim(
                sanitize_text_field(
                    $title
                )
            );

        if ($title === '') {
            throw new RuntimeException(
                'El título de la emisión es obligatorio.'
            );
        }

        $description =
            trim(
                sanitize_textarea_field(
                    $description
                )
            );

        $platform =
            sanitize_key(
                $platform
            );

        $platformUrl =
            esc_url_raw(
                trim(
                    $platformUrl
                ),
                [
                    'http',
                    'https',
                ]
            );

        if ($platformUrl === '') {
            throw new RuntimeException(
                'La URL del directo no es válida.'
            );
        }

        $existing =
            $this->findByCustomerId(
                $customerId
            );

        $now =
            current_time(
                'mysql',
                true
            );

        if ($existing !== null) {
            $directId =
                (int) $existing['id'];

            $currentStatus =
                sanitize_key(
                    (string) (
                        $existing['status']
                        ?? 'setup'
                    )
                );

            /*
             * Configurar plataforma/título no cierra
             * automáticamente una emisión abierta.
             */
            $nextStatus =
                $currentStatus === 'live'
                    ? 'live'
                    : 'setup';

            $updated =
                $wpdb->update(
                    $this->table,
                    [
                        'title' =>
                            $title,

                        'description' =>
                            $description !== ''
                                ? $description
                                : null,

                        'platform' =>
                            $platform,

                        'platform_url' =>
                            $platformUrl,

                        'status' =>
                            $nextStatus,

                        'updated_at' =>
                            $now,
                    ],
                    [
                        'id' =>
                            $directId,

                        'customer_id' =>
                            $customerId,
                    ],
                    [
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    ],
                    [
                        '%d',
                        '%d',
                    ]
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'No se pudo actualizar Mi directo: '
                    . $wpdb->last_error
                );
            }

            return $directId;
        }

        $slug =
            'directo-'
            . $customerId;

        $inserted =
            $wpdb->insert(
                $this->table,
                [
                    'customer_id' =>
                        $customerId,

                    'title' =>
                        $title,

                    'slug' =>
                        $slug,

                    'description' =>
                        $description !== ''
                            ? $description
                            : null,

                    'platform' =>
                        $platform,

                    'platform_url' =>
                        $platformUrl,

                    'status' =>
                        'setup',

                    'starts_at' =>
                        null,

                    'ended_at' =>
                        null,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                'No se pudo configurar Mi directo: '
                . $wpdb->last_error
            );
        }

        $directId =
            (int) $wpdb->insert_id;

        if ($directId <= 0) {
            throw new RuntimeException(
                'No se obtuvo un identificador válido para Mi directo.'
            );
        }

        return $directId;
    }
}
