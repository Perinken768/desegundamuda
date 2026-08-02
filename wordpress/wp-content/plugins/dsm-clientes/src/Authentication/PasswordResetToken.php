<?php

declare(strict_types=1);

namespace DSM\Clientes\Authentication;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PasswordResetToken
{
    public static function generate(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'No se pudo generar el token de recuperación.',
                0,
                $exception
            );
        }
    }

    public static function hash(string $token): string
    {
        return hash('sha256', trim($token));
    }

    public static function isValidFormat(string $token): bool
    {
        return preg_match(
            '/^[a-f0-9]{64}$/',
            trim($token)
        ) === 1;
    }

    private function __construct()
    {
    }
}