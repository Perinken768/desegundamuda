<?php

declare(strict_types=1);

namespace DSM\Anuncios\Image;

use RuntimeException;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de imágenes asociadas a anuncios.
 *
 * Gestiona exclusivamente la relación almacenada en:
 *
 * wp_dsm_ad_images
 *
 * No crea ni elimina archivos físicos de la biblioteca
 * multimedia de WordPress. Esa responsabilidad pertenece
 * a AdvertisementImageService.
 */
final class AdvertisementImageRepository
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
            . 'dsm_ad_images';
    }

    /**
     * Busca una imagen por su identificador interno.
     */
    public function findById(
        int $imageId
    ): ?AdvertisementImage {
        if ($imageId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
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

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? AdvertisementImage::fromArray(
                $row
            )
            : null;
    }

    /**
     * Busca una imagen mediante el anuncio y el adjunto.
     */
    public function findByAdvertisementAndAttachment(
        int $advertisementId,
        int $attachmentId
    ): ?AdvertisementImage {
        if (
            $advertisementId <= 0
            || $attachmentId <= 0
        ) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                  AND attachment_id = %d
                LIMIT 1
                ",
                $advertisementId,
                $attachmentId
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
            ? AdvertisementImage::fromArray(
                $row
            )
            : null;
    }

    /**
     * Devuelve todas las imágenes de un anuncio.
     *
     * La portada aparece primero y, después, se respeta
     * el orden manual establecido.
     *
     * @return array<int, AdvertisementImage>
     */
    public function findByAdvertisementId(
        int $advertisementId
    ): array {
        if ($advertisementId <= 0) {
            return [];
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ORDER BY
                    is_cover DESC,
                    sort_order ASC,
                    id ASC
                ",
                $advertisementId
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
            if (!is_array($row)) {
                continue;
            }

            $images[] =
                AdvertisementImage::fromArray(
                    $row
                );
        }

        return $images;
    }

    /**
     * Devuelve la imagen de portada de un anuncio.
     */
    public function findCoverByAdvertisementId(
        int $advertisementId
    ): ?AdvertisementImage {
        if ($advertisementId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    advertisement_id,
                    attachment_id,
                    sort_order,
                    is_cover,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                  AND is_cover = 1
                ORDER BY
                    sort_order ASC,
                    id ASC
                LIMIT 1
                ",
                $advertisementId
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
            ? AdvertisementImage::fromArray(
                $row
            )
            : null;
    }

    /**
     * Crea una relación entre un anuncio y un adjunto.
     *
     * Devuelve la entidad recién creada.
     */
    public function create(
        int $advertisementId,
        int $attachmentId,
        ?int $sortOrder = null,
        bool $isCover = false
    ): AdvertisementImage {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($attachmentId <= 0) {
            throw new RuntimeException(
                'El identificador del adjunto no es válido.'
            );
        }

        $existing =
            $this->findByAdvertisementAndAttachment(
                $advertisementId,
                $attachmentId
            );

        if ($existing !== null) {
            throw new RuntimeException(
                'La imagen ya está asociada a este anuncio.'
            );
        }

        $sortOrder =
            $sortOrder !== null
                ? max(
                    0,
                    $sortOrder
                )
                : $this->getNextSortOrder(
                    $advertisementId
                );

        $now =
            $this->now();

        $result =
            $this->database->insert(
                $this->tableName,
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'attachment_id' =>
                        $attachmentId,

                    'sort_order' =>
                        $sortOrder,

                    /*
                     * La portada se asigna después mediante
                     * setCover(), para garantizar que solo
                     * exista una portada por anuncio.
                     */
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
                'No se pudo asociar la imagen al anuncio.'
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
                $advertisementId,
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

    /**
     * Actualiza el orden de una imagen.
     */
    public function updateSortOrder(
        int $imageId,
        int $sortOrder
    ): AdvertisementImage {
        if ($imageId <= 0) {
            throw new RuntimeException(
                'El identificador de la imagen no es válido.'
            );
        }

        if ($sortOrder < 0) {
            throw new RuntimeException(
                'El orden de la imagen no puede ser negativo.'
            );
        }

        $existing =
            $this->findById(
                $imageId
            );

        if ($existing === null) {
            throw new RuntimeException(
                'No se encontró la imagen indicada.'
            );
        }

        $result =
            $this->database->update(
                $this->tableName,
                [
                    'sort_order' =>
                        $sortOrder,

                    'updated_at' =>
                        $this->now(),
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
                'No se pudo actualizar el orden de la imagen.'
            );
        }

        $updated =
            $this->findById(
                $imageId
            );

        if ($updated === null) {
            throw new RuntimeException(
                'La imagen se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updated;
    }

    /**
     * Establece una única portada para el anuncio.
     *
     * Primero desmarca todas las imágenes y después marca
     * únicamente la indicada.
     */
    public function setCover(
        int $advertisementId,
        int $imageId
    ): AdvertisementImage {
        if (
            $advertisementId <= 0
            || $imageId <= 0
        ) {
            throw new RuntimeException(
                'Los identificadores de anuncio e imagen no son válidos.'
            );
        }

        $image =
            $this->findById(
                $imageId
            );

        if (
            $image === null
            || !$image->belongsToAdvertisement(
                $advertisementId
            )
        ) {
            throw new RuntimeException(
                'La imagen no pertenece al anuncio indicado.'
            );
        }

        $this->database->query(
            'START TRANSACTION'
        );

        try {
            $cleared =
                $this->database->update(
                    $this->tableName,
                    [
                        'is_cover' =>
                            0,

                        'updated_at' =>
                            $this->now(),
                    ],
                    [
                        'advertisement_id' =>
                            $advertisementId,
                    ],
                    [
                        '%d',
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );

            if ($cleared === false) {
                throw new RuntimeException(
                    'No se pudieron desmarcar las portadas anteriores.'
                );
            }

            $marked =
                $this->database->update(
                    $this->tableName,
                    [
                        'is_cover' =>
                            1,

                        'updated_at' =>
                            $this->now(),
                    ],
                    [
                        'id' =>
                            $imageId,

                        'advertisement_id' =>
                            $advertisementId,
                    ],
                    [
                        '%d',
                        '%s',
                    ],
                    [
                        '%d',
                        '%d',
                    ]
                );

            if ($marked === false) {
                throw new RuntimeException(
                    'No se pudo establecer la imagen de portada.'
                );
            }

            $this->database->query(
                'COMMIT'
            );
        } catch (\Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }

        $updated =
            $this->findById(
                $imageId
            );

        if ($updated === null) {
            throw new RuntimeException(
                'La portada se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updated;
    }

    /**
     * Elimina una relación de imagen.
     *
     * No elimina el adjunto físico de WordPress.
     */
    public function delete(
        int $imageId
    ): bool {
        if ($imageId <= 0) {
            return false;
        }

        $image =
            $this->findById(
                $imageId
            );

        if ($image === null) {
            return false;
        }

        $deleted =
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

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudo eliminar la relación de la imagen.'
            );
        }

        /*
         * Si se eliminó la portada, asignamos como nueva portada
         * la primera imagen restante.
         */
        if ($image->isCover()) {
            $this->ensureCoverExists(
                $image->getAdvertisementId()
            );
        }

        return $deleted > 0;
    }

    /**
     * Elimina todas las relaciones de imágenes de un anuncio.
     *
     * No elimina los adjuntos físicos.
     */
    public function deleteByAdvertisementId(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        $deleted =
            $this->database->delete(
                $this->tableName,
                [
                    'advertisement_id' =>
                        $advertisementId,
                ],
                [
                    '%d',
                ]
            );

        if ($deleted === false) {
            throw new RuntimeException(
                'No se pudieron eliminar las imágenes del anuncio.'
            );
        }

        return (int) $deleted;
    }

    /**
     * Devuelve el número de imágenes asociadas al anuncio.
     */
    public function countByAdvertisementId(
        int $advertisementId
    ): int {
        if ($advertisementId <= 0) {
            return 0;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return 0;
        }

        return max(
            0,
            (int) $this->database
                ->get_var(
                    $sql
                )
        );
    }

    /**
     * Reordena completamente las imágenes del anuncio.
     *
     * @param array<int, int> $orderedImageIds
     */
    public function reorder(
        int $advertisementId,
        array $orderedImageIds
    ): void {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $currentImages =
            $this->findByAdvertisementId(
                $advertisementId
            );

        $validImageIds = [];

        foreach ($currentImages as $image) {
            $validImageIds[
                $image->getId()
            ] = true;
        }

        $normalizedIds = [];

        foreach ($orderedImageIds as $imageId) {
            $imageId =
                (int) $imageId;

            if (
                $imageId <= 0
                || !isset(
                    $validImageIds[
                        $imageId
                    ]
                )
                || isset(
                    $normalizedIds[
                        $imageId
                    ]
                )
            ) {
                continue;
            }

            $normalizedIds[
                $imageId
            ] = true;
        }

        /*
         * Las imágenes omitidas se añaden al final,
         * conservando su orden actual.
         */
        foreach ($currentImages as $image) {
            $imageId =
                $image->getId();

            if (!isset($normalizedIds[$imageId])) {
                $normalizedIds[
                    $imageId
                ] = true;
            }
        }

        $this->database->query(
            'START TRANSACTION'
        );

        try {
            $sortOrder = 0;

            foreach (
                array_keys($normalizedIds)
                as $imageId
            ) {
                $result =
                    $this->database->update(
                        $this->tableName,
                        [
                            'sort_order' =>
                                $sortOrder,

                            'updated_at' =>
                                $this->now(),
                        ],
                        [
                            'id' =>
                                $imageId,

                            'advertisement_id' =>
                                $advertisementId,
                        ],
                        [
                            '%d',
                            '%s',
                        ],
                        [
                            '%d',
                            '%d',
                        ]
                    );

                if ($result === false) {
                    throw new RuntimeException(
                        'No se pudo actualizar el orden de las imágenes.'
                    );
                }

                $sortOrder++;
            }

            $this->database->query(
                'COMMIT'
            );
        } catch (\Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    /**
     * Garantiza que un anuncio con imágenes tenga portada.
     */
    public function ensureCoverExists(
        int $advertisementId
    ): void {
        if ($advertisementId <= 0) {
            return;
        }

        $cover =
            $this->findCoverByAdvertisementId(
                $advertisementId
            );

        if ($cover !== null) {
            return;
        }

        $images =
            $this->findByAdvertisementId(
                $advertisementId
            );

        if ($images === []) {
            return;
        }

        $this->setCover(
            $advertisementId,
            $images[0]->getId()
        );
    }

    /**
     * Calcula el siguiente orden disponible.
     */
    private function getNextSortOrder(
        int $advertisementId
    ): int {
        $sql =
            $this->database->prepare(
                "
                SELECT COALESCE(
                    MAX(sort_order),
                    -1
                )
                FROM {$this->tableName}
                WHERE advertisement_id = %d
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return 0;
        }

        $currentMaximum =
            (int) $this->database
                ->get_var(
                    $sql
                );

        return max(
            0,
            $currentMaximum + 1
        );
    }

    /**
     * Fecha UTC para almacenamiento.
     */
    private function now(): string
    {
        return current_time(
            'mysql',
            true
        );
    }
}