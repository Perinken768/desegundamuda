<?php

declare(strict_types=1);

namespace DSM\Clientes\Impersonation;

use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\SessionToken;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerImpersonationService
{
    private const DURATION_SECONDS = 30 * MINUTE_IN_SECONDS;
    private const USER_AGENT_PREFIX = '[DSM-IMPERSONATION:';

    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function start(
        int $customerId,
        int $adminUserId,
        string $returnUrl
    ): void {
        if (
            $adminUserId <= 0
            || $adminUserId !== get_current_user_id()
            || !current_user_can('manage_options')
        ) {
            throw new RuntimeException(
                'El administrador no es válido.'
            );
        }

        if (CustomerImpersonationCookie::isActive()) {
            throw new RuntimeException(
                'Ya existe una suplantación activa.'
            );
        }

        $customer = $this->customerRepository->findById(
            $customerId
        );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró el cliente.'
            );
        }

        if (
            !CustomerStatus::canAuthenticate(
                $customer->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El estado del cliente no permite acceder a su cuenta.'
            );
        }

        $token = SessionToken::generate();
        $tokenHash = SessionToken::hash($token);
        $expiresAt = time() + self::DURATION_SECONDS;

        $session = $this->sessionRepository->create(
            $customerId,
            $tokenHash,
            $this->getIpAddress(),
            self::USER_AGENT_PREFIX
                . $adminUserId
                . '] '
                . $this->getUserAgent(),
            self::DURATION_SECONDS
        );

        try {
            CustomerCookie::set(
                $token,
                $expiresAt
            );

            CustomerImpersonationCookie::set(
                [
                    'admin_user_id' => $adminUserId,
                    'customer_id' => $customerId,
                    'session_id' => $session->getId(),
                    'expires_at' => $expiresAt,
                    'return_url' => wp_validate_redirect(
                        $returnUrl,
                        admin_url(
                            'admin.php?page=dsm-clientes'
                        )
                    ),
                ]
            );
        } catch (Throwable $exception) {
            $this->sessionRepository->revoke(
                $session->getId()
            );

            CustomerCookie::clear();
            CustomerImpersonationCookie::clear();

            throw $exception;
        }

        do_action(
            'dsm_customer_impersonation_started',
            $customerId,
            $adminUserId,
            $session->getId()
        );

        do_action(
            'dsm_audit_event',
            'customer.impersonation_started',
            [
                'customer_id' => $customerId,
                'actor_type' => 'wordpress_user',
                'actor_id' => $adminUserId,
                'session_id' => $session->getId(),
            ]
        );
    }

    public function stop(): string
    {
        $payload = CustomerImpersonationCookie::get();

        if ($payload === null) {
            CustomerCookie::clear();
            CustomerImpersonationCookie::clear();

            return admin_url(
                'admin.php?page=dsm-clientes'
            );
        }

        if (
            get_current_user_id()
            !== $payload['admin_user_id']
            || !current_user_can('manage_options')
        ) {
            throw new RuntimeException(
                'No puedes finalizar esta suplantación.'
            );
        }

        $this->sessionRepository->revoke(
            $payload['session_id']
        );

        CustomerCookie::clear();
        CustomerImpersonationCookie::clear();

        do_action(
            'dsm_customer_impersonation_stopped',
            $payload['customer_id'],
            $payload['admin_user_id'],
            $payload['session_id']
        );

        do_action(
            'dsm_audit_event',
            'customer.impersonation_stopped',
            [
                'customer_id' => $payload['customer_id'],
                'actor_type' => 'wordpress_user',
                'actor_id' => $payload['admin_user_id'],
                'session_id' => $payload['session_id'],
            ]
        );

        return $payload['return_url'];
    }

    public function enforceCurrentSession(): void
    {
        $token = CustomerCookie::get();

        if ($token === null) {
            if (CustomerImpersonationCookie::get() !== null) {
                CustomerImpersonationCookie::clear();
            }

            return;
        }

        $session = $this->sessionRepository
            ->findByTokenHash(
                SessionToken::hash($token)
            );

        if ($session === null) {
            CustomerCookie::clear();
            CustomerImpersonationCookie::clear();

            return;
        }

        $userAgent = $session->getUserAgent() ?? '';

        $isImpersonationSession = str_starts_with(
            $userAgent,
            self::USER_AGENT_PREFIX
        );

        if (!$isImpersonationSession) {
            return;
        }

        $payload = CustomerImpersonationCookie::get();

        if (
            $payload === null
            || !CustomerImpersonationCookie::isActive()
            || $payload['session_id'] !== $session->getId()
            || $payload['customer_id']
                !== $session->getCustomerId()
        ) {
            $this->sessionRepository->revoke(
                $session->getId()
            );

            CustomerCookie::clear();
            CustomerImpersonationCookie::clear();
        }
    }

    private function getIpAddress(): ?string
    {
        $ipAddress = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['REMOTE_ADDR'])
            )
            : '';

        return filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP
        ) !== false
            ? $ipAddress
            : null;
    }

    private function getUserAgent(): string
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(
                wp_unslash($_SERVER['HTTP_USER_AGENT'])
            )
            : 'No registrado';

        return mb_substr(
            $userAgent,
            0,
            420
        );
    }
}