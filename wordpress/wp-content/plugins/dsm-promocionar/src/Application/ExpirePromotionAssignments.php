<?php

declare(strict_types=1);

namespace DSM\Promocionar\Application;

use DateTimeImmutable;
use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ExpirePromotionAssignments
{
    public function __construct(
        private readonly PromotionAssignmentRepository $assignmentRepository =
            new PromotionAssignmentRepository(),

        private readonly PromotionWalletRepository $walletRepository =
            new PromotionWalletRepository(),

        private readonly StopPromotionAutomatically $stopPromotionAutomatically =
            new StopPromotionAutomatically()
    ) {
    }

    /**
     * @return array{
     *     checked:int,
     *     exhausted:int,
     *     failed:int
     * }
     */
    public function execute(
        int $limit = 100,
        ?DateTimeImmutable $now = null
    ): array {
        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $now ??=
            new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            );

        $checked = 0;
        $exhausted = 0;
        $failed = 0;

        $assignments =
            $this->assignmentRepository
                ->findActive(
                    $limit
                );

        foreach ($assignments as $assignment) {
            $checked++;

            try {
                $wallet =
                    $this->walletRepository
                        ->findById(
                            $assignment->getWalletId()
                        );

                if ($wallet === null) {
                    throw new \RuntimeException(
                        'No se encontró el saldo asociado a la promoción.'
                    );
                }

                $remainingSeconds =
                    $wallet->getRemainingSeconds();

                if ($remainingSeconds <= 0) {
                    $exhaustionAt =
                        $assignment->getStartedAt();
                } else {
                    $exhaustionAt =
                        $assignment
                            ->getStartedAt()
                            ->modify(
                                sprintf(
                                    '+%d seconds',
                                    $remainingSeconds
                                )
                            );
                }

                if ($now < $exhaustionAt) {
                    continue;
                }

                $this->stopPromotionAutomatically
                    ->execute(
                        assignmentId:
                            $assignment->getId(),

                        stoppedAt:
                            $exhaustionAt
                    );

                $exhausted++;
            } catch (Throwable $exception) {
                $failed++;

                error_log(
                    sprintf(
                        '[DSM Promocionar] No se pudo agotar '
                        . 'la promoción %d: %s',
                        $assignment->getId(),
                        $exception->getMessage()
                    )
                );
            }
        }

        return [
            'checked' =>
                $checked,

            'exhausted' =>
                $exhausted,

            'failed' =>
                $failed,
        ];
    }
}
