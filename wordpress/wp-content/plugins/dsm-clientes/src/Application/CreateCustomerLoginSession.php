<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\LoginResult;
use DSM\Clientes\Authentication\SessionToken;
use DSM\Clientes\Customer\Customer;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateCustomerLoginSession
{
    private const SESSION_DURATION =
        2592000; // 30 días.

    public function __construct(
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(
        Customer $customer,
        ?string $ipAddress,
        ?string $userAgent
    ): LoginResult {
        $token =
            SessionToken::generate();

        $tokenHash =
            SessionToken::hash(
                $token
            );

        $session =
            $this->sessionRepository->create(
                $customer->getId(),
                $tokenHash,
                $this->normalizeIpAddress(
                    $ipAddress
                ),
                $this->normalizeUserAgent(
                    $userAgent
                ),
                self::SESSION_DURATION
            );

        return new LoginResult(
            $customer,
            $session,
            $token
        );
    }

    private function normalizeIpAddress(
        ?string $ipAddress
    ): ?string {
        if (
            $ipAddress === null
            || $ipAddress === ''
        ) {
            return null;
        }

        return filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP
        ) !== false
            ? $ipAddress
            : null;
    }

    private function normalizeUserAgent(
        ?string $userAgent
    ): ?string {
        if ($userAgent === null) {
            return null;
        }

        $userAgent =
            trim(
                $userAgent
            );

        if ($userAgent === '') {
            return null;
        }

        return mb_substr(
            $userAgent,
            0,
            500
        );
    }
}
