<?php

declare(strict_types=1);

namespace DSM\Mail\Mail;

use DSM\Core\Contracts\MailerInterface;
use RuntimeException;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class WordPressMailer implements MailerInterface
{
    private ?WP_Error $lastError = null;

    public function __construct()
    {
        add_action(
            'wp_mail_failed',
            [$this, 'captureError']
        );
    }

    /**
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
    ): bool {
        $this->lastError = null;

        $sent = wp_mail(
            $to,
            $subject,
            $message,
            $headers,
            $attachments
        );

        if (!$sent) {
            $message = $this->lastError?->get_error_message()
                ?? 'WordPress no pudo completar el envío del correo.';

            throw new RuntimeException($message);
        }

        return true;
    }

    public function captureError(WP_Error $error): void
    {
        $this->lastError = $error;
    }
}