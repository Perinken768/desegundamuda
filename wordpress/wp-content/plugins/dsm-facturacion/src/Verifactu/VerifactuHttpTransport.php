<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuHttpTransport
{
    private const CONNECT_TIMEOUT_SECONDS =
        15;

    private const REQUEST_TIMEOUT_SECONDS =
        60;

    public function assertReady(): void
    {
        if (
            !extension_loaded(
                'curl'
            )
        ) {
            throw new RuntimeException(
                'La extensión PHP cURL no está disponible.'
            );
        }

        if (
            !VerifactuSettings::isEnabled()
        ) {
            throw new RuntimeException(
                'VERI*FACTU está desactivado. El transporte permanece bloqueado.'
            );
        }

        VerifactuCertificateConfig::assertConfigured();

        $endpoint =
            VerifactuSettings::getEndpoint();

        if (
            !str_starts_with(
                $endpoint,
                'https://'
            )
        ) {
            throw new RuntimeException(
                'El endpoint VERI*FACTU debe utilizar HTTPS.'
            );
        }
    }

    /**
     * Devuelve únicamente información segura.
     *
     * Nunca devuelve contraseñas.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array
    {
        $settings =
            VerifactuSettings::get();

        $certificate =
            VerifactuCertificateConfig::get();

        return [
            'enabled' =>
                VerifactuSettings::isEnabled(),

            'environment' =>
                (string) (
                    $settings[
                        'environment'
                    ]
                    ?? ''
                ),

            'certificate_type' =>
                (string) (
                    $settings[
                        'certificate_type'
                    ]
                    ?? ''
                ),

            'certificate_status' =>
                VerifactuCertificateConfig::getSafeStatus(),

            'certificate_format' =>
                $certificate['format'],

            'endpoint' =>
                VerifactuSettings::getEndpoint(
                    $settings
                ),

            'curl_available' =>
                extension_loaded(
                    'curl'
                ),
        ];
    }

    /**
     * Ejecuta el POST SOAP real.
     *
     * Este método NO debe llamarse hasta tener
     * configurado y verificado el certificado.
     *
     * @return array{
     *     http_status: int,
     *     response_xml: string,
     *     duration_ms: int
     * }
     */
    public function send(
        string $endpoint,
        string $requestXml
    ): array {
        $this->assertReady();

        $endpoint =
            trim(
                $endpoint
            );

        $requestXml =
            trim(
                $requestXml
            );

        if ($requestXml === '') {
            throw new RuntimeException(
                'La solicitud SOAP VERI*FACTU está vacía.'
            );
        }

        /*
         * La remisión solamente puede enviarse al endpoint
         * derivado de la configuración actual.
         */
        $expectedEndpoint =
            VerifactuSettings::getEndpoint();

        if (
            !hash_equals(
                $expectedEndpoint,
                $endpoint
            )
        ) {
            throw new RuntimeException(
                'El endpoint de la remisión no coincide con la configuración VERI*FACTU actual.'
            );
        }

        $certificate =
            VerifactuCertificateConfig::get();

        $curl =
            curl_init();

        if ($curl === false) {
            throw new RuntimeException(
                'No se pudo inicializar cURL.'
            );
        }

        $headers = [
            'Content-Type: text/xml; charset=UTF-8',
            'Accept: text/xml',
            'SOAPAction: ""',
            'Content-Length: '
                . strlen(
                    $requestXml
                ),
        ];

        $options = [
            CURLOPT_URL =>
                $endpoint,

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                $requestXml,

            CURLOPT_HTTPHEADER =>
                $headers,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_HEADER =>
                false,

            CURLOPT_CONNECTTIMEOUT =>
                self::CONNECT_TIMEOUT_SECONDS,

            CURLOPT_TIMEOUT =>
                self::REQUEST_TIMEOUT_SECONDS,

            /*
             * Nunca se desactiva la comprobación TLS.
             */
            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2,

            CURLOPT_SSLCERT =>
                $certificate[
                    'certificate_path'
                ],

            CURLOPT_SSLCERTTYPE =>
                $certificate[
                    'format'
                ],
        ];

        if (
            $certificate[
                'certificate_password'
            ] !== ''
        ) {
            $options[
                CURLOPT_KEYPASSWD
            ] =
                $certificate[
                    'certificate_password'
                ];
        }

        /*
         * PEM puede utilizar certificado y clave
         * privada en ficheros separados.
         */
        if (
            $certificate['format']
                === VerifactuCertificateConfig::FORMAT_PEM
            && $certificate[
                'private_key_path'
            ] !== ''
        ) {
            $options[
                CURLOPT_SSLKEY
            ] =
                $certificate[
                    'private_key_path'
                ];

            $options[
                CURLOPT_SSLKEYTYPE
            ] =
                'PEM';

            if (
                $certificate[
                    'private_key_password'
                ] !== ''
            ) {
                $options[
                    CURLOPT_KEYPASSWD
                ] =
                    $certificate[
                        'private_key_password'
                    ];
            }
        }

        if (
            curl_setopt_array(
                $curl,
                $options
            ) === false
        ) {
            curl_close(
                $curl
            );

            throw new RuntimeException(
                'No se pudieron configurar las opciones cURL VERI*FACTU.'
            );
        }

        $startedAt =
            microtime(
                true
            );

        $response =
            curl_exec(
                $curl
            );

        $durationMs =
            (int) round(
                (
                    microtime(
                        true
                    )
                    - $startedAt
                )
                * 1000
            );

        if ($response === false) {
            $errno =
                curl_errno(
                    $curl
                );

            $error =
                curl_error(
                    $curl
                );

            curl_close(
                $curl
            );

            throw new RuntimeException(
                sprintf(
                    'Error de transporte VERI*FACTU cURL %d: %s',
                    $errno,
                    $error
                )
            );
        }

        $httpStatus =
            (int) curl_getinfo(
                $curl,
                CURLINFO_RESPONSE_CODE
            );

        curl_close(
            $curl
        );

        return [
            'http_status' =>
                $httpStatus,

            'response_xml' =>
                (string) $response,

            'duration_ms' =>
                $durationMs,
        ];
    }
}
