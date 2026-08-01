<?php

declare(strict_types=1);

namespace DSM\Core\Mail;

use DSM\Core\Contracts\MailerInterface;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class MailerRegistry
{
    private static ?MailerInterface $mailer = null;

    public static function set(
        MailerInterface $mailer
    ): void {
        self::$mailer = $mailer;
    }

    public static function has(): bool
    {
        return self::$mailer !== null;
    }

    public static function get(): MailerInterface
    {
        if (self::$mailer === null) {
            throw new RuntimeException(
                'No hay ningún servicio de correo registrado en DSM Core.'
            );
        }

        return self::$mailer;
    }

    public static function clear(): void
    {
        self::$mailer = null;
    }

    private function __construct()
    {
    }
}