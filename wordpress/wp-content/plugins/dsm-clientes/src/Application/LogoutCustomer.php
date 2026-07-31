<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\SessionToken;

if (!defined('ABSPATH')) {
    exit;
}

final class LogoutCustomer
{
    public function __construct(
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function execute(): void
    {
        $token = CustomerCookie::get();

        if ($token !== null) {
            $session = $this->sessionRepository->findByTokenHash(
                SessionToken::hash($token)
            );

            if ($session !== null && !$session->isRevoked()) {
                $this->sessionRepository->revoke(
                    $session->getId()
                );
            }
        }

        CustomerCookie::clear();
    }
}