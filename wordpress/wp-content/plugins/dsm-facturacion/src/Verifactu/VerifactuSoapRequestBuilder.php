<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DOMDocument;
use DOMElement;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSoapRequestBuilder
{
    private const SOAP_NS =
        'http://schemas.xmlsoap.org/soap/envelope/';

    private const SUPPLY_NS =
        'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd';

    private const INFO_NS =
        'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd';

    private const MAX_RECORDS =
        1000;

    private string $recordsTable;

    public function __construct()
    {
        global $wpdb;

        $this->recordsTable =
            $wpdb->prefix
            . 'dsm_verifactu_records';
    }

    /**
     * Construye una remisión SOAP VERI*FACTU.
     *
     * $incidence = true:
     *
     * <RemisionVoluntaria>
     *     <Incidencia>S</Incidencia>
     * </RemisionVoluntaria>
     *
     * IMPORTANTE:
     * Esto modifica únicamente la cabecera de la remisión.
     * Los payload XML fiscales congelados no se reconstruyen.
     *
     * @param array<int> $recordIds
     */
    public function build(
        array $recordIds,
        bool $incidence = false
    ): string {
        global $wpdb;

        $recordIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $recordIds
                        ),
                        static fn (int $id): bool =>
                            $id > 0
                    )
                )
            );

        if ($recordIds === []) {
            throw new RuntimeException(
                'No se han indicado registros VERI*FACTU para la remisión.'
            );
        }

        if (
            count($recordIds)
            > self::MAX_RECORDS
        ) {
            throw new RuntimeException(
                sprintf(
                    'Una remisión VERI*FACTU no puede contener más de %d registros.',
                    self::MAX_RECORDS
                )
            );
        }

        $rows = [];

        foreach ($recordIds as $recordId) {
            $row =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT
                            id,
                            environment,
                            issuer_fiscal_name,
                            issuer_tax_id,
                            payload_version,
                            payload_xml
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

            if (
                trim(
                    (string) (
                        $row['payload_xml']
                        ?? ''
                    )
                ) === ''
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El registro VERI*FACTU %d no tiene payload XML congelado.',
                        $recordId
                    )
                );
            }

            if (
                trim(
                    (string) (
                        $row['payload_version']
                        ?? ''
                    )
                ) !== VerifactuXmlGenerator::VERSION
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El registro VERI*FACTU %d tiene una versión de payload no soportada.',
                        $recordId
                    )
                );
            }

            $rows[] =
                $row;
        }

        $this->assertSameIssuer(
            $rows
        );

        $this->assertSameEnvironment(
            $rows
        );

        $document =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $document->formatOutput =
            true;

        $envelope =
            $document->createElementNS(
                self::SOAP_NS,
                'soapenv:Envelope'
            );

        $document->appendChild(
            $envelope
        );

        $envelope->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:sum',
            self::SUPPLY_NS
        );

        $envelope->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:sum1',
            self::INFO_NS
        );

        /*
         * SOAP Header.
         */
        $header =
            $document->createElementNS(
                self::SOAP_NS,
                'soapenv:Header'
            );

        $envelope->appendChild(
            $header
        );

        /*
         * SOAP Body.
         */
        $body =
            $document->createElementNS(
                self::SOAP_NS,
                'soapenv:Body'
            );

        $envelope->appendChild(
            $body
        );

        /*
         * RegFactuSistemaFacturacion.
         */
        $request =
            $document->createElementNS(
                self::SUPPLY_NS,
                'sum:RegFactuSistemaFacturacion'
            );

        $body->appendChild(
            $request
        );

        /*
         * ==================================================
         * CABECERA
         * ==================================================
         */
        $cabecera =
            $document->createElementNS(
                self::SUPPLY_NS,
                'sum:Cabecera'
            );

        $request->appendChild(
            $cabecera
        );

        $obligado =
            $document->createElementNS(
                self::INFO_NS,
                'sum1:ObligadoEmision'
            );

        $cabecera->appendChild(
            $obligado
        );

        $this->appendInfoElement(
            $document,
            $obligado,
            'NombreRazon',
            (string) $rows[0]['issuer_fiscal_name']
        );

        $this->appendInfoElement(
            $document,
            $obligado,
            'NIF',
            (string) $rows[0]['issuer_tax_id']
        );

        /*
         * Incidencia pertenece a la cabecera de la
         * remisión voluntaria, NO al registro fiscal.
         *
         * Orden XSD:
         *
         * ObligadoEmision
         * Representante?
         * RemisionVoluntaria?
         * RemisionRequerimiento?
         */
        if ($incidence) {
            $remisionVoluntaria =
                $document->createElementNS(
                    self::INFO_NS,
                    'sum1:RemisionVoluntaria'
                );

            $cabecera->appendChild(
                $remisionVoluntaria
            );

            $this->appendInfoElement(
                $document,
                $remisionVoluntaria,
                'Incidencia',
                'S'
            );
        }

        /*
         * ==================================================
         * REGISTROS
         * ==================================================
         */
        foreach ($rows as $row) {
            $registroFactura =
                $document->createElementNS(
                    self::SUPPLY_NS,
                    'sum:RegistroFactura'
                );

            $request->appendChild(
                $registroFactura
            );

            $payload =
                new DOMDocument();

            $previousLibxmlState =
                libxml_use_internal_errors(
                    true
                );

            libxml_clear_errors();

            try {
                $loaded =
                    $payload->loadXML(
                        (string) $row['payload_xml']
                    );

                $errors =
                    libxml_get_errors();
            } finally {
                libxml_clear_errors();

                libxml_use_internal_errors(
                    $previousLibxmlState
                );
            }

            if (!$loaded) {
                throw new RuntimeException(
                    sprintf(
                        'El payload XML del registro VERI*FACTU %d no es XML válido. %s',
                        (int) $row['id'],
                        $this->formatLibxmlErrors(
                            $errors
                        )
                    )
                );
            }

            $payloadRoot =
                $payload->documentElement;

            if (
                !$payloadRoot instanceof DOMElement
                || $payloadRoot->namespaceURI
                    !== self::INFO_NS
                || !in_array(
                    $payloadRoot->localName,
                    [
                        'RegistroAlta',
                        'RegistroAnulacion',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    sprintf(
                        'El payload del registro VERI*FACTU %d no contiene RegistroAlta ni RegistroAnulacion válido.',
                        (int) $row['id']
                    )
                );
            }

            /*
             * Importamos literalmente el payload fiscal
             * congelado.
             */
            $imported =
                $document->importNode(
                    $payloadRoot,
                    true
                );

            $registroFactura->appendChild(
                $imported
            );
        }

        $xml =
            $document->saveXML();

        if (
            $xml === false
            || trim(
                $xml
            ) === ''
        ) {
            throw new RuntimeException(
                'No se pudo generar la solicitud SOAP VERI*FACTU.'
            );
        }

        return $xml;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function assertSameIssuer(
        array $rows
    ): void {
        $issuerTaxId =
            trim(
                (string) $rows[0]['issuer_tax_id']
            );

        $issuerFiscalName =
            trim(
                (string) $rows[0]['issuer_fiscal_name']
            );

        if (
            $issuerTaxId === ''
            || $issuerFiscalName === ''
        ) {
            throw new RuntimeException(
                'El obligado a la emisión no está correctamente identificado.'
            );
        }

        foreach ($rows as $row) {
            if (
                trim(
                    (string) $row['issuer_tax_id']
                ) !== $issuerTaxId
                || trim(
                    (string) $row['issuer_fiscal_name']
                ) !== $issuerFiscalName
            ) {
                throw new RuntimeException(
                    'Todos los registros de una misma remisión deben pertenecer al mismo obligado a la emisión.'
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function assertSameEnvironment(
        array $rows
    ): void {
        $environment =
            trim(
                (string) $rows[0]['environment']
            );

        if ($environment === '') {
            throw new RuntimeException(
                'El entorno VERI*FACTU del registro está vacío.'
            );
        }

        foreach ($rows as $row) {
            if (
                trim(
                    (string) $row['environment']
                ) !== $environment
            ) {
                throw new RuntimeException(
                    'No se pueden mezclar registros VERI*FACTU de distintos entornos en una misma remisión.'
                );
            }
        }
    }

    private function appendInfoElement(
        DOMDocument $document,
        DOMElement $parent,
        string $name,
        string $value
    ): DOMElement {
        $element =
            $document->createElementNS(
                self::INFO_NS,
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

    /**
     * @param array<int, \LibXMLError> $errors
     */
    private function formatLibxmlErrors(
        array $errors
    ): string {
        $messages = [];

        foreach ($errors as $error) {
            $message =
                trim(
                    $error->message
                );

            if ($message !== '') {
                $messages[] =
                    $message;
            }
        }

        return implode(
            ' | ',
            $messages
        );
    }
}
