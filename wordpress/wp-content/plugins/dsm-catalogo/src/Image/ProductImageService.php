<?php

declare(strict_types=1);

namespace DSM\Catalogo\Image;

use DSM\Catalogo\Product\ProductRepository;
use RuntimeException;
use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductImageService
{
    public const DEFAULT_MAX_IMAGES = 10;

    public function __construct(
        private readonly ProductRepository $productRepository =
            new ProductRepository(),

        private readonly ProductImageRepository $imageRepository =
            new ProductImageRepository()
    ) {
    }

    /**
     * @return array<int, ProductImage>
     */
    public function getImages(
        int $productId
    ): array {
        $this->requireProduct(
            $productId
        );

        return $this->imageRepository
            ->findByProductId(
                $productId
            );
    }

    public function addExistingAttachment(
        int $productId,
        int $attachmentId,
        bool $setAsCover = false
    ): ProductImage {
        $this->requireProduct(
            $productId
        );

        $this->validateAttachment(
            $attachmentId
        );

        $currentCount =
            $this->imageRepository
                ->countByProductId(
                    $productId
                );

        if (
            $currentCount
            >= self::DEFAULT_MAX_IMAGES
        ) {
            throw new RuntimeException(
                sprintf(
                    'El producto no puede tener más de %d imágenes.',
                    self::DEFAULT_MAX_IMAGES
                )
            );
        }

        $mustBeCover =
            $setAsCover
            || $currentCount === 0;

        $image =
            $this->imageRepository
                ->create(
                    $productId,
                    $attachmentId,
                    null,
                    $mustBeCover
                );

        $this->imageRepository
            ->ensureCoverExists(
                $productId
            );

        return $image;
    }

    /**
     * @param array<int, int|string> $attachmentIds
     *
     * @return array<int, ProductImage>
     */
    public function addExistingAttachments(
        int $productId,
        array $attachmentIds
    ): array {
        $this->requireProduct(
            $productId
        );

        $normalized = [];

        foreach ($attachmentIds as $attachmentId) {
            $attachmentId =
                (int) $attachmentId;

            if ($attachmentId > 0) {
                $normalized[$attachmentId] =
                    $attachmentId;
            }
        }

        $normalized =
            array_values(
                $normalized
            );

        if ($normalized === []) {
            return [];
        }

        if (
            $this->imageRepository
                ->countByProductId(
                    $productId
                )
            + count($normalized)
            > self::DEFAULT_MAX_IMAGES
        ) {
            throw new RuntimeException(
                sprintf(
                    'El producto no puede tener más de %d imágenes.',
                    self::DEFAULT_MAX_IMAGES
                )
            );
        }

        foreach ($normalized as $attachmentId) {
            $this->validateAttachment(
                $attachmentId
            );
        }

        $created = [];

        foreach ($normalized as $attachmentId) {
            $created[] =
                $this->addExistingAttachment(
                    $productId,
                    $attachmentId
                );
        }

        return $created;
    }

    /**
     * Sube múltiples archivos desde un campo input[type=file].
     *
     * @return array<int, ProductImage>
     */
    public function uploadFiles(
        int $productId,
        string $fieldName
    ): array {
        $this->requireProduct(
            $productId
        );

        if (
            !isset($_FILES[$fieldName])
            || !is_array(
                $_FILES[$fieldName]
            )
        ) {
            return [];
        }

        $files =
            $_FILES[$fieldName];

        $names =
            $files['name']
            ?? [];

        if (!is_array($names)) {
            return [];
        }

        $uploaded = [];

        foreach (
            array_keys($names)
            as $index
        ) {
            $error =
                isset($files['error'][$index])
                    ? (int) $files['error'][$index]
                    : UPLOAD_ERR_NO_FILE;

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException(
                    'No se pudo subir una de las imágenes del producto.'
                );
            }

            $_FILES[
                'dsm_product_single_image'
            ] = [
                'name' =>
                    $files['name'][$index]
                    ?? '',

                'type' =>
                    $files['type'][$index]
                    ?? '',

                'tmp_name' =>
                    $files['tmp_name'][$index]
                    ?? '',

                'error' =>
                    $files['error'][$index]
                    ?? UPLOAD_ERR_NO_FILE,

                'size' =>
                    $files['size'][$index]
                    ?? 0,
            ];

            require_once ABSPATH
                . 'wp-admin/includes/file.php';

            require_once ABSPATH
                . 'wp-admin/includes/media.php';

            require_once ABSPATH
                . 'wp-admin/includes/image.php';

            $attachmentId =
                media_handle_upload(
                    'dsm_product_single_image',
                    0
                );

            unset(
                $_FILES[
                    'dsm_product_single_image'
                ]
            );

            if (is_wp_error($attachmentId)) {
                throw new RuntimeException(
                    'No se pudo guardar una imagen del producto: '
                    . $attachmentId->get_error_message()
                );
            }

            $uploaded[] =
                $this->addExistingAttachment(
                    $productId,
                    (int) $attachmentId
                );
        }

        return $uploaded;
    }

    public function setCover(
        int $productId,
        int $imageId
    ): ProductImage {
        $this->requireProduct(
            $productId
        );

        return $this->imageRepository
            ->setCover(
                $productId,
                $imageId
            );
    }

    public function remove(
        int $productId,
        int $imageId,
        bool $deleteAttachment = false
    ): void {
        $this->requireProduct(
            $productId
        );

        $image =
            $this->imageRepository
                ->findById(
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

        $attachmentId =
            $image->getAttachmentId();

        $this->imageRepository
            ->deleteById(
                $imageId
            );

        if ($deleteAttachment) {
            wp_delete_attachment(
                $attachmentId,
                true
            );
        }
    }

    private function requireProduct(
        int $productId
    ): void {
        if (
            $productId <= 0
            || $this->productRepository
                ->findById(
                    $productId
                ) === null
        ) {
            throw new RuntimeException(
                'No se encontró el producto indicado.'
            );
        }
    }

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

        $mimeType =
            get_post_mime_type(
                $attachmentId
            );

        if (
            !is_string($mimeType)
            || !str_starts_with(
                $mimeType,
                'image/'
            )
        ) {
            throw new RuntimeException(
                'El adjunto seleccionado no es una imagen válida.'
            );
        }

        return $attachment;
    }
}
