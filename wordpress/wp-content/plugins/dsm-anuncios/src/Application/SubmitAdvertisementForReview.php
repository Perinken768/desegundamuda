<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubmitAdvertisementForReview
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly AdvertisementModerationService $moderationService
    ) {
    }

    public function execute(
        int $customerId,
        int $advertisementId
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
            $this->advertisementRepository->findById(
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
                'No tienes permisos para enviar este anuncio.'
            );
        }

        if (trim($advertisement->getTitle()) === '') {
            throw new RuntimeException(
                'El anuncio necesita un título.'
            );
        }

        if (trim($advertisement->getDescription()) === '') {
            throw new RuntimeException(
                'El anuncio necesita una descripción.'
            );
        }

        if ($advertisement->getCategoryId() <= 0) {
            throw new RuntimeException(
                'El anuncio necesita una categoría.'
            );
        }

        if (
            trim(
                $advertisement->getConditionCode()
            ) === ''
        ) {
            throw new RuntimeException(
                'El anuncio necesita un estado de conservación.'
            );
        }

        return $this->moderationService
            ->submitForReview(
                $customerId,
                $advertisementId
            );
    }
}