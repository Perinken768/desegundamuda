<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class TaxCalculator
{
    /**
     * Calcula base e impuesto a partir de un precio final
     * que ya incluye impuestos.
     *
     * @return array{
     *     subtotal: float,
     *     tax_amount: float,
     *     total: float
     * }
     */
    public static function fromTaxIncludedTotal(
        float $total,
        float $taxRate
    ): array {
        if ($total < 0) {
            throw new RuntimeException(
                'El total no puede ser negativo.'
            );
        }

        if (
            $taxRate < 0
            || $taxRate > 100
        ) {
            throw new RuntimeException(
                'El tipo impositivo no es válido.'
            );
        }

        $totalCents =
            (int) round(
                $total * 100,
                0,
                PHP_ROUND_HALF_UP
            );

        if ($taxRate === 0.0) {
            return [
                'subtotal' =>
                    $totalCents / 100,

                'tax_amount' =>
                    0.0,

                'total' =>
                    $totalCents / 100,
            ];
        }

        $divisor =
            1 + (
                $taxRate / 100
            );

        $subtotalCents =
            (int) round(
                $totalCents / $divisor,
                0,
                PHP_ROUND_HALF_UP
            );

        $taxCents =
            $totalCents
            - $subtotalCents;

        return [
            'subtotal' =>
                $subtotalCents / 100,

            'tax_amount' =>
                $taxCents / 100,

            'total' =>
                $totalCents / 100,
        ];
    }

    private function __construct()
    {
    }
}
