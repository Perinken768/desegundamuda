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

final class CreatePromotionWalletFromPlan
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
        ?string $sourceType = null,
        ?string $sourceReference = null,
        ?int $paymentId = null
    ): PromotionWallet {
        CustomerContext::requireActive(
            $customerId
        );

        if ($planId <= 0) {
            throw new RuntimeException(
                'El identificador del plan no es válido.'
            );
        }

        if (
            $paymentId !== null
            && $paymentId <= 0
        ) {
            throw new RuntimeException(
                'El identificador del pago no es válido.'
            );
        }

        /*
         * Idempotencia a nivel de aplicación.
         *
         * Si el pago ya generó un wallet,
         * devolvemos el existente.
         */
        if ($paymentId !== null) {
            $existingWallet =
                $this->walletRepository
                    ->findByPaymentId(
                        $paymentId
                    );

            if ($existingWallet !== null) {
                if (
                    !$existingWallet
                        ->belongsToCustomer(
                            $customerId
                        )
                ) {
                    throw new RuntimeException(
                        'El pago ya está asociado a otro cliente.'
                    );
                }

                return $existingWallet;
            }
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

        $sourceType =
            $sourceType !== null
            && trim($sourceType) !== ''
                ? trim($sourceType)
                : 'promotion_plan';

        $sourceReference =
            $sourceReference !== null
            && trim($sourceReference) !== ''
                ? trim($sourceReference)
                : $plan->getCode();

        return $this->walletRepository
            ->create(
                customerId:
                    $customerId,

                seconds:
                    $plan->getDurationSeconds(),

                sourceType:
                    $sourceType,

                sourceReference:
                    $sourceReference,

                planId:
                    $plan->getId(),

                pricePaid:
                    $plan->getPrice(),

                currency:
                    $plan->getCurrency(),

                paymentId:
                    $paymentId
            );
    }
}