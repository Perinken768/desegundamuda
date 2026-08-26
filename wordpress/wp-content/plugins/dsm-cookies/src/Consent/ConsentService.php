<?php

declare(strict_types=1);

namespace DSM\Cookies\Consent;

if (!defined('ABSPATH')) {
    exit;
}

final class ConsentService
{
    public function getCurrent(): ?Consent
    {
        return ConsentCookie::read();
    }

    public function hasConsent(): bool
    {
        return $this->getCurrent()
            !== null;
    }

    public function isAllowed(
        string $category
    ): bool {
        /*
         * Las cookies estrictamente necesarias siempre
         * están permitidas.
         */
        if (
            sanitize_key(
                $category
            ) === 'necessary'
        ) {
            return true;
        }

        $consent =
            $this->getCurrent();

        if ($consent === null) {
            return false;
        }

        return $consent->allows(
            $category
        );
    }

    public function acceptAll(): Consent
    {
        $consent =
            Consent::acceptAll();

        ConsentCookie::write(
            $consent
        );

        return $consent;
    }

    public function rejectOptional(): Consent
    {
        $consent =
            Consent::rejectOptional();

        ConsentCookie::write(
            $consent
        );

        return $consent;
    }

    public function savePreferences(
        bool $preferences,
        bool $analytics,
        bool $marketing
    ): Consent {
        $consent =
            new Consent(
                preferences:
                    $preferences,

                analytics:
                    $analytics,

                marketing:
                    $marketing,

                updatedAt:
                    time()
            );

        ConsentCookie::write(
            $consent
        );

        return $consent;
    }
}
