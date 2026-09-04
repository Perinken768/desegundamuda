<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSettings
{
    public const OPTION_NAME =
        'dsm_facturacion_verifactu_settings';

    public const ENVIRONMENT_TEST =
        'test';

    public const ENVIRONMENT_PRODUCTION =
        'production';

    public const CERTIFICATE_STANDARD =
        'standard';

    public const CERTIFICATE_SEAL =
        'seal';

    public const DEFAULT_SYSTEM_ID =
        'D1';

    private const ENDPOINT_TEST_STANDARD =
        'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';

    private const ENDPOINT_TEST_SEAL =
        'https://prewww10.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';

    private const ENDPOINT_PRODUCTION_STANDARD =
        'https://www1.agenciatributaria.gob.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';

    private const ENDPOINT_PRODUCTION_SEAL =
        'https://www10.agenciatributaria.gob.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';

    private const WSDL_URL =
        'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tikeV1.0/cont/ws/SistemaFacturacion.wsdl';

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored =
            get_option(
                self::OPTION_NAME,
                []
            );

        $stored =
            is_array($stored)
                ? $stored
                : [];

        $settings =
            array_merge(
                self::defaults(),
                $stored
            );

        $settings['system_id'] =
            self::normalizeSystemId(
                (string) (
                    $settings['system_id']
                    ?? ''
                )
            );

        $settings['incidence_active'] =
            (int) (
                $settings[
                    'incidence_active'
                ]
                ?? 0
            ) === 1
                ? 1
                : 0;

        $settings['incidence_started_at'] =
            self::nullableString(
                $settings[
                    'incidence_started_at'
                ]
                ?? null
            );

        $settings['incidence_reason'] =
            self::nullableString(
                $settings[
                    'incidence_reason'
                ]
                ?? null
            );

        if (
            trim(
                (string) $settings[
                    'installation_id'
                ]
            ) === ''
        ) {
            $settings[
                'installation_id'
            ] =
                wp_generate_uuid4();

            self::save(
                $settings
            );
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' =>
                0,

            'environment' =>
                self::ENVIRONMENT_TEST,

            'certificate_type' =>
                self::CERTIFICATE_STANDARD,

            'installation_id' =>
                '',

            'system_name' =>
                'DeSegundaMuda',

            'system_id' =>
                self::DEFAULT_SYSTEM_ID,

            'system_version' =>
                defined(
                    'DSM_FACTURACION_VERSION'
                )
                    ? DSM_FACTURACION_VERSION
                    : '0.1.0',

            /*
             * Estado global de incidencia VERI*FACTU.
             */
            'incidence_active' =>
                0,

            'incidence_started_at' =>
                null,

            'incidence_reason' =>
                null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(
        array $data
    ): void {
        $settings =
            array_merge(
                self::defaults(),
                $data
            );

        $settings['system_id'] =
            self::normalizeSystemId(
                (string) (
                    $settings['system_id']
                    ?? ''
                )
            );

        $settings['incidence_active'] =
            (int) (
                $settings[
                    'incidence_active'
                ]
                ?? 0
            ) === 1
                ? 1
                : 0;

        $settings['incidence_started_at'] =
            self::nullableString(
                $settings[
                    'incidence_started_at'
                ]
                ?? null
            );

        $settings['incidence_reason'] =
            self::nullableString(
                $settings[
                    'incidence_reason'
                ]
                ?? null
            );

        update_option(
            self::OPTION_NAME,
            $settings,
            false
        );
    }

    public static function getEndpoint(
        ?array $settings = null
    ): string {
        $settings =
            $settings
            ?? self::get();

        $environment =
            (string) (
                $settings[
                    'environment'
                ]
                ?? self::ENVIRONMENT_TEST
            );

        $certificateType =
            (string) (
                $settings[
                    'certificate_type'
                ]
                ?? self::CERTIFICATE_STANDARD
            );

        if (
            $environment
            === self::ENVIRONMENT_PRODUCTION
        ) {
            return $certificateType
                === self::CERTIFICATE_SEAL
                    ? self::ENDPOINT_PRODUCTION_SEAL
                    : self::ENDPOINT_PRODUCTION_STANDARD;
        }

        return $certificateType
            === self::CERTIFICATE_SEAL
                ? self::ENDPOINT_TEST_SEAL
                : self::ENDPOINT_TEST_STANDARD;
    }

    public static function getWsdlUrl(): string
    {
        return self::WSDL_URL;
    }

    public static function isEnabled(): bool
    {
        $settings =
            self::get();

        return (int) $settings[
            'enabled'
        ] === 1;
    }

    public static function isProduction(): bool
    {
        $settings =
            self::get();

        return (
            (string) $settings[
                'environment'
            ]
        ) === self::ENVIRONMENT_PRODUCTION;
    }

    public static function hasCertificateConfigured(): bool
    {
        return VerifactuCertificateConfig::isConfigured();
    }

    public static function getConfigurationStatus(): string
    {
        if (!self::isEnabled()) {
            return 'disabled';
        }

        if (
            !self::hasCertificateConfigured()
        ) {
            return 'certificate_missing';
        }

        return 'ready';
    }

    /**
     * Indica si DSM está actualmente en situación
     * global de incidencia VERI*FACTU.
     */
    public static function isIncidenceActive(): bool
    {
        $settings =
            self::get();

        return (int) (
            $settings[
                'incidence_active'
            ]
            ?? 0
        ) === 1;
    }

    /**
     * @return array{
     *     active: bool,
     *     started_at: string|null,
     *     reason: string|null
     * }
     */
    public static function getIncidenceState(): array
    {
        $settings =
            self::get();

        return [
            'active' =>
                (int) (
                    $settings[
                        'incidence_active'
                    ]
                    ?? 0
                ) === 1,

            'started_at' =>
                self::nullableString(
                    $settings[
                        'incidence_started_at'
                    ]
                    ?? null
                ),

            'reason' =>
                self::nullableString(
                    $settings[
                        'incidence_reason'
                    ]
                    ?? null
                ),
        ];
    }

    /**
     * Activa el modo global de incidencia.
     */
    public static function startIncidence(
        string $reason
    ): void {
        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new RuntimeException(
                'Debe indicar el motivo de la incidencia VERI*FACTU.'
            );
        }

        if (
            function_exists(
                'mb_substr'
            )
        ) {
            $reason =
                mb_substr(
                    $reason,
                    0,
                    500
                );
        } else {
            $reason =
                substr(
                    $reason,
                    0,
                    500
                );
        }

        $settings =
            self::get();

        $settings[
            'incidence_active'
        ] =
            1;

        /*
         * Si ya estaba activa, conservamos el instante
         * original de inicio.
         */
        if (
            trim(
                (string) (
                    $settings[
                        'incidence_started_at'
                    ]
                    ?? ''
                )
            ) === ''
        ) {
            $settings[
                'incidence_started_at'
            ] =
                gmdate(
                    'Y-m-d H:i:s'
                );
        }

        $settings[
            'incidence_reason'
        ] =
            $reason;

        self::save(
            $settings
        );
    }

    /**
     * Desactiva el modo global de incidencia.
     */
    public static function endIncidence(): void
    {
        $settings =
            self::get();

        $settings[
            'incidence_active'
        ] =
            0;

        $settings[
            'incidence_started_at'
        ] =
            null;

        $settings[
            'incidence_reason'
        ] =
            null;

        self::save(
            $settings
        );
    }

    private static function normalizeSystemId(
        string $systemId
    ): string {
        $systemId =
            strtoupper(
                trim(
                    $systemId
                )
            );

        $systemId =
            preg_replace(
                '/[^A-Z0-9]/',
                '',
                $systemId
            )
            ?? '';

        if (
            $systemId === ''
            || strlen($systemId) > 2
        ) {
            return self::DEFAULT_SYSTEM_ID;
        }

        return $systemId;
    }

    private static function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function __construct()
    {
    }
}
