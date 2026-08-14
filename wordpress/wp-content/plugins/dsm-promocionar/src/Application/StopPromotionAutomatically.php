<?php

declare(strict_types=1);

namespace DSM\Promocionar\Application;

use DateTimeImmutable;
use DSM\Promocionar\Promotion\PromotionAssignment;
use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionAssignmentStatus;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class StopPromotionAutomatically
{
    public function __construct(
        private readonly PromotionWalletRepository $walletRepository =
            new PromotionWalletRepository(),

        private readonly PromotionAssignmentRepository $assignmentRepository =
            new PromotionAssignmentRepository()
    ) {
    }

    public function execute(
        int $assignmentId,
        ?DateTimeImmutable $stoppedAt = null
    ): PromotionAssignment {
        global $wpdb;

        if ($assignmentId <= 0) {
            throw new RuntimeException(
                'El identificador de la promoción no es válido.'
            );
        }

        $stoppedAt ??=
            new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            );

        $wpdb->query(
            'START TRANSACTION'
        );

        try {
            $assignment =
                $this->assignmentRepository
                    ->findByIdForUpdate(
                        $assignmentId
                    );

            if ($assignment === null) {
                throw new RuntimeException(
                    'No se encontró la promoción indicada.'
                );
            }

            if (!$assignment->isActive()) {
                throw new RuntimeException(
                    'La promoción ya está finalizada.'
                );
            }

            $wallet =
                $this->walletRepository
                    ->findByIdForUpdate(
                        $assignment->getWalletId()
                    );

            if ($wallet === null) {
                throw new RuntimeException(
                    'No se encontró el saldo asociado a la promoción.'
                );
            }

            /*
             * La asignación y el wallet deben pertenecer
             * al mismo cliente.
             */
            if (
                !$wallet->belongsToCustomer(
                    $assignment->getCustomerId()
                )
            ) {
                throw new RuntimeException(
                    'El saldo y la promoción pertenecen a clientes distintos.'
                );
            }

            $startedAt =
                $assignment->getStartedAt();

            if ($stoppedAt < $startedAt) {
                throw new RuntimeException(
                    'La fecha de finalización no puede ser anterior al inicio.'
                );
            }

            $elapsedSeconds =
                max(
                    0,
                    $stoppedAt->getTimestamp()
                    - $startedAt->getTimestamp()
                );

            $consumedSeconds =
                min(
                    $elapsedSeconds,
                    $wallet->getRemainingSeconds()
                );

            $updatedWallet =
                $this->walletRepository
                    ->consumeSeconds(
                        $wallet->getId(),
                        $consumedSeconds
                    );

            $assignmentStatus =
                $updatedWallet->getRemainingSeconds() > 0
                    ? PromotionAssignmentStatus::STOPPED
                    : PromotionAssignmentStatus::EXHAUSTED;

            $updatedAssignment =
                $this->assignmentRepository
                    ->stop(
                        assignmentId:
                            $assignmentId,

                        consumedSeconds:
                            $consumedSeconds,

                        status:
                            $assignmentStatus,

                        stoppedAt:
                            $stoppedAt
                    );

            $wpdb->query(
                'COMMIT'
            );

            return $updatedAssignment;
        } catch (Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }
}
