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
 * Caso de uso para rechazar un anuncio pendiente.
 *
 * La validación de la transición, la actualización del
 * estado y el registro del historial se delegan en
 * AdvertisementModerationService.
 */
final class RejectAdvertisement
{
    public function __construct(
        private readonly AdvertisementModerationService $moderationService
    ) {
    }

    /**
     * Rechaza un anuncio pendiente.
     *
     * @param int         $advertisementId Identificador del anuncio.
     * @param int         $userId          Usuario de WordPress responsable.
     * @param string      $reason          Motivo obligatorio del rechazo.
     * @param string|null $notes           Notas administrativas opcionales.
     */
    public function execute(
        int $advertisementId,
        int $userId,
        string $reason,
        ?string $notes = null
    ): Advertisement {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($userId <= 0) {
            throw new RuntimeException(
                'El identificador del usuario no es válido.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new RuntimeException(
                'El motivo del rechazo es obligatorio.'
            );
        }

        return $this->moderationService
            ->reject(
                $advertisementId,
                $userId,
                $reason,
                $notes
            );
    }
}