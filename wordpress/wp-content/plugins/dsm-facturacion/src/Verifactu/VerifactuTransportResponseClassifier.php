<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clasifica una respuesta HTTP/SOAP antes de que
 * pueda considerarse una respuesta fiscal AEAT.
 *
 * Esta clase:
 *
 * - no realiza red;
 * - no modifica base de datos;
 * - no procesa EstadoRegistro;
 * - no decide si una factura fue aceptada;
 * - únicamente clasifica transporte/protocolo.
 */
final class VerifactuTransportResponseClassifier
{
    public const RESULT_FISCAL_RESPONSE =
        'fiscal_response';

    public const RESULT_RETRYABLE_ERROR =
        'retryable_error';

    public const RESULT_PERMANENT_ERROR =
        'permanent_error';

    public const RESULT_INVALID_RESPONSE =
        'invalid_response';

    /**
     * @return array<string, mixed>
     */
    public function classify(
        int $httpStatus,
        string $responseXml
    ): array {
        $responseXml =
            trim(
                $responseXml
            );

        /*
         * ==================================================
         * HTTP temporal
         * ==================================================
         */
        if (
            in_array(
                $httpStatus,
                [
                    408,
                    425,
                    429,
                    500,
                    502,
                    503,
                    504,
                ],
                true
            )
        ) {
            return $this->result(
                self::RESULT_RETRYABLE_ERROR,
                true,
                'http_temporary',
                sprintf(
                    'AEAT respondió con HTTP %d, considerado temporal.',
                    $httpStatus
                ),
                $httpStatus
            );
        }

        /*
         * ==================================================
         * HTTP cliente / permanente
         * ==================================================
         *
         * No se reintenta ciegamente una petición que
         * el servidor ha rechazado como error del cliente.
         */
        if (
            $httpStatus >= 400
            && $httpStatus <= 499
        ) {
            return $this->result(
                self::RESULT_PERMANENT_ERROR,
                false,
                'http_client_error',
                sprintf(
                    'AEAT respondió con HTTP %d. Requiere revisión antes de reenviar.',
                    $httpStatus
                ),
                $httpStatus
            );
        }

        /*
         * Otros 5xx se consideran igualmente temporales.
         */
        if (
            $httpStatus >= 500
            && $httpStatus <= 599
        ) {
            return $this->result(
                self::RESULT_RETRYABLE_ERROR,
                true,
                'http_server_error',
                sprintf(
                    'AEAT respondió con HTTP %d, error de servidor.',
                    $httpStatus
                ),
                $httpStatus
            );
        }

        /*
         * Cualquier HTTP fuera de 2xx no reconocido
         * explícitamente no debe avanzar al procesador
         * fiscal.
         */
        if (
            $httpStatus < 200
            || $httpStatus >= 300
        ) {
            return $this->result(
                self::RESULT_PERMANENT_ERROR,
                false,
                'http_unexpected',
                sprintf(
                    'AEAT respondió con un estado HTTP inesperado: %d.',
                    $httpStatus
                ),
                $httpStatus
            );
        }

        /*
         * ==================================================
         * HTTP 2xx pero sin cuerpo
         * ==================================================
         */
        if ($responseXml === '') {
            return $this->result(
                self::RESULT_RETRYABLE_ERROR,
                true,
                'empty_response',
                sprintf(
                    'AEAT respondió con HTTP %d pero sin cuerpo SOAP.',
                    $httpStatus
                ),
                $httpStatus
            );
        }

        /*
         * ==================================================
         * XML
         * ==================================================
         */
        $document =
            new DOMDocument();

        $previousLibxmlState =
            libxml_use_internal_errors(
                true
            );

        try {
            libxml_clear_errors();

            $loaded =
                $document->loadXML(
                    $responseXml
                );

            $errors =
                libxml_get_errors();

            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors(
                $previousLibxmlState
            );
        }

        if (!$loaded) {
            $detail =
                '';

            if (
                isset(
                    $errors[0]
                )
            ) {
                $detail =
                    trim(
                        (string) $errors[0]->message
                    );
            }

            return $this->result(
                self::RESULT_INVALID_RESPONSE,
                true,
                'invalid_xml',
                $detail !== ''
                    ? sprintf(
                        'AEAT respondió con XML no válido: %s',
                        $detail
                    )
                    : 'AEAT respondió con XML no válido.',
                $httpStatus
            );
        }

        $xpath =
            new DOMXPath(
                $document
            );

        /*
         * ==================================================
         * SOAP Fault
         * ==================================================
         */
        $faultNodes =
            $xpath->query(
                '//*[local-name()="Fault"]'
            );

        if (
            $faultNodes !== false
            && $faultNodes->length > 0
        ) {
            $fault =
                $faultNodes->item(
                    0
                );

            if ($fault instanceof DOMElement) {
                $faultCode =
                    $this->directChildText(
                        $fault,
                        'faultcode'
                    );

                $faultMessage =
                    $this->directChildText(
                        $fault,
                        'faultstring'
                    );

                $normalizedCode =
                    strtolower(
                        trim(
                            (string) $faultCode
                        )
                    );

                /*
                 * SOAP 1.1:
                 *
                 * Server -> problema servidor, se puede
                 * reintentar.
                 *
                 * Client -> problema petición, no se debe
                 * reenviar sin corregirla.
                 */
                if (
                    str_contains(
                        $normalizedCode,
                        'server'
                    )
                ) {
                    return $this->result(
                        self::RESULT_RETRYABLE_ERROR,
                        true,
                        'soap_fault_server',
                        $this->faultMessage(
                            'SOAP Fault de servidor',
                            $faultCode,
                            $faultMessage
                        ),
                        $httpStatus,
                        $faultCode,
                        $faultMessage
                    );
                }

                if (
                    str_contains(
                        $normalizedCode,
                        'client'
                    )
                ) {
                    return $this->result(
                        self::RESULT_PERMANENT_ERROR,
                        false,
                        'soap_fault_client',
                        $this->faultMessage(
                            'SOAP Fault de cliente',
                            $faultCode,
                            $faultMessage
                        ),
                        $httpStatus,
                        $faultCode,
                        $faultMessage
                    );
                }

                /*
                 * Un Fault desconocido no debe
                 * clasificarse alegremente como
                 * reintentable.
                 */
                return $this->result(
                    self::RESULT_PERMANENT_ERROR,
                    false,
                    'soap_fault_unknown',
                    $this->faultMessage(
                        'SOAP Fault no clasificado',
                        $faultCode,
                        $faultMessage
                    ),
                    $httpStatus,
                    $faultCode,
                    $faultMessage
                );
            }
        }

        /*
         * ==================================================
         * Respuesta SOAP normal
         * ==================================================
         *
         * Aquí todavía NO afirmamos Correcto,
         * AceptadoConErrores ni Incorrecto.
         *
         * Únicamente permitimos que la respuesta pase al
         * VerifactuResponseProcessor.
         */
        return $this->result(
            self::RESULT_FISCAL_RESPONSE,
            false,
            'fiscal_response',
            'Se ha recibido una respuesta HTTP/SOAP procesable fiscalmente.',
            $httpStatus
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function result(
        string $result,
        bool $retryable,
        string $reason,
        string $message,
        int $httpStatus,
        ?string $faultCode = null,
        ?string $faultMessage = null
    ): array {
        return [
            'result' =>
                $result,

            'retryable' =>
                $retryable,

            'reason' =>
                $reason,

            'message' =>
                $message,

            'http_status' =>
                $httpStatus,

            'is_fiscal_response' =>
                $result
                === self::RESULT_FISCAL_RESPONSE,

            'is_fault' =>
                $faultCode !== null
                || $faultMessage !== null,

            'fault_code' =>
                $faultCode,

            'fault_message' =>
                $faultMessage,
        ];
    }

    private function faultMessage(
        string $prefix,
        ?string $faultCode,
        ?string $faultMessage
    ): string {
        $parts = [
            $prefix,
        ];

        $faultCode =
            trim(
                (string) $faultCode
            );

        $faultMessage =
            trim(
                (string) $faultMessage
            );

        if ($faultCode !== '') {
            $parts[] =
                '['
                . $faultCode
                . ']';
        }

        if ($faultMessage !== '') {
            $parts[] =
                $faultMessage;
        }

        return implode(
            ' ',
            $parts
        );
    }

    private function directChildText(
        DOMElement $parent,
        string $localName
    ): ?string {
        foreach (
            $parent->childNodes
            as $child
        ) {
            if (
                $child instanceof DOMElement
                && $child->localName
                    === $localName
            ) {
                $value =
                    trim(
                        (string) $child->textContent
                    );

                return $value !== ''
                    ? $value
                    : null;
            }
        }

        return null;
    }
}
