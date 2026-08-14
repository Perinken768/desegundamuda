<?php

declare(strict_types=1);

namespace DSM\Promocionar\Application;

use DSM\Promocionar\Promotion\PromotionWallet;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use DSM\Promocionar\Support\CustomerContext;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CreatePromotionWallet
{
    public function __construct(
        private readonly PromotionWalletRepository $walletRepository =
            new PromotionWalletRepository()
    ) {
    }

    public function execute(
        int $customerId,
        int $seconds,
        ?string $sourceType = null,
        ?string $sourceReference = null
    ): PromotionWallet {
        CustomerContext::requireActive(
            $customerId
        );

        if ($seconds <= 0) {
            throw new RuntimeException(
                'El tiempo de promoción debe ser mayor que cero.'
            );
        }

        return $this->walletRepository->create(
            customerId:
                $customerId,

            seconds:
                $seconds,

            sourceType:
                $sourceType,

            sourceReference:
                $sourceReference
        );
    }
}
