<?php

declare(strict_types=1);

namespace DSM\Anuncios\Image;

use DateTimeImmutable;
use InvalidArgumentException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Representa una imagen asociada a un anuncio.
 *
 * La imagen física permanece gestionada por la biblioteca
 * multimedia de WordPress mediante attachment_id.
 *
 * Esta entidad únicamente representa la relación entre:
 *
 * - el anuncio;
 * - el adjunto de WordPress;
 * - el orden de presentación;
 * - la condición de imagen de portada.
 */
final class AdvertisementImage
{
    public function __construct(
        private readonly int $id,
        private readonly int $advertisementId,
        private readonly int $attachmentId,
        private readonly int $sortOrder,
        private readonly bool $isCover,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
        $this->validate();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data
    ): self {
        return new self(
            id:
                self::requiredPositiveInt(
                    $data['id']
                    ?? null,
                    'id'
                ),

            advertisementId:
                self::requiredPositiveInt(
                    $data['advertisement_id']
                    ?? null,
                    'advertisement_id'
                ),

            attachmentId:
                self::requiredPositiveInt(
                    $data['attachment_id']
                    ?? null,
                    'attachment_id'
                ),

            sortOrder:
                self::nonNegativeInt(
                    $data['sort_order']
                    ?? 0
                ),

            isCover:
                self::toBoolean(
                    $data['is_cover']
                    ?? false
                ),

            createdAt:
                self::requiredDateTime(
                    $data['created_at']
                    ?? null,
                    'created_at'
                ),

            updatedAt:
                self::requiredDateTime(
                    $data['updated_at']
                    ?? null,
                    'updated_at'
                )
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdvertisementId(): int
    {
        return $this->advertisementId;
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

    /**
     * Comprueba si la imagen pertenece al anuncio indicado.
     */
    public function belongsToAdvertisement(
        int $advertisementId
    ): bool {
        return $advertisementId > 0
            && $this->advertisementId
                === $advertisementId;
    }

    /**
     * Comprueba si la entidad referencia el adjunto indicado.
     */
    public function referencesAttachment(
        int $attachmentId
    ): bool {
        return $attachmentId > 0
            && $this->attachmentId
                === $attachmentId;
    }

    /**
     * Devuelve una representación neutral de la entidad.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' =>
                $this->id,

            'advertisement_id' =>
                $this->advertisementId,

            'attachment_id' =>
                $this->attachmentId,

            'sort_order' =>
                $this->sortOrder,

            'is_cover' =>
                $this->isCover,

            'created_at' =>
                $this->createdAt
                    ->format(
                        'Y-m-d H:i:s'
                    ),

            'updated_at' =>
                $this->updatedAt
                    ->format(
                        'Y-m-d H:i:s'
                    ),
        ];
    }

    private function validate(): void
    {
        if ($this->id <= 0) {
            throw new InvalidArgumentException(
                'El identificador de la imagen no es válido.'
            );
        }

        if ($this->advertisementId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($this->attachmentId <= 0) {
            throw new InvalidArgumentException(
                'El identificador del adjunto no es válido.'
            );
        }

        if ($this->sortOrder < 0) {
            throw new InvalidArgumentException(
                'El orden de la imagen no puede ser negativo.'
            );
        }
    }

    private static function requiredPositiveInt(
        mixed $value,
        string $fieldName
    ): int {
        $integer =
            (int) $value;

        if ($integer <= 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'El campo %s debe contener un entero positivo.',
                    $fieldName
                )
            );
        }

        return $integer;
    }

    private static function nonNegativeInt(
        mixed $value
    ): int {
        $integer =
            (int) $value;

        if ($integer < 0) {
            throw new InvalidArgumentException(
                'El orden de la imagen no puede ser negativo.'
            );
        }

        return $integer;
    }

    private static function requiredDateTime(
        mixed $value,
        string $fieldName
    ): DateTimeImmutable {
        if (
            $value === null
            || trim(
                (string) $value
            ) === ''
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'El campo %s es obligatorio.',
                    $fieldName
                )
            );
        }

        try {
            return new DateTimeImmutable(
                (string) $value
            );
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                sprintf(
                    'El campo %s no contiene una fecha válida.',
                    $fieldName
                ),
                0,
                $exception
            );
        }
    }

    private static function toBoolean(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }
}