<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DOMDocument;
use DOMElement;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuXmlGenerator
{
    public const VERSION =
        '1.0';

    public const TAX_IGIC =
        '03';

    public const REGIME_GENERAL =
        '01';

    public const OPERATION_SUBJECT_NOT_EXEMPT =
        'S1';

    private const NS =
        'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd';

    private string $recordsTable;

    private VerifactuRecordRepository $recordRepository;

    public function __construct()
    {
        global $wpdb;

        $this->recordsTable =
            $wpdb->prefix
            . 'dsm_verifactu_records';

        $this->recordRepository =
            new VerifactuRecordRepository();
    }

    public function generate(
        int $recordId
    ): string {
        /*
         * Un payload fiscal congelado jamás se
         * reconstruye.
         */
        $storedPayload =
            $this->recordRepository
                ->findPayload(
                    $recordId
                );

        if ($storedPayload !== null) {
            return $storedPayload['xml'];
        }

        $xml =
            $this->build(
                $recordId
            );

        return $this->recordRepository
            ->persistPayload(
                $recordId,
                self::VERSION,
                $xml
            );
    }

    private function build(
        int $recordId
    ): string {
        global $wpdb;

        if ($recordId <= 0) {
            throw new RuntimeException(
                'El identificador del registro VERI*FACTU no es válido.'
            );
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->recordsTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $recordId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            throw new RuntimeException(
                sprintf(
                    'No existe el registro VERI*FACTU %d.',
                    $recordId
                )
            );
        }

        $recordType =
            trim(
                (string) (
                    $row['record_type']
                    ?? ''
                )
            );

        return match ($recordType) {
            'alta' =>
                $this->buildRegistration(
                    $row
                ),

            'anulacion' =>
                $this->buildCancellation(
                    $row
                ),

            default =>
                throw new RuntimeException(
                    sprintf(
                        'Tipo de registro VERI*FACTU no soportado: %s',
                        $recordType
                    )
                ),
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildRegistration(
        array $row
    ): string {
        $this->validateRegistration(
            $row
        );

        $customerSnapshot =
            $this->decodeSnapshot(
                $row['customer_snapshot']
                ?? null,
                'cliente'
            );

        $document =
            $this->createDocument();

        $root =
            $document->createElementNS(
                self::NS,
                'sum1:RegistroAlta'
            );

        $document->appendChild(
            $root
        );

        $this->append(
            $document,
            $root,
            'IDVersion',
            self::VERSION
        );

        $invoiceId =
            $this->appendContainer(
                $document,
                $root,
                'IDFactura'
            );

        $this->append(
            $document,
            $invoiceId,
            'IDEmisorFactura',
            (string) $row['issuer_tax_id']
        );

        $this->append(
            $document,
            $invoiceId,
            'NumSerieFactura',
            (string) $row['invoice_number']
        );

        $this->append(
            $document,
            $invoiceId,
            'FechaExpedicionFactura',
            $this->formatFiscalDate(
                (string) $row['invoice_date']
            )
        );

        $this->append(
            $document,
            $root,
            'NombreRazonEmisor',
            (string) $row['issuer_fiscal_name']
        );

        /*
         * Orden XSD:
         *
         * NombreRazonEmisor
         * Subsanacion?
         * RechazoPrevio?
         * TipoFactura
         */
        $subsanacion =
            $this->nullableMarker(
                $row['subsanacion']
                ?? null
            );

        if ($subsanacion !== null) {
            $this->append(
                $document,
                $root,
                'Subsanacion',
                $subsanacion
            );
        }

        $rechazoPrevio =
            $this->nullableMarker(
                $row['rechazo_previo']
                ?? null
            );

        if ($rechazoPrevio !== null) {
            $this->append(
                $document,
                $root,
                'RechazoPrevio',
                $rechazoPrevio
            );
        }

        $this->append(
            $document,
            $root,
            'TipoFactura',
            (string) $row['invoice_type']
        );

        $this->append(
            $document,
            $root,
            'DescripcionOperacion',
            (string) $row['description']
        );

        $customerName =
            trim(
                (string) (
                    $customerSnapshot['fiscal_name']
                    ?? ''
                )
            );

        $customerTaxId =
            trim(
                (string) (
                    $customerSnapshot['tax_id']
                    ?? ''
                )
            );

        if (
            $customerName === ''
            || $customerTaxId === ''
        ) {
            throw new RuntimeException(
                'La factura F1 requiere destinatario fiscal identificado.'
            );
        }

        $destinations =
            $this->appendContainer(
                $document,
                $root,
                'Destinatarios'
            );

        $destination =
            $this->appendContainer(
                $document,
                $destinations,
                'IDDestinatario'
            );

        $this->append(
            $document,
            $destination,
            'NombreRazon',
            $customerName
        );

        $this->append(
            $document,
            $destination,
            'NIF',
            $customerTaxId
        );

        $breakdown =
            $this->appendContainer(
                $document,
                $root,
                'Desglose'
            );

        $detail =
            $this->appendContainer(
                $document,
                $breakdown,
                'DetalleDesglose'
            );

        $this->append(
            $document,
            $detail,
            'Impuesto',
            self::TAX_IGIC
        );

        $this->append(
            $document,
            $detail,
            'ClaveRegimen',
            self::REGIME_GENERAL
        );

        $this->append(
            $document,
            $detail,
            'CalificacionOperacion',
            self::OPERATION_SUBJECT_NOT_EXEMPT
        );

        $this->append(
            $document,
            $detail,
            'TipoImpositivo',
            $this->normalizeNumber(
                $row['tax_rate']
            )
        );

        $this->append(
            $document,
            $detail,
            'BaseImponibleOimporteNoSujeto',
            $this->normalizeNumber(
                $row['tax_base']
            )
        );

        $this->append(
            $document,
            $detail,
            'CuotaRepercutida',
            $this->normalizeNumber(
                $row['tax_amount']
            )
        );

        $this->append(
            $document,
            $root,
            'CuotaTotal',
            $this->normalizeNumber(
                $row['tax_amount']
            )
        );

        $this->append(
            $document,
            $root,
            'ImporteTotal',
            $this->normalizeNumber(
                $row['total_amount']
            )
        );

        $this->appendChaining(
            $document,
            $root,
            $row
        );

        $this->appendSystem(
            $document,
            $root,
            $row
        );

        $this->appendFiscalFingerprint(
            $document,
            $root,
            $row
        );

        return $this->saveDocument(
            $document
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildCancellation(
        array $row
    ): string {
        $this->validateCancellation(
            $row
        );

        $document =
            $this->createDocument();

        $root =
            $document->createElementNS(
                self::NS,
                'sum1:RegistroAnulacion'
            );

        $document->appendChild(
            $root
        );

        $this->append(
            $document,
            $root,
            'IDVersion',
            self::VERSION
        );

        $invoiceId =
            $this->appendContainer(
                $document,
                $root,
                'IDFactura'
            );

        $this->append(
            $document,
            $invoiceId,
            'IDEmisorFacturaAnulada',
            (string) $row['issuer_tax_id']
        );

        $this->append(
            $document,
            $invoiceId,
            'NumSerieFacturaAnulada',
            (string) $row['invoice_number']
        );

        $this->append(
            $document,
            $invoiceId,
            'FechaExpedicionFacturaAnulada',
            $this->formatFiscalDate(
                (string) $row['invoice_date']
            )
        );

        /*
         * Orden XSD:
         *
         * IDFactura
         * RefExterna?
         * SinRegistroPrevio?
         * RechazoPrevio?
         * GeneradoPor?
         * Generador?
         * Encadenamiento
         *
         * DSM no usa todavía RefExterna,
         * GeneradoPor ni Generador.
         */
        $sinRegistroPrevio =
            $this->nullableMarker(
                $row['sin_registro_previo']
                ?? null
            );

        if ($sinRegistroPrevio !== null) {
            $this->append(
                $document,
                $root,
                'SinRegistroPrevio',
                $sinRegistroPrevio
            );
        }

        $rechazoPrevio =
            $this->nullableMarker(
                $row['rechazo_previo']
                ?? null
            );

        if ($rechazoPrevio !== null) {
            $this->append(
                $document,
                $root,
                'RechazoPrevio',
                $rechazoPrevio
            );
        }

        $this->appendChaining(
            $document,
            $root,
            $row
        );

        $this->appendSystem(
            $document,
            $root,
            $row
        );

        $this->appendFiscalFingerprint(
            $document,
            $root,
            $row
        );

        return $this->saveDocument(
            $document
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateRegistration(
        array $row
    ): void {
        $this->validateCommonRecord(
            $row
        );

        if (
            (string) (
                $row['record_type']
                ?? ''
            ) !== 'alta'
        ) {
            throw new RuntimeException(
                'El registro no es un RegistroAlta.'
            );
        }

        if (
            trim(
                (string) (
                    $row['invoice_type']
                    ?? ''
                )
            ) !== 'F1'
        ) {
            throw new RuntimeException(
                'El generador XML actual de DSM solamente admite facturas F1.'
            );
        }

        if (
            strtoupper(
                trim(
                    (string) (
                        $row['tax_type']
                        ?? ''
                    )
                )
            ) !== 'IGIC'
        ) {
            throw new RuntimeException(
                'El generador XML actual de DSM solamente admite IGIC.'
            );
        }

        if (
            !isset(
                $row['tax_rate']
            )
            || !is_numeric(
                $row['tax_rate']
            )
        ) {
            throw new RuntimeException(
                'El RegistroAlta no contiene un tipo impositivo válido.'
            );
        }

        if (
            !isset(
                $row['tax_base'],
                $row['tax_amount'],
                $row['total_amount']
            )
        ) {
            throw new RuntimeException(
                'El RegistroAlta no contiene importes fiscales completos.'
            );
        }

        $subsanacion =
            $this->nullableMarker(
                $row['subsanacion']
                ?? null
            );

        $rechazoPrevio =
            $this->nullableMarker(
                $row['rechazo_previo']
                ?? null
            );

        $sinRegistroPrevio =
            $this->nullableMarker(
                $row['sin_registro_previo']
                ?? null
            );

        $this->validateMarker(
            'Subsanacion',
            $subsanacion,
            [
                'S',
                'N',
            ]
        );

        $this->validateMarker(
            'RechazoPrevio',
            $rechazoPrevio,
            [
                'N',
                'S',
                'X',
            ]
        );

        if ($sinRegistroPrevio !== null) {
            throw new RuntimeException(
                'SinRegistroPrevio no es válido en un RegistroAlta.'
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateCancellation(
        array $row
    ): void {
        $this->validateCommonRecord(
            $row
        );

        if (
            (string) (
                $row['record_type']
                ?? ''
            ) !== 'anulacion'
        ) {
            throw new RuntimeException(
                'El registro no es un RegistroAnulacion.'
            );
        }

        $generationType =
            trim(
                (string) (
                    $row['generation_type']
                    ?? ''
                )
            );

        if (
            !in_array(
                $generationType,
                [
                    'normal',
                    'subsanacion',
                    'sin_registro_previo',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'Tipo de generación de anulación no soportado: %s',
                    $generationType
                )
            );
        }

        if (
            !isset(
                $row['source_record_id']
            )
            || (int) $row['source_record_id'] <= 0
        ) {
            throw new RuntimeException(
                'El RegistroAnulacion no identifica el registro fiscal de origen.'
            );
        }

        /*
         * Todos los RegistroAnulacion creados por DSM
         * parten de una cadena local existente.
         *
         * Incluso SinRegistroPrevio=S significa
         * "sin registro previo en AEAT", no
         * "sin registro anterior en nuestra cadena".
         */
        if (
            !isset(
                $row['previous_record_id']
            )
            || $row['previous_record_id'] === null
        ) {
            throw new RuntimeException(
                'El RegistroAnulacion no contiene registro anterior de cadena.'
            );
        }

        $subsanacion =
            $this->nullableMarker(
                $row['subsanacion']
                ?? null
            );

        $rechazoPrevio =
            $this->nullableMarker(
                $row['rechazo_previo']
                ?? null
            );

        $sinRegistroPrevio =
            $this->nullableMarker(
                $row['sin_registro_previo']
                ?? null
            );

        if ($subsanacion !== null) {
            throw new RuntimeException(
                'Subsanacion no es válido en un RegistroAnulacion.'
            );
        }

        $this->validateMarker(
            'SinRegistroPrevio',
            $sinRegistroPrevio,
            [
                'S',
                'N',
            ]
        );

        $this->validateMarker(
            'RechazoPrevio',
            $rechazoPrevio,
            [
                'S',
                'N',
            ]
        );

        /*
         * ==================================================
         * Reglas DSM por tipo de anulación
         * ==================================================
         */

        if ($generationType === 'normal') {
            if ($sinRegistroPrevio !== null) {
                throw new RuntimeException(
                    'Una anulación normal no puede indicar SinRegistroPrevio.'
                );
            }

            if ($rechazoPrevio !== null) {
                throw new RuntimeException(
                    'Una anulación normal no puede indicar RechazoPrevio.'
                );
            }

            return;
        }

        if ($generationType === 'subsanacion') {
            if ($sinRegistroPrevio !== null) {
                throw new RuntimeException(
                    'Una subsanación de anulación rechazada no puede indicar SinRegistroPrevio.'
                );
            }

            if ($rechazoPrevio !== 'S') {
                throw new RuntimeException(
                    'Una subsanación de anulación rechazada debe indicar RechazoPrevio=S.'
                );
            }

            return;
        }

        /*
         * SinRegistroPrevio=S.
         *
         * Este flujo se utiliza cuando la clave de
         * factura que queremos anular no consta
         * registrada en AEAT.
         *
         * DSM omite RechazoPrevio en este supuesto.
         */
        if (
            $generationType
            === 'sin_registro_previo'
        ) {
            if ($sinRegistroPrevio !== 'S') {
                throw new RuntimeException(
                    'Una anulación sin registro previo debe indicar SinRegistroPrevio=S.'
                );
            }

            if ($rechazoPrevio !== null) {
                throw new RuntimeException(
                    'DSM no utiliza RechazoPrevio en una anulación con SinRegistroPrevio=S.'
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function validateCommonRecord(
        array $row
    ): void {
        if (
            trim(
                (string) (
                    $row['issuer_tax_id']
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                'El registro no contiene NIF del emisor.'
            );
        }

        if (
            trim(
                (string) (
                    $row['invoice_number']
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                'El registro no contiene número de factura.'
            );
        }

        if (
            trim(
                (string) (
                    $row['invoice_date']
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                'El registro no contiene fecha de expedición.'
            );
        }

        $systemName =
            trim(
                (string) (
                    $row['system_name']
                    ?? ''
                )
            );

        $systemId =
            trim(
                (string) (
                    $row['system_id']
                    ?? ''
                )
            );

        $systemVersion =
            trim(
                (string) (
                    $row['system_version']
                    ?? ''
                )
            );

        $installationId =
            trim(
                (string) (
                    $row['installation_id']
                    ?? ''
                )
            );

        if (
            $systemName === ''
            || $systemId === ''
            || $systemVersion === ''
            || $installationId === ''
        ) {
            throw new RuntimeException(
                'La identificación del sistema VERI*FACTU está incompleta.'
            );
        }

        if (
            strlen(
                $systemId
            ) > 2
        ) {
            throw new RuntimeException(
                'IdSistemaInformatico debe contener entre 1 y 2 caracteres.'
            );
        }

        $hashAlgorithm =
            trim(
                (string) (
                    $row['hash_algorithm']
                    ?? ''
                )
            );

        if (
            $hashAlgorithm
            !== VerifactuHashGenerator::HASH_TYPE
        ) {
            throw new RuntimeException(
                'TipoHuella VERI*FACTU no soportado.'
            );
        }

        $hash =
            trim(
                (string) (
                    $row['hash_value']
                    ?? ''
                )
            );

        if (
            !preg_match(
                '/^[A-F0-9]{64}$/',
                $hash
            )
        ) {
            throw new RuntimeException(
                'El registro no contiene una huella SHA-256 válida.'
            );
        }

        if (
            trim(
                (string) (
                    $row['generated_at_iso']
                    ?? ''
                )
            ) === ''
        ) {
            throw new RuntimeException(
                'El registro no contiene FechaHoraHusoGenRegistro.'
            );
        }
    }

    private function validateMarker(
        string $name,
        ?string $value,
        array $allowedValues
    ): void {
        if ($value === null) {
            return;
        }

        if (
            !in_array(
                $value,
                $allowedValues,
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'Valor VERI*FACTU no válido para %s: %s',
                    $name,
                    $value
                )
            );
        }
    }

    private function nullableMarker(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            strtoupper(
                trim(
                    (string) $value
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function appendChaining(
        DOMDocument $document,
        DOMElement $root,
        array $row
    ): void {
        $chaining =
            $this->appendContainer(
                $document,
                $root,
                'Encadenamiento'
            );

        $previousRecordId =
            isset(
                $row['previous_record_id']
            )
            && $row['previous_record_id'] !== null
                ? (int) $row['previous_record_id']
                : null;

        if ($previousRecordId === null) {
            $this->append(
                $document,
                $chaining,
                'PrimerRegistro',
                'S'
            );

            return;
        }

        $previousIssuerTaxId =
            trim(
                (string) (
                    $row['previous_issuer_tax_id']
                    ?? ''
                )
            );

        $previousInvoiceNumber =
            trim(
                (string) (
                    $row['previous_invoice_number']
                    ?? ''
                )
            );

        $previousInvoiceDate =
            trim(
                (string) (
                    $row['previous_invoice_date']
                    ?? ''
                )
            );

        $previousHash =
            trim(
                (string) (
                    $row['previous_hash']
                    ?? ''
                )
            );

        if (
            $previousIssuerTaxId === ''
            || $previousInvoiceNumber === ''
            || $previousInvoiceDate === ''
            || !preg_match(
                '/^[A-F0-9]{64}$/',
                $previousHash
            )
        ) {
            throw new RuntimeException(
                'El encadenamiento VERI*FACTU anterior está incompleto.'
            );
        }

        $previous =
            $this->appendContainer(
                $document,
                $chaining,
                'RegistroAnterior'
            );

        $this->append(
            $document,
            $previous,
            'IDEmisorFactura',
            $previousIssuerTaxId
        );

        $this->append(
            $document,
            $previous,
            'NumSerieFactura',
            $previousInvoiceNumber
        );

        $this->append(
            $document,
            $previous,
            'FechaExpedicionFactura',
            $this->formatFiscalDate(
                $previousInvoiceDate
            )
        );

        $this->append(
            $document,
            $previous,
            'Huella',
            $previousHash
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function appendSystem(
        DOMDocument $document,
        DOMElement $root,
        array $row
    ): void {
        $system =
            $this->appendContainer(
                $document,
                $root,
                'SistemaInformatico'
            );

        /*
         * DSM es actualmente un SIF desarrollado
         * para el propio obligado tributario.
         *
         * Antes de producción se fijará la identidad
         * real del productor en la declaración
         * responsable.
         */
        $this->append(
            $document,
            $system,
            'NombreRazon',
            (string) $row['issuer_fiscal_name']
        );

        $this->append(
            $document,
            $system,
            'NIF',
            (string) $row['issuer_tax_id']
        );

        $this->append(
            $document,
            $system,
            'NombreSistemaInformatico',
            (string) $row['system_name']
        );

        $this->append(
            $document,
            $system,
            'IdSistemaInformatico',
            (string) $row['system_id']
        );

        $this->append(
            $document,
            $system,
            'Version',
            (string) $row['system_version']
        );

        $this->append(
            $document,
            $system,
            'NumeroInstalacion',
            (string) $row['installation_id']
        );

        $this->append(
            $document,
            $system,
            'TipoUsoPosibleSoloVerifactu',
            'S'
        );

        $this->append(
            $document,
            $system,
            'TipoUsoPosibleMultiOT',
            'N'
        );

        $this->append(
            $document,
            $system,
            'IndicadorMultiplesOT',
            'N'
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function appendFiscalFingerprint(
        DOMDocument $document,
        DOMElement $root,
        array $row
    ): void {
        $this->append(
            $document,
            $root,
            'FechaHoraHusoGenRegistro',
            (string) $row['generated_at_iso']
        );

        $this->append(
            $document,
            $root,
            'TipoHuella',
            (string) $row['hash_algorithm']
        );

        $this->append(
            $document,
            $root,
            'Huella',
            (string) $row['hash_value']
        );
    }

    private function createDocument(): DOMDocument
    {
        $document =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $document->formatOutput =
            true;

        return $document;
    }

    private function saveDocument(
        DOMDocument $document
    ): string {
        $xml =
            $document->saveXML();

        if (
            $xml === false
            || trim(
                $xml
            ) === ''
        ) {
            throw new RuntimeException(
                'No se pudo generar el XML VERI*FACTU.'
            );
        }

        return $xml;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSnapshot(
        mixed $value,
        string $label
    ): array {
        if (
            !is_string(
                $value
            )
            || trim(
                $value
            ) === ''
        ) {
            throw new RuntimeException(
                sprintf(
                    'El snapshot de %s está vacío.',
                    $label
                )
            );
        }

        $decoded =
            json_decode(
                $value,
                true
            );

        if (!is_array($decoded)) {
            throw new RuntimeException(
                sprintf(
                    'El snapshot de %s no contiene JSON válido.',
                    $label
                )
            );
        }

        return $decoded;
    }

    private function appendContainer(
        DOMDocument $document,
        DOMElement $parent,
        string $name
    ): DOMElement {
        $element =
            $document->createElementNS(
                self::NS,
                'sum1:' . $name
            );

        $parent->appendChild(
            $element
        );

        return $element;
    }

    private function append(
        DOMDocument $document,
        DOMElement $parent,
        string $name,
        string $value
    ): DOMElement {
        $element =
            $document->createElementNS(
                self::NS,
                'sum1:' . $name
            );

        $element->appendChild(
            $document->createTextNode(
                trim(
                    $value
                )
            )
        );

        $parent->appendChild(
            $element
        );

        return $element;
    }

    private function formatFiscalDate(
        string $value
    ): string {
        $value =
            trim(
                $value
            );

        $date =
            \DateTimeImmutable::createFromFormat(
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
                    'Fecha fiscal VERI*FACTU no válida: %s',
                    $value
                )
            );
        }

        return $date->format(
            'd-m-Y'
        );
    }

    private function normalizeNumber(
        mixed $value
    ): string {
        if (!is_numeric($value)) {
            throw new RuntimeException(
                'Se ha recibido un importe VERI*FACTU no numérico.'
            );
        }

        $number =
            number_format(
                (float) $value,
                10,
                '.',
                ''
            );

        $number =
            rtrim(
                $number,
                '0'
            );

        $number =
            rtrim(
                $number,
                '.'
            );

        if (
            $number === ''
            || $number === '-0'
        ) {
            return '0';
        }

        return $number;
    }
}
