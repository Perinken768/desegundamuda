<?php

declare(strict_types=1);

namespace DSM\Favoritos\Favorite;

use DateTimeImmutable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Representa una relación de favorito entre:
 *
 * - un cliente de DSM Clientes;
 * - un anuncio de DSM Anuncios.
 *
 * Esta entidad no contiene reglas de negocio externas.
 * Únicamente representa los datos almacenados en
 * wp_dsm_favorites.
 */
final class Favorite
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly int $advertisementId,
        private readonly DateTimeImmutable $createdAt
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(
                'El ID del favorito debe ser mayor que cero.'
            );
        }

        if ($this->customerId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del cliente debe ser mayor que cero.'
            );
        }

        if ($this->advertisementId <= 0) {
            throw new \InvalidArgumentException(
                'El ID del anuncio debe ser mayor que cero.'
            );
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getAdvertisementId(): int
    {
        return $this->advertisementId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function belongsToCustomer(
        int $customerId
    ): bool {
        return $this->customerId
            === $customerId;
    }

    public function belongsToAdvertisement(
        int $advertisementId
    ): bool {
        return $this->advertisementId
            === $advertisementId;
    }

    public function matches(
        int $customerId,
        int $advertisementId
    ): bool {
        return $this->customerId
            === $customerId
            && $this->advertisementId
            === $advertisementId;
    }
}
