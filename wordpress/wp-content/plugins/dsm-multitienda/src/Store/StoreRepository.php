<?php

declare(strict_types=1);

namespace DSM\Multitienda\Store;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_multistore_stores';
    }

    public function findById(
        int $storeId
    ): ?Store {
        global $wpdb;

        if ($storeId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE id = %d
                    LIMIT 1",
                    $storeId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Store::fromArray(
                $row
            )
            : null;
    }

    public function findByCustomerId(
        int $customerId
    ): ?Store {
        global $wpdb;

        if ($customerId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE customer_id = %d
                    LIMIT 1",
                    $customerId
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Store::fromArray(
                $row
            )
            : null;
    }

    /**
     * @return array<int, Store>
     */
    public function findActive(
        int $limit = 250,
        int $offset = 0
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

        $offset =
            max(
                0,
                $offset
            );

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        id,
                        customer_id,
                        slug,
                        name,
                        description,
                        logo_attachment_id,
                        island,
                        location_text,
                        status,
                        created_at,
                        updated_at
                    FROM {$this->tableName}
                    WHERE status = %s
                    ORDER BY
                        updated_at DESC,
                        id DESC
                    LIMIT %d
                    OFFSET %d",
                    StoreStatus::ACTIVE,
                    $limit,
                    $offset
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $stores = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $stores[] =
                Store::fromArray(
                    $row
                );
        }

        return $stores;
    }

    public function findBySlug(
        string $slug
    ): ?Store {
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
                    "SELECT *
                    FROM {$this->tableName}
                    WHERE slug = %s
                    LIMIT 1",
                    $slug
                ),
                ARRAY_A
            );

        return is_array($row)
            ? Store::fromArray(
                $row
            )
            : null;
    }

    public function create(
        int $customerId,
        string $name,
        string $slug,
        ?string $description = null,
        ?int $logoAttachmentId = null,
        ?string $island = null,
        ?string $locationText = null,
        string $status = StoreStatus::DRAFT
    ): Store {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if (
            $this->findByCustomerId(
                $customerId
            ) !== null
        ) {
            throw new RuntimeException(
                'El cliente ya tiene una tienda asociada.'
            );
        }

        $name =
            trim(
                sanitize_text_field(
                    $name
                )
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la tienda es obligatorio.'
            );
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            throw new RuntimeException(
                'El slug de la tienda no es válido.'
            );
        }

        if (
            $this->findBySlug(
                $slug
            ) !== null
        ) {
            throw new RuntimeException(
                'Ya existe una tienda con ese slug.'
            );
        }

        if (!StoreStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado de la tienda no es válido.'
            );
        }

        $description =
            self::normalizeNullableText(
                $description
            );

        $island =
            self::normalizeNullableText(
                $island
            );

        $locationText =
            self::normalizeNullableText(
                $locationText
            );

        if (
            $logoAttachmentId !== null
            && $logoAttachmentId <= 0
        ) {
            $logoAttachmentId =
                null;
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $inserted =
            $wpdb->insert(
                $this->tableName,
                [
                    'customer_id' =>
                        $customerId,

                    'slug' =>
                        $slug,

                    'name' =>
                        $name,

                    'description' =>
                        $description,

                    'logo_attachment_id' =>
                        $logoAttachmentId,

                    'island' =>
                        $island,

                    'location_text' =>
                        $locationText,

                    'status' =>
                        $status,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ]
            );

        if ($inserted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la tienda: %s',
                    $wpdb->last_error
                )
            );
        }

        $store =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($store === null) {
            throw new RuntimeException(
                'La tienda se creó, pero no pudo recuperarse.'
            );
        }

        return $store;
    }

    public function updateProfile(
        int $storeId,
        string $name,
        string $slug,
        ?string $description,
        ?int $logoAttachmentId,
        ?string $island,
        ?string $locationText
    ): Store {
        global $wpdb;

        $store =
            $this->findById(
                $storeId
            );

        if ($store === null) {
            throw new RuntimeException(
                'No se encontró la tienda.'
            );
        }

        $name =
            trim(
                sanitize_text_field(
                    $name
                )
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la tienda es obligatorio.'
            );
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            throw new RuntimeException(
                'El slug de la tienda no es válido.'
            );
        }

        $existingBySlug =
            $this->findBySlug(
                $slug
            );

        if (
            $existingBySlug !== null
            && $existingBySlug->getId()
                !== $storeId
        ) {
            throw new RuntimeException(
                'Ya existe una tienda con ese slug.'
            );
        }

        if (
            $logoAttachmentId !== null
            && $logoAttachmentId <= 0
        ) {
            $logoAttachmentId =
                null;
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'name' =>
                        $name,

                    'slug' =>
                        $slug,

                    'description' =>
                        self::normalizeNullableText(
                            $description
                        ),

                    'logo_attachment_id' =>
                        $logoAttachmentId,

                    'island' =>
                        self::normalizeNullableText(
                            $island
                        ),

                    'location_text' =>
                        self::normalizeNullableText(
                            $locationText
                        ),

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $storeId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar la tienda: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireStore(
            $storeId
        );
    }

    public function updateStatus(
        int $storeId,
        string $status
    ): Store {
        global $wpdb;

        if (!StoreStatus::isValid($status)) {
            throw new RuntimeException(
                'El estado de la tienda no es válido.'
            );
        }

        if (
            $this->findById(
                $storeId
            ) === null
        ) {
            throw new RuntimeException(
                'No se encontró la tienda.'
            );
        }

        $updated =
            $wpdb->update(
                $this->tableName,
                [
                    'status' =>
                        $status,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $storeId,
                ]
            );

        if ($updated === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo cambiar el estado de la tienda: %s',
                    $wpdb->last_error
                )
            );
        }

        return $this->requireStore(
            $storeId
        );
    }

    private function requireStore(
        int $storeId
    ): Store {
        $store =
            $this->findById(
                $storeId
            );

        if ($store === null) {
            throw new RuntimeException(
                'La tienda no pudo recuperarse.'
            );
        }

        return $store;
    }

    private static function normalizeNullableText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                sanitize_text_field(
                    $value
                )
            );

        return $value !== ''
            ? $value
            : null;
    }
}
