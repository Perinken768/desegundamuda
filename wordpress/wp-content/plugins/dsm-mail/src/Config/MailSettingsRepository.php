<?php

declare(strict_types=1);

namespace DSM\Mail\Config;

use DSM\Mail\Security\SecretCipher;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class MailSettingsRepository
{
    private const OPTION_NAME = 'dsm_mail_settings';

    public function __construct(
        private readonly SecretCipher $cipher
    ) {
    }

    public function get(): MailSettings
    {
        $stored = get_option(
            self::OPTION_NAME,
            []
        );

        if (!is_array($stored)) {
            $stored = [];
        }

        return new MailSettings(
            (bool) ($stored['enabled'] ?? false),
            trim((string) ($stored['host'] ?? '')),
            (int) ($stored['port'] ?? 587),
            sanitize_key(
                (string) ($stored['encryption'] ?? 'tls')
            ),
            (bool) ($stored['authentication_enabled'] ?? true),
            trim((string) ($stored['username'] ?? '')),
            $this->cipher->decrypt(
                (string) ($stored['password'] ?? '')
            ),
            strtolower(
                trim((string) ($stored['from_email'] ?? ''))
            ),
            trim(
                (string) (
                    $stored['from_name']
                    ?? get_bloginfo('name')
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function save(array $input): MailSettings
    {
        $current = get_option(
            self::OPTION_NAME,
            []
        );

        if (!is_array($current)) {
            $current = [];
        }

        $password = isset($input['password'])
            ? (string) $input['password']
            : '';

        $encryptedPassword = $password !== ''
            ? $this->cipher->encrypt($password)
            : (string) ($current['password'] ?? '');

        $settings = new MailSettings(
            !empty($input['enabled']),
            trim((string) ($input['host'] ?? '')),
            (int) ($input['port'] ?? 587),
            sanitize_key(
                (string) ($input['encryption'] ?? 'tls')
            ),
            !empty($input['authentication_enabled']),
            trim((string) ($input['username'] ?? '')),
            $password !== ''
                ? $password
                : $this->cipher->decrypt(
                    $encryptedPassword
                ),
            strtolower(
                trim((string) ($input['from_email'] ?? ''))
            ),
            trim((string) ($input['from_name'] ?? ''))
        );

        $result = update_option(
            self::OPTION_NAME,
            [
                'enabled' => $settings->isEnabled(),
                'host' => $settings->getHost(),
                'port' => $settings->getPort(),
                'encryption' => $settings->getEncryption(),
                'authentication_enabled' =>
                    $settings->isAuthenticationEnabled(),
                'username' => $settings->getUsername(),
                'password' => $encryptedPassword,
                'from_email' => $settings->getFromEmail(),
                'from_name' => $settings->getFromName(),
            ],
            false
        );

        if ($result === false) {
            $existing = get_option(
                self::OPTION_NAME,
                []
            );

            if (!is_array($existing)) {
                throw new RuntimeException(
                    'No se pudo guardar la configuración SMTP.'
                );
            }
        }

        return $settings;
    }

    public function hasStoredPassword(): bool
    {
        $stored = get_option(
            self::OPTION_NAME,
            []
        );

        return is_array($stored)
            && !empty($stored['password']);
    }
}