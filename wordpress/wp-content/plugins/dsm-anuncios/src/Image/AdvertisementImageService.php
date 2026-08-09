<?php

declare(strict_types=1);

namespace DSM\Anuncios\Image;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use RuntimeException;
use Throwable;
use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Servicio de gestión de imágenes de anuncios.
 *
 * Responsabilidades:
 *
 * - validar que el anuncio exista;
 * - comprobar que pertenece al cliente;
 * - impedir cambios cuando el anuncio no sea editable;
 * - validar adjuntos de la biblioteca multimedia;
 * - aplicar el límite máximo de imágenes;
 * - asociar imágenes;
 * - asignar portada;
 * - reordenar imágenes;
 * - eliminar relaciones;
 * - eliminar opcionalmente el adjunto físico de WordPress.
 *
 * La persistencia de las relaciones se delega en
 * AdvertisementImageRepository.
 */
final class AdvertisementImageService
{
    public const DEFAULT_MAX_IMAGES =
        10;

    public const ABSOLUTE_MAX_IMAGES =
        30;

    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly AdvertisementImageRepository $imageRepository
    ) {
    }

    /**
     * Devuelve todas las imágenes del anuncio después de
     * comprobar que pertenece al cliente.
     *
     * @return array<int, AdvertisementImage>
     */
    public function getImagesForCustomer(
        int $customerId,
        int $advertisementId
    ): array {
        $this->resolveOwnedAdvertisement(
            $customerId,
            $advertisementId,
            false
        );

        return $this->imageRepository
            ->findByAdvertisementId(
                $advertisementId
            );
    }

    /**
     * Asocia un adjunto existente de WordPress a un anuncio.
     *
     * El primer adjunto asociado se convierte automáticamente
     * en portada.
     */
    public function addExistingAttachment(
        int $customerId,
        int $advertisementId,
        int $attachmentId,
        bool $setAsCover = false
    ): AdvertisementImage {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId,
                true
            );

        $this->validateAttachment(
            $attachmentId
        );

        $existing =
            $this->imageRepository
                ->findByAdvertisementAndAttachment(
                    $advertisementId,
                    $attachmentId
                );

        if ($existing !== null) {
            /*
             * Si ya existe y se solicita como portada,
             * actualizamos la portada en lugar de fallar.
             */
            if ($setAsCover) {
                return $this->imageRepository
                    ->setCover(
                        $advertisementId,
                        $existing->getId()
                    );
            }

            return $existing;
        }

        $currentImageCount =
            $this->imageRepository
                ->countByAdvertisementId(
                    $advertisementId
                );

        $maximumImages =
            $this->resolveMaximumImages(
                $advertisement
            );

        if (
            $currentImageCount
            >= $maximumImages
        ) {
            throw new RuntimeException(
                sprintf(
                    'El anuncio no puede tener más de %d imágenes.',
                    $maximumImages
                )
            );
        }

        /*
         * La primera imagen siempre se convierte en portada.
         */
        $mustBeCover =
            $setAsCover
            || $currentImageCount === 0;

        $image =
            $this->imageRepository->create(
                $advertisementId,
                $attachmentId,
                null,
                $mustBeCover
            );

        /*
         * Protección adicional frente a datos antiguos:
         * garantizamos que el anuncio tenga una portada.
         */
        $this->imageRepository
            ->ensureCoverExists(
                $advertisementId
            );

        return $this->imageRepository
            ->findById(
                $image->getId()
            )
            ?? $image;
    }

    /**
     * Asocia varios adjuntos a un anuncio.
     *
     * Los adjuntos inválidos provocan la cancelación del
     * proceso antes de crear ninguna relación.
     *
     * @param array<int, int|string> $attachmentIds
     *
     * @return array<int, AdvertisementImage>
     */
    public function addExistingAttachments(
        int $customerId,
        int $advertisementId,
        array $attachmentIds
    ): array {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId,
                true
            );

        $normalizedAttachmentIds =
            $this->normalizePositiveIds(
                $attachmentIds
            );

        if ($normalizedAttachmentIds === []) {
            return [];
        }

        /*
         * Validamos todos los adjuntos antes de modificar
         * las relaciones del anuncio.
         */
        foreach (
            $normalizedAttachmentIds
            as $attachmentId
        ) {
            $this->validateAttachment(
                $attachmentId
            );
        }

        $currentImages =
            $this->imageRepository
                ->findByAdvertisementId(
                    $advertisementId
                );

        $existingAttachmentIds = [];

        foreach ($currentImages as $image) {
            $existingAttachmentIds[
                $image->getAttachmentId()
            ] = true;
        }

        $newAttachmentIds = [];

        foreach (
            $normalizedAttachmentIds
            as $attachmentId
        ) {
            if (
                isset(
                    $existingAttachmentIds[
                        $attachmentId
                    ]
                )
            ) {
                continue;
            }

            $newAttachmentIds[] =
                $attachmentId;
        }

        $maximumImages =
            $this->resolveMaximumImages(
                $advertisement
            );

        if (
            count($currentImages)
            + count($newAttachmentIds)
            > $maximumImages
        ) {
            throw new RuntimeException(
                sprintf(
                    'El anuncio no puede tener más de %d imágenes.',
                    $maximumImages
                )
            );
        }

        $createdImages = [];

        foreach (
            $newAttachmentIds
            as $attachmentId
        ) {
            $createdImages[] =
                $this->addExistingAttachment(
                    $customerId,
                    $advertisementId,
                    $attachmentId,
                    false
                );
        }

        return $createdImages;
    }

    /**
     * Establece una imagen como portada.
     */
    public function setCover(
        int $customerId,
        int $advertisementId,
        int $imageId
    ): AdvertisementImage {
        $this->resolveOwnedAdvertisement(
            $customerId,
            $advertisementId,
            true
        );

        $image =
            $this->imageRepository
                ->findById(
                    $imageId
                );

        if ($image === null) {
            throw new RuntimeException(
                'No se encontró la imagen indicada.'
            );
        }

        if (
            !$image->belongsToAdvertisement(
                $advertisementId
            )
        ) {
            throw new RuntimeException(
                'La imagen no pertenece al anuncio indicado.'
            );
        }

        return $this->imageRepository
            ->setCover(
                $advertisementId,
                $imageId
            );
    }

    /**
     * Reordena las imágenes de un anuncio.
     *
     * El repositorio añadirá al final las imágenes existentes
     * que no se hayan incluido en el array recibido.
     *
     * @param array<int, int|string> $orderedImageIds
     *
     * @return array<int, AdvertisementImage>
     */
    public function reorder(
        int $customerId,
        int $advertisementId,
        array $orderedImageIds
    ): array {
        $this->resolveOwnedAdvertisement(
            $customerId,
            $advertisementId,
            true
        );

        $normalizedImageIds =
            $this->normalizePositiveIds(
                $orderedImageIds
            );

        $this->imageRepository
            ->reorder(
                $advertisementId,
                $normalizedImageIds
            );

        return $this->imageRepository
            ->findByAdvertisementId(
                $advertisementId
            );
    }

    /**
     * Elimina una imagen del anuncio.
     *
     * Por defecto solo se elimina la relación almacenada en
     * wp_dsm_ad_images.
     *
     * Cuando $deleteAttachment sea true, también se elimina
     * permanentemente el adjunto y su archivo físico de la
     * biblioteca multimedia de WordPress.
     */
    public function removeImage(
        int $customerId,
        int $advertisementId,
        int $imageId,
        bool $deleteAttachment = false
    ): bool {
        $this->resolveOwnedAdvertisement(
            $customerId,
            $advertisementId,
            true
        );

        $image =
            $this->imageRepository
                ->findById(
                    $imageId
                );

        if ($image === null) {
            return false;
        }

        if (
            !$image->belongsToAdvertisement(
                $advertisementId
            )
        ) {
            throw new RuntimeException(
                'La imagen no pertenece al anuncio indicado.'
            );
        }

        $attachmentId =
            $image->getAttachmentId();

        $deleted =
            $this->imageRepository
                ->delete(
                    $imageId
                );

        if (!$deleted) {
            return false;
        }

        if ($deleteAttachment) {
            $this->deletePhysicalAttachment(
                $attachmentId
            );
        }

        return true;
    }

    /**
     * Elimina todas las imágenes asociadas a un anuncio.
     *
     * @return int Número de relaciones eliminadas.
     */
    public function removeAllImages(
        int $customerId,
        int $advertisementId,
        bool $deleteAttachments = false
    ): int {
        $this->resolveOwnedAdvertisement(
            $customerId,
            $advertisementId,
            true
        );

        $images =
            $this->imageRepository
                ->findByAdvertisementId(
                    $advertisementId
                );

        if ($images === []) {
            return 0;
        }

        $attachmentIds = [];

        foreach ($images as $image) {
            $attachmentIds[] =
                $image->getAttachmentId();
        }

        $deletedRelations =
            $this->imageRepository
                ->deleteByAdvertisementId(
                    $advertisementId
                );

        if ($deleteAttachments) {
            foreach (
                $attachmentIds
                as $attachmentId
            ) {
                try {
                    $this->deletePhysicalAttachment(
                        $attachmentId
                    );
                } catch (Throwable $exception) {
                    /*
                     * Las relaciones ya se han eliminado.
                     * Registramos el fallo físico sin falsear
                     * el número de relaciones eliminadas.
                     */
                    error_log(
                        sprintf(
                            '[DSM Anuncios] No se pudo eliminar el adjunto %d: %s',
                            $attachmentId,
                            $exception->getMessage()
                        )
                    );
                }
            }
        }

        return $deletedRelations;
    }

    /**
     * Devuelve el máximo de imágenes permitido para el anuncio.
     *
     * Otros plugins, como DSM Suscripciones, podrán modificar
     * este valor mediante:
     *
     * dsm_advertisement_max_images
     */
    public function getMaximumImages(
        int $customerId,
        int $advertisementId
    ): int {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId,
                false
            );

        return $this->resolveMaximumImages(
            $advertisement
        );
    }

    /**
     * Devuelve cuántas imágenes adicionales se pueden añadir.
     */
    public function getRemainingImageSlots(
        int $customerId,
        int $advertisementId
    ): int {
        $maximumImages =
            $this->getMaximumImages(
                $customerId,
                $advertisementId
            );

        $currentImages =
            $this->imageRepository
                ->countByAdvertisementId(
                    $advertisementId
                );

        return max(
            0,
            $maximumImages
            - $currentImages
        );
    }

    /**
     * Resuelve y valida un anuncio perteneciente al cliente.
     */
    private function resolveOwnedAdvertisement(
        int $customerId,
        int $advertisementId,
        bool $mustBeEditable
    ): Advertisement {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        if (
            !$advertisement->belongsToCustomer(
                $customerId
            )
        ) {
            throw new RuntimeException(
                'No tienes permisos para gestionar las imágenes de este anuncio.'
            );
        }

        if (
            $mustBeEditable
            && !$advertisement
                ->isEditableByCustomer()
        ) {
            throw new RuntimeException(
                'Las imágenes del anuncio no se pueden modificar en su estado actual.'
            );
        }

        return $advertisement;
    }

    /**
     * Comprueba que el identificador corresponda a una imagen
     * válida de la biblioteca multimedia de WordPress.
     */
    private function validateAttachment(
        int $attachmentId
    ): WP_Post {
        if ($attachmentId <= 0) {
            throw new RuntimeException(
                'El identificador del adjunto no es válido.'
            );
        }

        $attachment =
            get_post(
                $attachmentId
            );

        if (
            !($attachment instanceof WP_Post)
            || $attachment->post_type
                !== 'attachment'
        ) {
            throw new RuntimeException(
                'No se encontró el adjunto indicado.'
            );
        }

        if (
            !wp_attachment_is_image(
                $attachmentId
            )
        ) {
            throw new RuntimeException(
                'El adjunto seleccionado no es una imagen válida.'
            );
        }

        $mimeType =
            get_post_mime_type(
                $attachmentId
            );

        if (
            !is_string($mimeType)
            || !str_starts_with(
                strtolower($mimeType),
                'image/'
            )
        ) {
            throw new RuntimeException(
                'El tipo de archivo del adjunto no es válido.'
            );
        }

        return $attachment;
    }

    /**
     * Calcula el límite de imágenes aplicable.
     *
     * @param Advertisement $advertisement
     */
    private function resolveMaximumImages(
        Advertisement $advertisement
    ): int {
        $maximumImages =
            apply_filters(
                'dsm_advertisement_max_images',
                self::DEFAULT_MAX_IMAGES,
                $advertisement->getCustomerId(),
                $advertisement->getId(),
                $advertisement
            );

        $maximumImages =
            (int) $maximumImages;

        return min(
            self::ABSOLUTE_MAX_IMAGES,
            max(
                1,
                $maximumImages
            )
        );
    }

    /**
     * Elimina permanentemente un adjunto de WordPress.
     */
    private function deletePhysicalAttachment(
        int $attachmentId
    ): void {
        if ($attachmentId <= 0) {
            return;
        }

        $attachment =
            get_post(
                $attachmentId
            );

        if (
            !($attachment instanceof WP_Post)
            || $attachment->post_type
                !== 'attachment'
        ) {
            return;
        }

        $deleted =
            wp_delete_attachment(
                $attachmentId,
                true
            );

        if ($deleted === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo eliminar el adjunto %d de WordPress.',
                    $attachmentId
                )
            );
        }
    }

    /**
     * Normaliza una colección de identificadores positivos,
     * eliminando valores inválidos y duplicados.
     *
     * @param array<int, int|string> $values
     *
     * @return array<int, int>
     */
    private function normalizePositiveIds(
        array $values
    ): array {
        $normalized = [];

        foreach ($values as $value) {
            $identifier =
                (int) $value;

            if ($identifier <= 0) {
                continue;
            }

            $normalized[
                $identifier
            ] = $identifier;
        }

        return array_values(
            $normalized
        );
    }
}