<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\DeactivateCustomerAccount;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountStatusController
{
    private const DEACTIVATE_ACTION =
        'dsm_customer_deactivate_account';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
                . self::DEACTIVATE_ACTION,
            [self::class, 'handleDeactivate']
        );

        add_action(
            'admin_post_'
                . self::DEACTIVATE_ACTION,
            [self::class, 'handleDeactivate']
        );
    }

    public static function handleDeactivate(): void
    {
        check_admin_referer(
            'dsm_customer_deactivate_account',
            'dsm_deactivate_account_nonce'
        );

        $password = isset($_POST['password'])
            ? (string) wp_unslash(
                $_POST['password']
            )
            : '';

        $sessionRepository =
            new CustomerSessionRepository();

        $customerRepository =
            new CustomerRepository();

        $authenticatedCustomer =
            new AuthenticatedCustomer(
                $sessionRepository,
                $customerRepository
            );

        $customer =
            $authenticatedCustomer->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url('/iniciar-sesion/')
            );

            exit;
        }

        try {
            $service =
                new DeactivateCustomerAccount(
                    $customerRepository,
                    $sessionRepository
                );

            $service->execute(
                $customer->getId(),
                $password
            );

            CustomerCookie::clear();

            wp_safe_redirect(
                add_query_arg(
                    'account_status',
                    'deactivated',
                    home_url('/iniciar-sesion/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudo cerrar temporalmente la cuenta: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                add_query_arg(
                    'account_error',
                    'deactivation_failed',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        }
    }

    private function __construct()
    {
    }
}
