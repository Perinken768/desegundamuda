<?php

declare(strict_types=1);

namespace DSM\Core\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

interface MailerInterface
{
    /**
     * Envía un mensaje de correo.
     *
     * @param string|string[] $to
     * @param string|string[] $headers
     * @param string[] $attachments
     */
    public function send(
        string|array $to,
        string $subject,
        string $message,
        string|array $headers = [],
        array $attachments = []
    ): bool;
}