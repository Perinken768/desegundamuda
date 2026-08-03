<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SubmitAdvertisementForReview
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository
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

        if (
            !AdvertisementStatus::canBeSubmitted(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El anuncio no puede enviarse a revisión en su estado actual.'
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

        $this->advertisementRepository->updateStatus(
            $advertisementId,
            AdvertisementStatus::PENDING
        );

        $updatedAdvertisement =
            $this->advertisementRepository->findById(
                $advertisementId
            );

        if ($updatedAdvertisement === null) {
            throw new RuntimeException(
                'El anuncio se envió, pero no pudo recuperarse.'
            );
        }

        return $updatedAdvertisement;
    }
}