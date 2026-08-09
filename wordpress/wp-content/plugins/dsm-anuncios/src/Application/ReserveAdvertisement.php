<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Caso de uso para marcar un anuncio como reservado.
 *
 * La validación de la transición, la actualización del
 * estado y el registro del historial se delegan en
 * AdvertisementModerationService.
 */
final class ReserveAdvertisement
{
    public function __construct(
        private readonly AdvertisementModerationService $moderationService
    ) {
    }

    /**
     * Marca como reservado un anuncio activo perteneciente
     * al cliente.
     */
    public function execute(
        int $customerId,
        int $advertisementId,
        ?string $notes = null
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

        return $this->moderationService
            ->reserve(
                $customerId,
                $advertisementId,
                $notes
            );
    }
}