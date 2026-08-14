<?php

declare(strict_types=1);

namespace DSM\Promocionar\Integration;

use DSM\Promocionar\Application\StopPromotionAutomatically;
use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementPromotionIntegration
{
    public function register(): void
    {
        /*
         * Una reserva hace que el anuncio deje de ser
         * promocionable temporalmente.
         */
        add_action(
            'dsm_advertisement_reserved',
            [
                $this,
                'handleAdvertisementUnavailable',
            ],
            10,
            1
        );

        /*
         * Un anuncio cerrado deja de consumir promoción.
         */
        add_action(
            'dsm_advertisement_closed',
            [
                $this,
                'handleAdvertisementUnavailable',
            ],
            10,
            1
        );

        /*
         * Un anuncio rechazado tampoco debe continuar
         * consumiendo tiempo promocionado.
         */
        add_action(
            'dsm_advertisement_rejected',
            [
                $this,
                'handleAdvertisementUnavailable',
            ],
            10,
            1
        );

        /*
         * Si el anuncio se elimina, liberamos igualmente
         * el saldo restante de promoción.
         *
         * El evento original aporta más parámetros,
         * pero aquí solo necesitamos advertisementId.
         */
        add_action(
            'dsm_advertisement_deleted',
            [
                $this,
                'handleAdvertisementUnavailable',
            ],
            10,
            1
        );
    }

    public function handleAdvertisementUnavailable(
        int $advertisementId
    ): void {
        if ($advertisementId <= 0) {
            return;
        }

        try {
            $repository =
                new PromotionAssignmentRepository();

            $assignment =
                $repository
                    ->findActiveByAdvertisement(
                        $advertisementId
                    );

            if ($assignment === null) {
                return;
            }

            $useCase =
                new StopPromotionAutomatically();

            $useCase->execute(
                $assignment->getId()
            );
        } catch (Throwable $exception) {
            /*
             * Estos hooks se ejecutan después del COMMIT
             * de DSM Anuncios.
             *
             * Un fallo en Promocionar no debe provocar
             * un error fatal al usuario ni intentar
             * deshacer una operación ya confirmada.
             */
            error_log(
                sprintf(
                    '[DSM Promocionar] No se pudo detener '
                    . 'automáticamente la promoción del '
                    . 'anuncio %d: %s',
                    $advertisementId,
                    $exception->getMessage()
                )
            );
        }
    }
}