<?php

declare(strict_types=1);

namespace DSM\Mail\Mail;

use DSM\Core\Contracts\MailerInterface;

if (!defined('ABSPATH')) {
    exit;
}

final class WordPressMailer implements MailerInterface
{
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
        return wp_mail(
            $to,
            $subject,
            $message,
            $headers,
            $attachments
        );
    }
}