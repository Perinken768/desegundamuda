<?php

declare(strict_types=1);

namespace DSM\Clientes\Impersonation;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerImpersonationCookie
{
    public const NAME = 'dsm_customer_impersonation';

    /**
     * @param array{
     *     admin_user_id: int,
     *     customer_id: int,
     *     session_id: int,
     *     expires_at: int,
     *     return_url: string
     * } $payload
     */
    public static function set(array $payload): void
    {
        $encodedPayload = self::encodePayload($payload);
        $signature = hash_hmac(
            'sha256',
            $encodedPayload,
            wp_salt('auth')
        );

        $value = $encodedPayload . '.' . $signature;

        setcookie(
            self::NAME,
            $value,
            [
                'expires' => $payload['expires_at'],
                'path' => COOKIEPATH ?: '/',
                'domain' => COOKIE_DOMAIN ?: '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[self::NAME] = $value;
    }

    /**
     * @return array{
     *     admin_user_id: int,
     *     customer_id: int,
     *     session_id: int,
     *     expires_at: int,
     *     return_url: string
     * }|null
     */
    public static function get(): ?array
    {
        $value = $_COOKIE[self::NAME] ?? null;

        if (!is_string($value) || $value === '') {
            return null;
        }

        $parts = explode('.', $value, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            $encodedPayload,
            wp_salt('auth')
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $json = self::base64UrlDecode($encodedPayload);

        if ($json === null) {
            return null;
        }

        $payload = json_decode($json, true);

        if (!is_array($payload)) {
            return null;
        }

        $adminUserId = (int) ($payload['admin_user_id'] ?? 0);
        $customerId = (int) ($payload['customer_id'] ?? 0);
        $sessionId = (int) ($payload['session_id'] ?? 0);
        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        $returnUrl = (string) ($payload['return_url'] ?? '');

        if (
            $adminUserId <= 0
            || $customerId <= 0
            || $sessionId <= 0
            || $expiresAt <= time()
        ) {
            return null;
        }

        return [
            'admin_user_id' => $adminUserId,
            'customer_id' => $customerId,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt,
            'return_url' => wp_validate_redirect(
                $returnUrl,
                admin_url('admin.php?page=dsm-clientes')
            ),
        ];
    }

    public static function isActive(): bool
    {
        $payload = self::get();

        if ($payload === null) {
            return false;
        }

        return is_user_logged_in()
            && current_user_can('manage_options')
            && get_current_user_id()
                === $payload['admin_user_id'];
    }

    public static function clear(): void
    {
        setcookie(
            self::NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => COOKIEPATH ?: '/',
                'domain' => COOKIE_DOMAIN ?: '',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_COOKIE[self::NAME]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function encodePayload(array $payload): string
    {
        $json = wp_json_encode($payload);

        if (!is_string($json)) {
            return '';
        }

        return rtrim(
            strtr(
                base64_encode($json),
                '+/',
                '-_'
            ),
            '='
        );
    }

    private static function base64UrlDecode(
        string $value
    ): ?string {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(
            strtr($value, '-_', '+/'),
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