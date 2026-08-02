<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\CustomerDeletionService;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerCookie;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Deletion\CustomerDeletionRequestRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountDeletionController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_request_deletion',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_dsm_customer_request_deletion',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_nopriv_dsm_customer_confirm_deletion',
            [self::class, 'handleConfirmation']
        );

        add_action(
            'admin_post_dsm_customer_confirm_deletion',
            [self::class, 'handleConfirmation']
        );

        add_action(
            'admin_post_nopriv_dsm_customer_cancel_deletion',
            [self::class, 'handleCancellation']
        );

        add_action(
            'admin_post_dsm_customer_cancel_deletion',
            [self::class, 'handleCancellation']
        );
    }

    public static function handleRequest(): void
    {
        check_admin_referer(
            'dsm_customer_request_deletion',
            'dsm_deletion_nonce'
        );

        $password = isset($_POST['password'])
            ? (string) wp_unslash(
                $_POST['password']
            )
            : '';

        $customerRepository =
            new CustomerRepository();

        $sessionRepository =
            new CustomerSessionRepository();

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
            self::createService(
                $customerRepository,
                $sessionRepository
            )->request(
                $customer,
                $password
            );

            wp_safe_redirect(
                add_query_arg(
                    'account_status',
                    'deletion_email_sent',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error solicitando eliminación: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                add_query_arg(
                    'account_error',
                    'deletion_request_failed',
                    home_url('/mi-cuenta/')
                )
            );

            exit;
        }
    }

    public static function handleConfirmation(): void
    {
        $token = isset($_GET['token'])
            ? sanitize_text_field(
                wp_unslash($_GET['token'])
            )
            : '';

        try {
            self::createService()->confirm($token);

            CustomerCookie::clear();

            self::redirectLogin(
                'deletion_scheduled'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error confirmando eliminación: '
                . $exception->getMessage()
            );

            self::redirectLogin(
                'deletion_invalid'
            );
        }
    }

    public static function handleCancellation(): void
    {
        $token = isset($_GET['token'])
            ? sanitize_text_field(
                wp_unslash($_GET['token'])
            )
            : '';

        try {
            self::createService()->cancel($token);

            self::redirectLogin(
                'deletion_cancelled'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error cancelando eliminación: '
                . $exception->getMessage()
            );

            self::redirectLogin(
                'deletion_invalid'
            );
        }
    }

    private static function createService(
        ?CustomerRepository $customerRepository = null,
        ?CustomerSessionRepository $sessionRepository = null
    ): CustomerDeletionService {
        return new CustomerDeletionService(
            new CustomerDeletionRequestRepository(),
            $customerRepository
                ?? new CustomerRepository(),
            $sessionRepository
                ?? new CustomerSessionRepository()
        );
    }

    private static function redirectLogin(
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                'account_status',
                $status,
                home_url('/iniciar-sesion/')
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}