<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuCertificateConfig
{
    public const FORMAT_P12 =
        'P12';

    public const FORMAT_PEM =
        'PEM';

    /**
     * @return array{
     *     certificate_path: string,
     *     certificate_password: string,
     *     private_key_path: string,
     *     private_key_password: string,
     *     format: string
     * }
     */
    public static function get(): array
    {
        $certificatePath =
            self::constantValue(
                'DSM_VERIFACTU_CERT_PATH'
            );

        $certificatePassword =
            self::constantValue(
                'DSM_VERIFACTU_CERT_PASSWORD'
            );

        $privateKeyPath =
            self::constantValue(
                'DSM_VERIFACTU_KEY_PATH'
            );

        $privateKeyPassword =
            self::constantValue(
                'DSM_VERIFACTU_KEY_PASSWORD'
            );

        return [
            'certificate_path' =>
                $certificatePath,

            'certificate_password' =>
                $certificatePassword,

            'private_key_path' =>
                $privateKeyPath,

            'private_key_password' =>
                $privateKeyPassword,

            'format' =>
                self::detectFormat(
                    $certificatePath
                ),
        ];
    }

    public static function isConfigured(): bool
    {
        try {
            self::assertConfigured();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public static function assertConfigured(): void
    {
        if (
            !extension_loaded(
                'openssl'
            )
        ) {
            throw new RuntimeException(
                'La extensión PHP OpenSSL no está disponible.'
            );
        }

        $config =
            self::get();

        self::assertReadableFile(
            $config['certificate_path'],
            'certificado VERI*FACTU'
        );

        if (
            $config['format']
            === self::FORMAT_P12
        ) {
            self::validateP12(
                $config
            );

            return;
        }

        self::validatePem(
            $config
        );
    }

    public static function getSafeStatus(): string
    {
        $config =
            self::get();

        if (
            $config[
                'certificate_path'
            ] === ''
        ) {
            return 'missing_path';
        }

        if (
            !is_file(
                $config[
                    'certificate_path'
                ]
            )
        ) {
            return 'file_missing';
        }

        if (
            !is_readable(
                $config[
                    'certificate_path'
                ]
            )
        ) {
            return 'not_readable';
        }

        if (
            !extension_loaded(
                'openssl'
            )
        ) {
            return 'openssl_missing';
        }

        try {
            self::assertConfigured();

            return 'configured';
        } catch (RuntimeException $e) {
            $message =
                $e->getMessage();

            if (
                str_contains(
                    $message,
                    'contraseña'
                )
                || str_contains(
                    $message,
                    'PKCS#12'
                )
            ) {
                return 'invalid_p12';
            }

            if (
                str_contains(
                    $message,
                    'caducado'
                )
            ) {
                return 'expired';
            }

            if (
                str_contains(
                    $message,
                    'todavía no es válido'
                )
            ) {
                return 'not_yet_valid';
            }

            if (
                str_contains(
                    $message,
                    'clave privada'
                )
            ) {
                return 'private_key_error';
            }

            if (
                str_contains(
                    $message,
                    'no corresponde'
                )
            ) {
                return 'key_mismatch';
            }

            if (
                str_contains(
                    $message,
                    'certificado X.509'
                )
            ) {
                return 'invalid_certificate';
            }

            return 'invalid';
        }
    }

    /**
     * Devuelve únicamente metadatos seguros.
     *
     * Nunca devuelve claves privadas ni contraseñas.
     *
     * @return array<string, mixed>
     */
    public static function inspectCertificate(): array
    {
        $config =
            self::get();

        self::assertConfigured();

        $certificatePem =
            self::extractCertificatePem(
                $config
            );

        $certificate =
            openssl_x509_read(
                $certificatePem
            );

        if ($certificate === false) {
            throw new RuntimeException(
                'No se pudo leer el certificado X.509 VERI*FACTU.'
            );
        }

        $parsed =
            openssl_x509_parse(
                $certificate,
                false
            );

        if (!is_array($parsed)) {
            throw new RuntimeException(
                'No se pudieron analizar los metadatos del certificado VERI*FACTU.'
            );
        }

        return [
            'format' =>
                $config['format'],

            'filename' =>
                basename(
                    $config[
                        'certificate_path'
                    ]
                ),

            'subject' =>
                self::formatDistinguishedName(
                    $parsed['subject']
                    ?? []
                ),

            'issuer' =>
                self::formatDistinguishedName(
                    $parsed['issuer']
                    ?? []
                ),

            'serial_number' =>
                (string) (
                    $parsed[
                        'serialNumberHex'
                    ]
                    ?? $parsed[
                        'serialNumber'
                    ]
                    ?? ''
                ),

            'valid_from' =>
                isset(
                    $parsed[
                        'validFrom_time_t'
                    ]
                )
                    ? gmdate(
                        'Y-m-d H:i:s',
                        (int) $parsed[
                            'validFrom_time_t'
                        ]
                    )
                    : '',

            'valid_to' =>
                isset(
                    $parsed[
                        'validTo_time_t'
                    ]
                )
                    ? gmdate(
                        'Y-m-d H:i:s',
                        (int) $parsed[
                            'validTo_time_t'
                        ]
                    )
                    : '',

            'days_remaining' =>
                self::calculateDaysRemaining(
                    $parsed
                ),
        ];
    }

    /**
     * @param array{
     *     certificate_path: string,
     *     certificate_password: string,
     *     private_key_path: string,
     *     private_key_password: string,
     *     format: string
     * } $config
     */
    private static function validateP12(
        array $config
    ): void {
        $contents =
            file_get_contents(
                $config[
                    'certificate_path'
                ]
            );

        if ($contents === false) {
            throw new RuntimeException(
                'No se pudo leer el fichero PKCS#12 VERI*FACTU.'
            );
        }

        $certificates = [];

        self::clearOpenSslErrors();

        $read =
            openssl_pkcs12_read(
                $contents,
                $certificates,
                $config[
                    'certificate_password'
                ]
            );

        self::clearOpenSslErrors();

        if (!$read) {
            throw new RuntimeException(
                'No se pudo abrir el PKCS#12 VERI*FACTU. El fichero o la contraseña no son válidos.'
            );
        }

        $certificatePem =
            trim(
                (string) (
                    $certificates['cert']
                    ?? ''
                )
            );

        $privateKeyPem =
            trim(
                (string) (
                    $certificates['pkey']
                    ?? ''
                )
            );

        if ($certificatePem === '') {
            throw new RuntimeException(
                'El PKCS#12 VERI*FACTU no contiene un certificado X.509.'
            );
        }

        if ($privateKeyPem === '') {
            throw new RuntimeException(
                'El PKCS#12 VERI*FACTU no contiene clave privada.'
            );
        }

        self::validateCertificateAndKey(
            $certificatePem,
            $privateKeyPem,
            ''
        );
    }

    /**
     * @param array{
     *     certificate_path: string,
     *     certificate_password: string,
     *     private_key_path: string,
     *     private_key_password: string,
     *     format: string
     * } $config
     */
    private static function validatePem(
        array $config
    ): void {
        $certificatePem =
            file_get_contents(
                $config[
                    'certificate_path'
                ]
            );

        if ($certificatePem === false) {
            throw new RuntimeException(
                'No se pudo leer el certificado PEM VERI*FACTU.'
            );
        }

        $privateKeyPem =
            $certificatePem;

        $privateKeyPassword =
            $config[
                'certificate_password'
            ];

        if (
            $config[
                'private_key_path'
            ] !== ''
        ) {
            self::assertReadableFile(
                $config[
                    'private_key_path'
                ],
                'clave privada VERI*FACTU'
            );

            $privateKeyPem =
                file_get_contents(
                    $config[
                        'private_key_path'
                    ]
                );

            if ($privateKeyPem === false) {
                throw new RuntimeException(
                    'No se pudo leer la clave privada PEM VERI*FACTU.'
                );
            }

            $privateKeyPassword =
                $config[
                    'private_key_password'
                ];
        }

        self::validateCertificateAndKey(
            $certificatePem,
            $privateKeyPem,
            $privateKeyPassword
        );
    }

    private static function validateCertificateAndKey(
        string $certificatePem,
        string $privateKeyPem,
        string $privateKeyPassword
    ): void {
        self::clearOpenSslErrors();

        $certificate =
            openssl_x509_read(
                $certificatePem
            );

        self::clearOpenSslErrors();

        if ($certificate === false) {
            throw new RuntimeException(
                'El certificado X.509 VERI*FACTU no es válido.'
            );
        }

        self::clearOpenSslErrors();

        $privateKey =
            openssl_pkey_get_private(
                $privateKeyPem,
                $privateKeyPassword
            );

        self::clearOpenSslErrors();

        if ($privateKey === false) {
            throw new RuntimeException(
                'La clave privada VERI*FACTU no es válida o su contraseña es incorrecta.'
            );
        }

        if (
            !openssl_x509_check_private_key(
                $certificate,
                $privateKey
            )
        ) {
            throw new RuntimeException(
                'La clave privada VERI*FACTU no corresponde al certificado.'
            );
        }

        $parsed =
            openssl_x509_parse(
                $certificate,
                false
            );

        if (!is_array($parsed)) {
            throw new RuntimeException(
                'No se pudieron analizar los metadatos del certificado X.509 VERI*FACTU.'
            );
        }

        self::assertCertificateDates(
            $parsed
        );
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function assertCertificateDates(
        array $parsed
    ): void {
        $validFrom =
            isset(
                $parsed[
                    'validFrom_time_t'
                ]
            )
                ? (int) $parsed[
                    'validFrom_time_t'
                ]
                : 0;

        $validTo =
            isset(
                $parsed[
                    'validTo_time_t'
                ]
            )
                ? (int) $parsed[
                    'validTo_time_t'
                ]
                : 0;

        if (
            $validFrom <= 0
            || $validTo <= 0
        ) {
            throw new RuntimeException(
                'El certificado X.509 VERI*FACTU no contiene un periodo de validez reconocible.'
            );
        }

        $now =
            time();

        if ($now < $validFrom) {
            throw new RuntimeException(
                'El certificado VERI*FACTU todavía no es válido.'
            );
        }

        if ($now > $validTo) {
            throw new RuntimeException(
                'El certificado VERI*FACTU está caducado.'
            );
        }
    }

    /**
     * @param array{
     *     certificate_path: string,
     *     certificate_password: string,
     *     private_key_path: string,
     *     private_key_password: string,
     *     format: string
     * } $config
     */
    private static function extractCertificatePem(
        array $config
    ): string {
        if (
            $config['format']
            === self::FORMAT_PEM
        ) {
            $certificatePem =
                file_get_contents(
                    $config[
                        'certificate_path'
                    ]
                );

            if ($certificatePem === false) {
                throw new RuntimeException(
                    'No se pudo leer el certificado PEM VERI*FACTU.'
                );
            }

            return $certificatePem;
        }

        $contents =
            file_get_contents(
                $config[
                    'certificate_path'
                ]
            );

        if ($contents === false) {
            throw new RuntimeException(
                'No se pudo leer el fichero PKCS#12 VERI*FACTU.'
            );
        }

        $certificates = [];

        if (
            !openssl_pkcs12_read(
                $contents,
                $certificates,
                $config[
                    'certificate_password'
                ]
            )
        ) {
            throw new RuntimeException(
                'No se pudo abrir el PKCS#12 VERI*FACTU.'
            );
        }

        $certificatePem =
            trim(
                (string) (
                    $certificates['cert']
                    ?? ''
                )
            );

        if ($certificatePem === '') {
            throw new RuntimeException(
                'El PKCS#12 VERI*FACTU no contiene certificado.'
            );
        }

        return $certificatePem;
    }

    private static function assertReadableFile(
        string $path,
        string $description
    ): void {
        if ($path === '') {
            throw new RuntimeException(
                sprintf(
                    'No está configurada la ruta de %s.',
                    $description
                )
            );
        }

        if (!is_file($path)) {
            throw new RuntimeException(
                sprintf(
                    'No existe el %s: %s',
                    $description,
                    $path
                )
            );
        }

        if (!is_readable($path)) {
            throw new RuntimeException(
                sprintf(
                    'El %s no es legible por PHP: %s',
                    $description,
                    $path
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function calculateDaysRemaining(
        array $parsed
    ): ?int {
        if (
            !isset(
                $parsed[
                    'validTo_time_t'
                ]
            )
        ) {
            return null;
        }

        $seconds =
            (int) $parsed[
                'validTo_time_t'
            ]
            - time();

        return (int) floor(
            $seconds / DAY_IN_SECONDS
        );
    }

    /**
     * @param mixed $value
     */
    private static function formatDistinguishedName(
        mixed $value
    ): string {
        if (!is_array($value)) {
            return '';
        }

        $parts = [];

        foreach ($value as $key => $item) {
            if (
                is_scalar($item)
                && trim(
                    (string) $item
                ) !== ''
            ) {
                $parts[] =
                    (string) $key
                    . '='
                    . trim(
                        (string) $item
                    );
            }
        }

        return implode(
            ', ',
            $parts
        );
    }

    private static function clearOpenSslErrors(): void
    {
        while (
            openssl_error_string()
            !== false
        ) {
        }
    }

    private static function detectFormat(
        string $path
    ): string {
        $extension =
            strtolower(
                pathinfo(
                    $path,
                    PATHINFO_EXTENSION
                )
            );

        if (
            in_array(
                $extension,
                [
                    'p12',
                    'pfx',
                ],
                true
            )
        ) {
            return self::FORMAT_P12;
        }

        return self::FORMAT_PEM;
    }

    private static function constantValue(
        string $name
    ): string {
        if (!defined($name)) {
            return '';
        }

        $value =
            constant(
                $name
            );

        if (!is_scalar($value)) {
            return '';
        }

        return trim(
            (string) $value
        );
    }

    private function __construct()
    {
    }
}
