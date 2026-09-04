<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuHashGenerator
{
    public const HASH_TYPE = '01';

    /**
     * Genera la cadena oficial y la huella SHA-256
     * para un RegistroAlta VERI*FACTU.
     *
     * @return array{
     *     input: string,
     *     hash: string
     * }
     */
    public function generateRegistrationHash(
        string $issuerTaxId,
        string $invoiceNumber,
        string $invoiceDate,
        string $invoiceType,
        string|float|int $taxTotal,
        string|float|int $totalAmount,
        ?string $previousHash,
        string $generatedAtIso
    ): array {
        $issuerTaxId =
            $this->cleanText(
                $issuerTaxId
            );

        $invoiceNumber =
            $this->cleanText(
                $invoiceNumber
            );

        $invoiceDate =
            $this->cleanText(
                $invoiceDate
            );

        $invoiceType =
            $this->cleanText(
                $invoiceType
            );

        $previousHash =
            $this->cleanText(
                $previousHash
                ?? ''
            );

        $generatedAtIso =
            $this->cleanText(
                $generatedAtIso
            );

        if ($issuerTaxId === '') {
            throw new RuntimeException(
                'IDEmisorFactura no puede estar vacío.'
            );
        }

        if ($invoiceNumber === '') {
            throw new RuntimeException(
                'NumSerieFactura no puede estar vacío.'
            );
        }

        if ($invoiceDate === '') {
            throw new RuntimeException(
                'FechaExpedicionFactura no puede estar vacía.'
            );
        }

        if ($invoiceType === '') {
            throw new RuntimeException(
                'TipoFactura no puede estar vacío.'
            );
        }

        if ($generatedAtIso === '') {
            throw new RuntimeException(
                'FechaHoraHusoGenRegistro no puede estar vacía.'
            );
        }

        if (
            $previousHash !== ''
            && !preg_match(
                '/^[A-F0-9]{64}$/',
                $previousHash
            )
        ) {
            throw new RuntimeException(
                'La huella anterior no tiene un formato SHA-256 válido.'
            );
        }

        $taxTotal =
            $this->normalizeNumber(
                $taxTotal
            );

        $totalAmount =
            $this->normalizeNumber(
                $totalAmount
            );

        /*
         * IMPORTANTE:
         *
         * El orden de estos campos no puede modificarse.
         * Es exactamente el definido por AEAT para
         * RegistroAlta.
         */
        $input =
            'IDEmisorFactura='
            . $issuerTaxId

            . '&NumSerieFactura='
            . $invoiceNumber

            . '&FechaExpedicionFactura='
            . $invoiceDate

            . '&TipoFactura='
            . $invoiceType

            . '&CuotaTotal='
            . $taxTotal

            . '&ImporteTotal='
            . $totalAmount

            . '&Huella='
            . $previousHash

            . '&FechaHoraHusoGenRegistro='
            . $generatedAtIso;

        /*
         * PHP trabaja internamente con bytes de la cadena.
         * Nuestros datos se almacenan y generan en UTF-8,
         * que es la codificación exigida por AEAT.
         */
        $hash =
            strtoupper(
                hash(
                    'sha256',
                    $input
                )
            );

        if (
            strlen($hash) !== 64
            || !preg_match(
                '/^[A-F0-9]{64}$/',
                $hash
            )
        ) {
            throw new RuntimeException(
                'No se pudo generar una huella SHA-256 válida.'
            );
        }

        return [
            'input' =>
                $input,

            'hash' =>
                $hash,
        ];
    }

    /**
     * Genera la huella de un RegistroAnulacion.
     *
     * @return array{
     *     input: string,
     *     hash: string
     * }
     */
    public function generateCancellationHash(
        string $issuerTaxId,
        string $invoiceNumber,
        string $invoiceDate,
        ?string $previousHash,
        string $generatedAtIso
    ): array {
        $issuerTaxId =
            $this->cleanText(
                $issuerTaxId
            );

        $invoiceNumber =
            $this->cleanText(
                $invoiceNumber
            );

        $invoiceDate =
            $this->cleanText(
                $invoiceDate
            );

        $previousHash =
            $this->cleanText(
                $previousHash
                ?? ''
            );

        $generatedAtIso =
            $this->cleanText(
                $generatedAtIso
            );

        if ($issuerTaxId === '') {
            throw new RuntimeException(
                'IDEmisorFacturaAnulada no puede estar vacío.'
            );
        }

        if ($invoiceNumber === '') {
            throw new RuntimeException(
                'NumSerieFacturaAnulada no puede estar vacío.'
            );
        }

        if ($invoiceDate === '') {
            throw new RuntimeException(
                'FechaExpedicionFacturaAnulada no puede estar vacía.'
            );
        }

        if ($generatedAtIso === '') {
            throw new RuntimeException(
                'FechaHoraHusoGenRegistro no puede estar vacía.'
            );
        }

        if (
            $previousHash !== ''
            && !preg_match(
                '/^[A-F0-9]{64}$/',
                $previousHash
            )
        ) {
            throw new RuntimeException(
                'La huella anterior no tiene un formato SHA-256 válido.'
            );
        }

        $input =
            'IDEmisorFacturaAnulada='
            . $issuerTaxId

            . '&NumSerieFacturaAnulada='
            . $invoiceNumber

            . '&FechaExpedicionFacturaAnulada='
            . $invoiceDate

            . '&Huella='
            . $previousHash

            . '&FechaHoraHusoGenRegistro='
            . $generatedAtIso;

        $hash =
            strtoupper(
                hash(
                    'sha256',
                    $input
                )
            );

        if (
            strlen($hash) !== 64
            || !preg_match(
                '/^[A-F0-9]{64}$/',
                $hash
            )
        ) {
            throw new RuntimeException(
                'No se pudo generar una huella SHA-256 válida.'
            );
        }

        return [
            'input' =>
                $input,

            'hash' =>
                $hash,
        ];
    }

    private function normalizeNumber(
        string|float|int $value
    ): string {
        /*
         * DSM maneja importes monetarios con dos decimales.
         *
         * Generamos una representación sin ceros
         * innecesarios a la derecha:
         *
         * 9.9900 -> 9.99
         * 10.00  -> 10
         * 12.30  -> 12.3
         */
        if (is_float($value)) {
            $value =
                number_format(
                    $value,
                    10,
                    '.',
                    ''
                );
        }

        $value =
            trim(
                (string) $value
            );

        if (
            !preg_match(
                '/^-?\d+(?:\.\d+)?$/',
                $value
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'Valor numérico VERI*FACTU no válido: %s',
                    $value
                )
            );
        }

        if (str_contains($value, '.')) {
            $value =
                rtrim(
                    $value,
                    '0'
                );

            $value =
                rtrim(
                    $value,
                    '.'
                );
        }

        if ($value === '-0') {
            $value = '0';
        }

        return $value;
    }

    private function cleanText(
        string $value
    ): string {
        return trim(
            $value
        );
    }
}
