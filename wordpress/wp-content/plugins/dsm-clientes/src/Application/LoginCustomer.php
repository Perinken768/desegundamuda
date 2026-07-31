<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\LoginResult;
use DSM\Clientes\Authentication\SessionToken;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class LoginCustomer
{
    private const SESSION_DURATION = 2592000; // 30 días.

    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(
        string $email,
        string $password,
        ?string $ipAddress,
        ?string $userAgent
    ): LoginResult {
        $email = strtolower(trim($email));

        if (!is_email($email)) {
            throw new RuntimeException(
                'Las credenciales no son válidas.'
            );
        }

        $credentials = $this->customerRepository
            ->findCredentialsByEmail($email);

        /*
         * Mismo mensaje si no existe o la contraseña es incorrecta.
         * Evita revelar qué correos están registrados.
         */
        if (
            $credentials === null
            || !wp_check_password(
                $password,
                $credentials['password_hash']
            )
        ) {
            throw new RuntimeException(
                'Las credenciales no son válidas.'
            );
        }

        if ($credentials['status'] === CustomerStatus::BLOCKED) {
            throw new RuntimeException(
                'La cuenta está bloqueada.'
            );
        }

        if ($credentials['status'] === CustomerStatus::SUSPENDED) {
            throw new RuntimeException(
                'La cuenta está suspendida.'
            );
        }

        /*
         * Permitimos pending de momento porque todavía no hemos
         * implementado la verificación de correo.
         */
        $customer = $this->customerRepository->findById(
            $credentials['id']
        );

        if ($customer === null) {
            throw new RuntimeException(
                'No se pudo recuperar la cuenta.'
            );
        }

        $token     = SessionToken::generate();
        $tokenHash = SessionToken::hash($token);

        $session = $this->sessionRepository->create(
            $customer->getId(),
            $tokenHash,
            $this->normalizeIpAddress($ipAddress),
            $this->normalizeUserAgent($userAgent),
            self::SESSION_DURATION
        );

        return new LoginResult(
            $customer,
            $session,
            $token
        );
    }

    private function normalizeIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false
            ? $ipAddress
            : null;
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        $userAgent = trim($userAgent);

        if ($userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 500);
    }
}