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
 * Publicación directa de un anuncio por su propietario.
 *
 * La transición y el historial se delegan en
 * AdvertisementModerationService.
 */
final class PublishCustomerAdvertisement
{
    public function __construct(
        private readonly AdvertisementModerationService $moderationService
    ) {
    }

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
            ->publishByCustomer(
                $customerId,
                $advertisementId,
                $notes
            );
    }
}
