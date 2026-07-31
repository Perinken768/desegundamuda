<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerCookie
{
    public const NAME = 'dsm_customer_session';

    public static function set(
        string $token,
        int $expiresTimestamp
    ): void {
        setcookie(
            self::NAME,
            $token,
            [
                'expires'  => $expiresTimestamp,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[self::NAME] = $token;
    }

    public static function get(): ?string
    {
        $token = $_COOKIE[self::NAME] ?? null;

        if (!is_string($token)) {
            return null;
        }

        $token = trim($token);

        return SessionToken::isValidFormat($token)
            ? $token
            : null;
    }

    public static function clear(): void
    {
        setcookie(
            self::NAME,
            '',
            [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_COOKIE[self::NAME]);
    }

    private function __construct()
    {
    }
}