<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DateTimeImmutable;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuCancellationSubsanationData
{
    public function __construct(
        private readonly ?string $issuerTaxId = null,
        private readonly ?string $invoiceNumber = null,
        private readonly ?string $invoiceDate = null
    ) {
        $this->validate();
    }

    public function getIssuerTaxId(): ?string
    {
        return self::nullable(
            $this->issuerTaxId
        );
    }

    public function getInvoiceNumber(): ?string
    {
        return self::nullable(
            $this->invoiceNumber
        );
    }

    /**
     * Fecha en formato interno YYYY-MM-DD.
     */
    public function getInvoiceDate(): ?string
    {
        $value =
            self::nullable(
                $this->invoiceDate
            );

        if ($value === null) {
            return null;
        }

        $date =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $value
            );

        if (
            $date === false
            || $date->format(
                'Y-m-d'
            ) !== $value
        ) {
            throw new RuntimeException(
                sprintf(
                    'La fecha corregida de anulación no es válida: %s',
                    $value
                )
            );
        }

        return $value;
    }

    public function hasChanges(): bool
    {
        return
            $this->getIssuerTaxId() !== null
            || $this->getInvoiceNumber() !== null
            || $this->getInvoiceDate() !== null;
    }

    private function validate(): void
    {
        if (!$this->hasChanges()) {
            throw new RuntimeException(
                'La subsanación de anulación no contiene ningún dato corregido.'
            );
        }

        $issuerTaxId =
            $this->getIssuerTaxId();

        if (
            $issuerTaxId !== null
            && strlen(
                $issuerTaxId
            ) > 50
        ) {
            throw new RuntimeException(
                'El identificador fiscal corregido es demasiado largo.'
            );
        }

        $invoiceNumber =
            $this->getInvoiceNumber();

        if (
            $invoiceNumber !== null
            && strlen(
                $invoiceNumber
            ) > 80
        ) {
            throw new RuntimeException(
                'El número de factura corregido es demasiado largo.'
            );
        }

        /*
         * Fuerza también la validación de fecha
         * durante la construcción.
         */
        $this->getInvoiceDate();
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
