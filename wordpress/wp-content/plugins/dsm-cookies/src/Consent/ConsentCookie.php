<?php

declare(strict_types=1);

namespace DSM\Cookies\Consent;

if (!defined('ABSPATH')) {
    exit;
}

final class ConsentCookie
{
    public const NAME =
        'dsm_cookie_consent';

    private const LIFETIME =
        180 * DAY_IN_SECONDS;

    public static function read(): ?Consent
    {
        if (
            !isset(
                $_COOKIE[
                    self::NAME
                ]
            )
        ) {
            return null;
        }

        $raw =
            trim(
                (string) wp_unslash(
                    $_COOKIE[
                        self::NAME
                    ]
                )
            );

        if ($raw === '') {
            return null;
        }

        return self::decode(
            $raw
        );
    }

    public static function write(
        Consent $consent
    ): void {
        $value =
            self::encode(
                $consent
            );

        $expires =
            time()
            + self::LIFETIME;

        $path =
            defined('COOKIEPATH')
            && COOKIEPATH !== ''
                ? COOKIEPATH
                : '/';

        $domain =
            defined('COOKIE_DOMAIN')
                ? (string) COOKIE_DOMAIN
                : '';

        setcookie(
            self::NAME,
            $value,
            [
                'expires' =>
                    $expires,

                'path' =>
                    $path,

                'domain' =>
                    $domain,

                'secure' =>
                    is_ssl(),

                'httponly' =>
                    true,

                'samesite' =>
                    'Lax',
            ]
        );

        /*
         * Permitimos que la misma petición pueda consultar
         * inmediatamente el nuevo consentimiento.
         */
        $_COOKIE[
            self::NAME
        ] =
            $value;
    }

    public static function delete(): void
    {
        $path =
            defined('COOKIEPATH')
            && COOKIEPATH !== ''
                ? COOKIEPATH
                : '/';

        $domain =
            defined('COOKIE_DOMAIN')
                ? (string) COOKIE_DOMAIN
                : '';

        setcookie(
            self::NAME,
            '',
            [
                'expires' =>
                    time() - HOUR_IN_SECONDS,

                'path' =>
                    $path,

                'domain' =>
                    $domain,

                'secure' =>
                    is_ssl(),

                'httponly' =>
                    true,

                'samesite' =>
                    'Lax',
            ]
        );

        unset(
            $_COOKIE[
                self::NAME
            ]
        );
    }

    private static function encode(
        Consent $consent
    ): string {
        $json =
            wp_json_encode(
                $consent->toArray(),
                JSON_UNESCAPED_SLASHES
            );

        if (!is_string($json)) {
            return '';
        }

        $payload =
            self::base64UrlEncode(
                $json
            );

        $signature =
            hash_hmac(
                'sha256',
                $payload,
                self::getSigningKey()
            );

        return $payload
            . '.'
            . $signature;
    }

    private static function decode(
        string $value
    ): ?Consent {
        $parts =
            explode(
                '.',
                $value,
                2
            );

        if (count($parts) !== 2) {
            return null;
        }

        [
            $payload,
            $signature,
        ] = $parts;

        $expectedSignature =
            hash_hmac(
                'sha256',
                $payload,
                self::getSigningKey()
            );

        if (
            !hash_equals(
                $expectedSignature,
                $signature
            )
        ) {
            return null;
        }

        $json =
            self::base64UrlDecode(
                $payload
            );

        if ($json === null) {
            return null;
        }

        $data =
            json_decode(
                $json,
                true
            );

        if (!is_array($data)) {
            return null;
        }

        if (
            (int) (
                $data[
                    'version'
                ]
                ?? 0
            ) !== Consent::VERSION
        ) {
            return null;
        }

        return Consent::fromArray(
            $data
        );
    }

    private static function getSigningKey(): string
    {
        return wp_salt(
            'auth'
        );
    }

    private static function base64UrlEncode(
        string $value
    ): string {
        return rtrim(
            strtr(
                base64_encode(
                    $value
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private static function base64UrlDecode(
        string $value
    ): ?string {
        $padding =
            strlen($value)
            % 4;

        if ($padding > 0) {
            $value .=
                str_repeat(
                    '=',
                    4 - $padding
                );
        }

        $decoded =
            base64_decode(
                strtr(
                    $value,
                    '-_',
                    '+/'
                ),
                true
            );

        return is_string($decoded)
            ? $decoded
            : null;
    }

    private function __construct()
    {
    }
}
