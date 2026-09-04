<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSubsanationData
{
    public function __construct(
        private readonly ?string $customerFiscalName = null,
        private readonly ?string $customerTaxId = null,
        private readonly ?string $description = null,
        private readonly ?float $taxRate = null,
        private readonly ?float $taxBase = null,
        private readonly ?float $taxAmount = null,
        private readonly ?float $totalAmount = null
    ) {
        $this->validate();
    }

    public function getCustomerFiscalName(): ?string
    {
        return self::nullable(
            $this->customerFiscalName
        );
    }

    public function getCustomerTaxId(): ?string
    {
        return self::nullable(
            $this->customerTaxId
        );
    }

    public function getDescription(): ?string
    {
        return self::nullable(
            $this->description
        );
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate;
    }

    public function getTaxBase(): ?float
    {
        return $this->taxBase;
    }

    public function getTaxAmount(): ?float
    {
        return $this->taxAmount;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function hasChanges(): bool
    {
        return
            $this->getCustomerFiscalName() !== null
            || $this->getCustomerTaxId() !== null
            || $this->getDescription() !== null
            || $this->taxRate !== null
            || $this->taxBase !== null
            || $this->taxAmount !== null
            || $this->totalAmount !== null;
    }

    private function validate(): void
    {
        if (!$this->hasChanges()) {
            throw new RuntimeException(
                'La subsanación VERI*FACTU no contiene ningún dato corregido.'
            );
        }

        if (
            $this->taxRate !== null
            && $this->taxRate < 0
        ) {
            throw new RuntimeException(
                'El tipo impositivo corregido no puede ser negativo.'
            );
        }

        if (
            $this->taxBase !== null
            && $this->taxBase < 0
        ) {
            throw new RuntimeException(
                'La base imponible corregida no puede ser negativa.'
            );
        }

        if (
            $this->taxAmount !== null
            && $this->taxAmount < 0
        ) {
            throw new RuntimeException(
                'La cuota tributaria corregida no puede ser negativa.'
            );
        }

        if (
            $this->totalAmount !== null
            && $this->totalAmount <= 0
        ) {
            throw new RuntimeException(
                'El importe total corregido debe ser mayor que cero.'
            );
        }
    }

    private static function nullable(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}
