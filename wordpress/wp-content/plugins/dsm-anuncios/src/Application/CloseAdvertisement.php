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
 * Caso de uso para cerrar un anuncio por decisión
 * de su propietario.
 *
 * El cierre realizado desde esta clase se considera una
 * retirada voluntaria. Los cierres por venta podrán disponer
 * más adelante de un caso de uso específico.
 */
final class CloseAdvertisement
{
    public function __construct(
        private readonly AdvertisementModerationService $moderationService
    ) {
    }

    /**
     * Cierra un anuncio activo o reservado perteneciente
     * al cliente.
     *
     * Mantiene la firma original para no romper los
     * controladores consumidores:
     *
     * - customerId
     * - advertisementId
     * - notes
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
            ->close(
                $customerId,
                $advertisementId,
                AdvertisementModerationService::
                    CLOSURE_REASON_WITHDRAWN,
                $notes
            );
    }
}