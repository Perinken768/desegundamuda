<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuResponseParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(
        string $xml
    ): array {
        $xml =
            trim(
                $xml
            );

        if ($xml === '') {
            throw new RuntimeException(
                'La respuesta SOAP de AEAT está vacía.'
            );
        }

        $document =
            new DOMDocument();

        /*
         * libxml_use_internal_errors() modifica estado global
         * del proceso PHP.
         *
         * Guardamos el valor anterior para no contaminar
         * WordPress ni otros plugins después del parseo.
         */
        $previousLibxmlState =
            libxml_use_internal_errors(
                true
            );

        libxml_clear_errors();

        try {
            $loaded =
                $document->loadXML(
                    $xml
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
            $message =
                'La respuesta de AEAT no contiene XML válido.';

            if ($errors !== []) {
                $first =
                    $errors[0];

                $message .=
                    ' '
                    . trim(
                        (string) $first->message
                    );
            }

            throw new RuntimeException(
                $message
            );
        }

        $xpath =
            new DOMXPath(
                $document
            );

        $fault =
            $this->parseSoapFault(
                $xpath
            );

        if ($fault !== null) {
            return [
                'is_fault' =>
                    true,

                'fault_code' =>
                    $fault[
                        'fault_code'
                    ],

                'fault_message' =>
                    $fault[
                        'fault_message'
                    ],

                'csv' =>
                    null,

                'presentation_tax_id' =>
                    null,

                'presentation_timestamp' =>
                    null,

                'wait_seconds' =>
                    null,

                'submission_status' =>
                    null,

                'records' =>
                    [],
            ];
        }

        $submissionStatus =
            $this->textByLocalName(
                $xpath,
                'EstadoEnvio'
            );

        if ($submissionStatus === null) {
            throw new RuntimeException(
                'La respuesta AEAT no contiene EstadoEnvio.'
            );
        }

        if (
            !in_array(
                $submissionStatus,
                [
                    'Correcto',
                    'ParcialmenteCorrecto',
                    'Incorrecto',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'EstadoEnvio AEAT desconocido: %s',
                    $submissionStatus
                )
            );
        }

        $records = [];

        $responseLines =
            $xpath->query(
                '//*[local-name()="RespuestaLinea"]'
            );

        if ($responseLines !== false) {
            foreach ($responseLines as $line) {
                if (!$line instanceof DOMElement) {
                    continue;
                }

                $records[] =
                    $this->parseResponseLine(
                        $line
                    );
            }
        }

        return [
            'is_fault' =>
                false,

            'fault_code' =>
                null,

            'fault_message' =>
                null,

            'csv' =>
                $this->textByLocalName(
                    $xpath,
                    'CSV'
                ),

            'presentation_tax_id' =>
                $this->textByLocalName(
                    $xpath,
                    'NIFPresentador'
                ),

            'presentation_timestamp' =>
                $this->textByLocalName(
                    $xpath,
                    'TimestampPresentacion'
                ),

            'wait_seconds' =>
                $this->integerByLocalName(
                    $xpath,
                    'TiempoEsperaEnvio'
                ),

            'submission_status' =>
                $submissionStatus,

            'records' =>
                $records,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponseLine(
        DOMElement $line
    ): array {
        $xpath =
            new DOMXPath(
                $line->ownerDocument
            );

        $idFactura =
            $this->directChild(
                $line,
                'IDFactura'
            );

        $operacion =
            $this->directChild(
                $line,
                'Operacion'
            );

        $recordStatus =
            $this->directChildText(
                $line,
                'EstadoRegistro'
            );

        if (
            $recordStatus !== null
            && !in_array(
                $recordStatus,
                [
                    'Correcto',
                    'AceptadoConErrores',
                    'Incorrecto',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'EstadoRegistro AEAT desconocido: %s',
                    $recordStatus
                )
            );
        }

        $duplicateNode =
            $this->directChild(
                $line,
                'RegistroDuplicado'
            );

        $duplicate = null;

        if ($duplicateNode instanceof DOMElement) {
            $duplicateStatus =
                $this->directChildText(
                    $duplicateNode,
                    'EstadoRegistroDuplicado'
                );

            if (
                $duplicateStatus !== null
                && !in_array(
                    $duplicateStatus,
                    [
                        'Correcta',
                        'AceptadaConErrores',
                        'Anulada',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    sprintf(
                        'EstadoRegistroDuplicado AEAT desconocido: %s',
                        $duplicateStatus
                    )
                );
            }

            $duplicate = [
                'request_id' =>
                    $this->directChildText(
                        $duplicateNode,
                        'IdPeticionRegistroDuplicado'
                    ),

                'status' =>
                    $duplicateStatus,

                'error_code' =>
                    $this->directChildText(
                        $duplicateNode,
                        'CodigoErrorRegistro'
                    ),

                'error_message' =>
                    $this->directChildText(
                        $duplicateNode,
                        'DescripcionErrorRegistro'
                    ),
            ];
        }

        return [
            'issuer_tax_id' =>
                $idFactura instanceof DOMElement
                    ? $this->directChildText(
                        $idFactura,
                        'IDEmisorFactura'
                    )
                    : null,

            'invoice_number' =>
                $idFactura instanceof DOMElement
                    ? $this->directChildText(
                        $idFactura,
                        'NumSerieFactura'
                    )
                    : null,

            'invoice_date' =>
                $idFactura instanceof DOMElement
                    ? $this->directChildText(
                        $idFactura,
                        'FechaExpedicionFactura'
                    )
                    : null,

            'operation' =>
                $operacion instanceof DOMElement
                    ? $this->directChildText(
                        $operacion,
                        'TipoOperacion'
                    )
                    : null,

            'subsanacion' =>
                $this->directChildText(
                    $line,
                    'Subsanacion'
                ),

            'rechazo_previo' =>
                $this->directChildText(
                    $line,
                    'RechazoPrevio'
                ),

            'sin_registro_previo' =>
                $this->directChildText(
                    $line,
                    'SinRegistroPrevio'
                ),

            'external_reference' =>
                $this->directChildText(
                    $line,
                    'RefExterna'
                ),

            'status' =>
                $recordStatus,

            /*
             * Estos dos son exclusivamente los errores
             * del registro enviado.
             *
             * No descendemos dentro de RegistroDuplicado.
             */
            'error_code' =>
                $this->directChildText(
                    $line,
                    'CodigoErrorRegistro'
                ),

            'error_message' =>
                $this->directChildText(
                    $line,
                    'DescripcionErrorRegistro'
                ),

            /*
             * Los errores del registro previamente
             * almacenado quedan separados.
             */
            'duplicate' =>
                $duplicate,
        ];
    }

    /**
     * @return array{
     *     fault_code: string|null,
     *     fault_message: string|null
     * }|null
     */
    private function parseSoapFault(
        DOMXPath $xpath
    ): ?array {
        $faultNodes =
            $xpath->query(
                '//*[local-name()="Fault"]'
            );

        if (
            $faultNodes === false
            || $faultNodes->length === 0
        ) {
            return null;
        }

        $fault =
            $faultNodes->item(0);

        if (!$fault instanceof DOMElement) {
            return null;
        }

        return [
            'fault_code' =>
                $this->directChildText(
                    $fault,
                    'faultcode'
                ),

            'fault_message' =>
                $this->directChildText(
                    $fault,
                    'faultstring'
                ),
        ];
    }

    private function textByLocalName(
        DOMXPath $xpath,
        string $localName
    ): ?string {
        $nodes =
            $xpath->query(
                '//*[local-name()="'
                . $localName
                . '"]'
            );

        if (
            $nodes === false
            || $nodes->length === 0
        ) {
            return null;
        }

        return $this->nodeText(
            $nodes->item(0)
        );
    }

    private function integerByLocalName(
        DOMXPath $xpath,
        string $localName
    ): ?int {
        $value =
            $this->textByLocalName(
                $xpath,
                $localName
            );

        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (
            !preg_match(
                '/^\d+$/',
                $value
            )
        ) {
            throw new RuntimeException(
                sprintf(
                    'El valor AEAT %s no es numérico: %s',
                    $localName,
                    $value
                )
            );
        }

        return (int) $value;
    }

    private function directChild(
        DOMElement $parent,
        string $localName
    ): ?DOMElement {
        foreach (
            $parent->childNodes
            as $child
        ) {
            if (
                $child instanceof DOMElement
                && $child->localName
                    === $localName
            ) {
                return $child;
            }
        }

        return null;
    }

    private function directChildText(
        DOMElement $parent,
        string $localName
    ): ?string {
        $child =
            $this->directChild(
                $parent,
                $localName
            );

        return $this->nodeText(
            $child
        );
    }

    private function nodeText(
        ?DOMNode $node
    ): ?string {
        if ($node === null) {
            return null;
        }

        $value =
            trim(
                (string) $node->textContent
            );

        return $value !== ''
            ? $value
            : null;
    }
}
