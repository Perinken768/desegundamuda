<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Report\AdvertisementReport;
use DSM\Anuncios\Report\AdvertisementReportRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class ReportAdvertisement
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository =
            new AdvertisementRepository(),

        private readonly AdvertisementReportRepository $reportRepository =
            new AdvertisementReportRepository()
    ) {
    }

    public function execute(
        int $reporterCustomerId,
        int $advertisementId,
        string $reasonCode,
        ?string $details = null
    ): int {
        if ($reporterCustomerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente que realiza la denuncia.'
            );
        }

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio indicado no es válido.'
            );
        }

        $reasonCode =
            sanitize_key(
                $reasonCode
            );

        if (
            !AdvertisementReport::isValidReason(
                $reasonCode
            )
        ) {
            throw new RuntimeException(
                'Selecciona un motivo de denuncia válido.'
            );
        }

        $reporterContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $reporterCustomerId
            );

        if (!is_array($reporterContext)) {
            throw new RuntimeException(
                'No se pudo obtener el cliente que realiza la denuncia.'
            );
        }

        $reporterStatus =
            sanitize_key(
                (string) (
                    $reporterContext['status']
                    ?? ''
                )
            );

        if ($reporterStatus !== 'active') {
            throw new RuntimeException(
                'Tu cuenta debe estar activa para denunciar un anuncio.'
            );
        }

        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'El anuncio indicado no existe.'
            );
        }

        if (
            !AdvertisementStatus::isPublic(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'Este anuncio ya no está disponible públicamente.'
            );
        }

        $ownerCustomerId =
            $advertisement->getCustomerId();

        if ($ownerCustomerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al propietario del anuncio.'
            );
        }

        if (
            $ownerCustomerId
            === $reporterCustomerId
        ) {
            throw new RuntimeException(
                'No puedes denunciar tu propio anuncio.'
            );
        }

        if (
            $this->reportRepository
                ->existsForReporter(
                    $advertisementId,
                    $reporterCustomerId
                )
        ) {
            throw new RuntimeException(
                'Ya has denunciado este anuncio.'
            );
        }

        return $this->reportRepository
            ->create(
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'reporter_customer_id' =>
                        $reporterCustomerId,

                    'reported_customer_id' =>
                        $ownerCustomerId,

                    'reason_code' =>
                        $reasonCode,

                    'details' =>
                        $details,
                ]
            );
    }
}
