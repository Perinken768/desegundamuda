<?php

declare(strict_types=1);

namespace DSM\Catalogo\Image;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

final class ProductImage
{
    public function __construct(
        private readonly int $id,
        private readonly int $productId,
        private readonly int $attachmentId,
        private readonly int $sortOrder,
        private readonly bool $isCover,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la imagen no es válido.'
            );
        }

        if ($productId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del producto no es válido.'
            );
        }

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del adjunto no es válido.'
            );
        }

        if ($sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden de la imagen no puede ser negativo.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data
    ): self {
        return new self(
            id:
                (int) (
                    $data['id']
                    ?? 0
                ),

            productId:
                (int) (
                    $data['product_id']
                    ?? 0
                ),

            attachmentId:
                (int) (
                    $data['attachment_id']
                    ?? 0
                ),

            sortOrder:
                max(
                    0,
                    (int) (
                        $data['sort_order']
                        ?? 0
                    )
                ),

            isCover:
                in_array(
                    $data['is_cover']
                    ?? false,
                    [
                        true,
                        1,
                        '1',
                        'true',
                        'yes',
                        'on',
                    ],
                    true
                ),

            createdAt:
                new DateTimeImmutable(
                    (string) $data['created_at']
                ),

            updatedAt:
                new DateTimeImmutable(
                    (string) $data['updated_at']
                )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getAttachmentId(): int
    {
        return $this->attachmentId;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isCover(): bool
    {
        return $this->isCover;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function belongsToProduct(
        int $productId
    ): bool {
        return $productId > 0
            && $this->productId
                === $productId;
    }
}
