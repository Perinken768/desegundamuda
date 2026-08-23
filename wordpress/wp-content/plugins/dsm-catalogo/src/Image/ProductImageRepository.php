<?php

declare(strict_types=1);

namespace DSM\Catalogo\Image;

use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductImageRepository
{
    private wpdb $database;

    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->database =
            $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_product_images';
    }

    public function findById(
        int $imageId
    ): ?ProductImage {
        if ($imageId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    product_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $imageId
            );

        $row =
            is_string($sql)
                ? $this->database->get_row(
                    $sql,
                    ARRAY_A
                )
                : null;

        return is_array($row)
            ? ProductImage::fromArray(
                $row
            )
            : null;
    }

    /**
     * @return array<int, ProductImage>
     */
    public function findByProductId(
        int $productId
    ): array {
        if ($productId <= 0) {
            return [];
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    product_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE product_id = %d
                ORDER BY
                    is_cover DESC,
                    sort_order ASC,
                    id ASC
                ",
                $productId
            );

        if (!is_string($sql)) {
            return [];
        }

        $rows =
            $this->database->get_results(
                $sql,
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $images = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $images[] =
                    ProductImage::fromArray(
                        $row
                    );
            }
        }

        return $images;
    }

    public function findCoverByProductId(
        int $productId
    ): ?ProductImage {
        if ($productId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    product_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE product_id = %d
                  AND is_cover = 1
                ORDER BY
                    sort_order ASC,
                    id ASC
                LIMIT 1
                ",
                $productId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? ProductImage::fromArray(
                $row
            )
            : null;
    }

    public function countByProductId(
        int $productId
    ): int {
        if ($productId <= 0) {
            return 0;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE product_id = %d
                ",
                $productId
            );

        return is_string($sql)
            ? max(
                0,
                (int) $this->database
                    ->get_var(
                        $sql
                    )
            )
            : 0;
    }

    public function create(
        int $productId,
        int $attachmentId,
        ?int $sortOrder = null,
        bool $isCover = false
    ): ProductImage {
        if (
            $productId <= 0
            || $attachmentId <= 0
        ) {
            throw new RuntimeException(
                'Los identificadores de producto y adjunto deben ser válidos.'
            );
        }

        $sortOrder =
            $sortOrder
            ?? $this->getNextSortOrder(
                $productId
            );

        $now =
            current_time(
                'mysql',
                true
            );

        $result =
            $this->database->insert(
                $this->tableName,
                [
                    'product_id' =>
                        $productId,

                    'attachment_id' =>
                        $attachmentId,

                    'sort_order' =>
                        max(
                            0,
                            $sortOrder
                        ),

                    'is_cover' =>
                        0,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo asociar la imagen al producto.'
            );
        }

        $imageId =
            (int) $this->database
                ->insert_id;

        if ($imageId <= 0) {
            throw new RuntimeException(
                'La imagen se guardó, pero no se obtuvo su identificador.'
            );
        }

        if ($isCover) {
            $this->setCover(
                $productId,
                $imageId
            );
        }

        $image =
            $this->findById(
                $imageId
            );

        if ($image === null) {
            throw new RuntimeException(
                'La imagen se guardó, pero no pudo recuperarse.'
            );
        }

        return $image;
    }

    public function setCover(
        int $productId,
        int $imageId
    ): ProductImage {
        $image =
            $this->findById(
                $imageId
            );

        if (
            $image === null
            || !$image->belongsToProduct(
                $productId
            )
        ) {
            throw new RuntimeException(
                'La imagen no pertenece al producto indicado.'
            );
        }

        $this->database->query(
            $this->database->prepare(
                "
                UPDATE {$this->tableName}
                SET
                    is_cover = 0,
                    updated_at = %s
                WHERE product_id = %d
                ",
                current_time(
                    'mysql',
                    true
                ),
                $productId
            )
        );

        $result =
            $this->database->update(
                $this->tableName,
                [
                    'is_cover' =>
                        1,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $imageId,
                ],
                [
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo establecer la portada del producto.'
            );
        }

        return $this->findById(
            $imageId
        )
        ?? throw new RuntimeException(
            'No se pudo recuperar la imagen de portada.'
        );
    }

    public function ensureCoverExists(
        int $productId
    ): void {
        if (
            $this->findCoverByProductId(
                $productId
            ) !== null
        ) {
            return;
        }

        $images =
            $this->findByProductId(
                $productId
            );

        if ($images === []) {
            return;
        }

        $this->setCover(
            $productId,
            $images[0]->getId()
        );
    }

    public function deleteById(
        int $imageId
    ): void {
        $image =
            $this->findById(
                $imageId
            );

        if ($image === null) {
            return;
        }

        $result =
            $this->database->delete(
                $this->tableName,
                [
                    'id' =>
                        $imageId,
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo eliminar la relación de imagen.'
            );
        }

        $this->ensureCoverExists(
            $image->getProductId()
        );
    }

    private function getNextSortOrder(
        int $productId
    ): int {
        $sql =
            $this->database->prepare(
                "
                SELECT COALESCE(
                    MAX(sort_order),
                    -1
                )
                FROM {$this->tableName}
                WHERE product_id = %d
                ",
                $productId
            );

        $maximum =
            is_string($sql)
                ? (int) $this->database
                    ->get_var(
                        $sql
                    )
                : -1;

        return $maximum + 1;
    }
}
