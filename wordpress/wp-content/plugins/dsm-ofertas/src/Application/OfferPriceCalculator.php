<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class OfferPriceCalculator
{
    /**
     * @return array{
     *     original_price: float,
     *     discount_amount: float,
     *     final_price: float
     * }
     */
    public function calculate(
        float $originalPrice,
        string $benefitType,
        float $benefitValue
    ): array {
        $originalPrice =
            round(
                max(
                    0,
                    $originalPrice
                ),
                2
            );

        $benefitType =
            sanitize_key(
                $benefitType
            );

        $benefitValue =
            round(
                max(
                    0,
                    $benefitValue
                ),
                2
            );

        $finalPrice =
            match ($benefitType) {
                'free_months' =>
                    0.0,

                'percentage_discount' =>
                    $this->percentageDiscount(
                        $originalPrice,
                        $benefitValue
                    ),

                'fixed_discount' =>
                    $originalPrice
                    - $benefitValue,

                'special_price' =>
                    $benefitValue,

                default =>
                    throw new RuntimeException(
                        'El tipo de beneficio no es válido.'
                    ),
            };

        $finalPrice =
            round(
                max(
                    0,
                    $finalPrice
                ),
                2
            );

        /*
         * Nunca permitimos que un precio especial
         * supere el precio normal.
         *
         * DSM Ofertas concede ventajas comerciales,
         * no incrementos de precio.
         */
        $finalPrice =
            min(
                $originalPrice,
                $finalPrice
            );

        $discountAmount =
            round(
                max(
                    0,
                    $originalPrice
                    - $finalPrice
                ),
                2
            );

        return [
            'original_price' =>
                $originalPrice,

            'discount_amount' =>
                $discountAmount,

            'final_price' =>
                $finalPrice,
        ];
    }

    private function percentageDiscount(
        float $originalPrice,
        float $percentage
    ): float {
        $percentage =
            min(
                100,
                max(
                    0,
                    $percentage
                )
            );

        return $originalPrice
            * (
                1
                - (
                    $percentage
                    / 100
                )
            );
    }
}
