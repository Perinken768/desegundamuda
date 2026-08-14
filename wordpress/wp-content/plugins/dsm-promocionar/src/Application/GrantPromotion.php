<?php

declare(strict_types=1);

namespace DSM\Promocionar\Application;

use DSM\Promocionar\Promotion\PromotionPlanRepository;
use DSM\Promocionar\Promotion\PromotionWallet;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use DSM\Promocionar\Support\CustomerContext;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class GrantPromotion
{
    public function __construct(
        private readonly PromotionPlanRepository $planRepository =
            new PromotionPlanRepository(),

        private readonly PromotionWalletRepository $walletRepository =
            new PromotionWalletRepository()
    ) {
    }

    public function execute(
        int $customerId,
        int $planId,
        string $reason
    ): PromotionWallet {
        CustomerContext::requireActive(
            $customerId
        );

        if ($planId <= 0) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
            );
        }

        $reason =
            trim(
                sanitize_text_field(
                    $reason
                )
            );

        if ($reason === '') {
            throw new RuntimeException(
                'Debes indicar el motivo de la concesión.'
            );
        }

        $plan =
            $this->planRepository
                ->findById(
                    $planId
                );

        if ($plan === null) {
            throw new RuntimeException(
                'No se encontró el plan de promoción indicado.'
            );
        }

        if (!$plan->isActive()) {
            throw new RuntimeException(
                'El plan de promoción no está disponible.'
            );
        }

        $administratorId =
            get_current_user_id();

        $sourceReference =
            sprintf(
                'admin_%d_%s',
                $administratorId,
                sanitize_title(
                    $reason
                )
            );

        return $this->walletRepository
            ->create(
                customerId:
                    $customerId,

                seconds:
                    $plan->getDurationSeconds(),

                sourceType:
                    'admin_grant',

                sourceReference:
                    $sourceReference,

                planId:
                    $plan->getId(),

                pricePaid:
                    0.00,

                currency:
                    $plan->getCurrency(),

                paymentId:
                    null
            );
    }
}
