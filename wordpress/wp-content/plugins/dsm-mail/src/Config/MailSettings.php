<?php

declare(strict_types=1);

namespace DSM\Mail\Config;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class MailSettings
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption,
        private readonly bool $authenticationEnabled,
        private readonly string $username,
        private readonly string $password,
        private readonly string $fromEmail,
        private readonly string $fromName
    ) {
        $this->validate();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getEncryption(): string
    {
        return $this->encryption;
    }

    public function isAuthenticationEnabled(): bool
    {
        return $this->authenticationEnabled;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function isComplete(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (
            $this->host === ''
            || $this->fromEmail === ''
            || !is_email($this->fromEmail)
        ) {
            return false;
        }

        if (
            $this->authenticationEnabled
            && (
                $this->username === ''
                || $this->password === ''
            )
        ) {
            return false;
        }

        return true;
    }

    private function validate(): void
    {
        if ($this->port < 1 || $this->port > 65535) {
            throw new RuntimeException(
                'El puerto SMTP no es válido.'
            );
        }

        if (
            !in_array(
                $this->encryption,
                ['none', 'tls', 'ssl'],
                true
            )
        ) {
            throw new RuntimeException(
                'El cifrado SMTP no es válido.'
            );
        }

        if (
            $this->fromEmail !== ''
            && !is_email($this->fromEmail)
        ) {
            throw new RuntimeException(
                'El correo del remitente no es válido.'
            );
        }
    }
}