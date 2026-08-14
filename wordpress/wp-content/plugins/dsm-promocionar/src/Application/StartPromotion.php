<?php

declare(strict_types=1);

namespace DSM\Promocionar\Application;

use DSM\Promocionar\Promotion\PromotionAssignment;
use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use DSM\Promocionar\Promotion\PromotionWalletStatus;
use DSM\Promocionar\Support\AdvertisementContext;
use DSM\Promocionar\Support\CustomerContext;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StartPromotion
{
    public function __construct(
        private readonly PromotionWalletRepository $walletRepository =
            new PromotionWalletRepository(),

        private readonly PromotionAssignmentRepository $assignmentRepository =
            new PromotionAssignmentRepository()
    ) {
    }

    public function execute(
        int $customerId,
        int $walletId,
        int $advertisementId
    ): PromotionAssignment {
        global $wpdb;

        CustomerContext::requireActive(
            $customerId
        );

        AdvertisementContext::requirePromotable(
            $advertisementId,
            $customerId
        );

        if ($walletId <= 0) {
            throw new RuntimeException(
                'El identificador del saldo de promoción no es válido.'
            );
        }

        $wpdb->query(
            'START TRANSACTION'
        );

        try {
            /*
             * Bloqueamos el wallet para impedir que otra petición
             * intente utilizar simultáneamente el mismo saldo.
             */
            $wallet =
                $this->walletRepository
                    ->findByIdForUpdate(
                        $walletId
                    );

            if ($wallet === null) {
                throw new RuntimeException(
                    'No se encontró el saldo de promoción indicado.'
                );
            }

            if (
                !$wallet->belongsToCustomer(
                    $customerId
                )
            ) {
                throw new RuntimeException(
                    'El saldo de promoción no pertenece al cliente indicado.'
                );
            }

            if (!$wallet->canBeAssigned()) {
                throw new RuntimeException(
                    'El saldo de promoción no está disponible.'
                );
            }

            /*
             * Un wallet solo puede estar aplicado a un anuncio
             * al mismo tiempo.
             */
            $activeWalletAssignment =
                $this->assignmentRepository
                    ->findActiveByWalletForUpdate(
                        $walletId
                    );

            if ($activeWalletAssignment !== null) {
                throw new RuntimeException(
                    'El saldo de promoción ya está siendo utilizado.'
                );
            }

            /*
             * Un anuncio solo puede tener una promoción activa
             * al mismo tiempo, aunque el cliente disponga de
             * varios wallets.
             */
            $activeAdvertisementAssignment =
                $this->assignmentRepository
                    ->findActiveByAdvertisementForUpdate(
                        $advertisementId
                    );

            if ($activeAdvertisementAssignment !== null) {
                throw new RuntimeException(
                    'El anuncio ya tiene una promoción activa.'
                );
            }

            /*
             * Volvemos a comprobar el anuncio justo antes de
             * crear la asignación.
             */
            AdvertisementContext::requirePromotable(
                $advertisementId,
                $customerId
            );

            $assignment =
                $this->assignmentRepository
                    ->create(
                        walletId:
                            $walletId,

                        customerId:
                            $customerId,

                        advertisementId:
                            $advertisementId
                    );

            $this->walletRepository
                ->setStatus(
                    $walletId,
                    PromotionWalletStatus::IN_USE
                );

            $wpdb->query(
                'COMMIT'
            );

            return $assignment;
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }
}
