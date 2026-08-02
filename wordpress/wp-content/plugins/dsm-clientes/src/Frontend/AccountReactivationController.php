<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\ReactivateCustomerAccount;
use DSM\Clientes\Application\SendCustomerReactivationEmail;
use DSM\Clientes\Authentication\AccountReactivationRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class AccountReactivationController
{
    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_dsm_customer_request_reactivation',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_dsm_customer_request_reactivation',
            [self::class, 'handleRequest']
        );

        add_action(
            'admin_post_nopriv_dsm_customer_confirm_reactivation',
            [self::class, 'handleConfirmation']
        );

        add_action(
            'admin_post_dsm_customer_confirm_reactivation',
            [self::class, 'handleConfirmation']
        );
    }

    public static function handleRequest(): void
    {
        check_admin_referer(
            'dsm_customer_request_reactivation',
            'dsm_reactivation_nonce'
        );

        $email = isset($_POST['email'])
            ? sanitize_email(
                wp_unslash($_POST['email'])
            )
            : '';

        try {
            $customerRepository =
                new CustomerRepository();

            $customer =
                $customerRepository->findByEmail(
                    $email
                );

            /*
             * Respuesta neutra para no revelar si existe
             * una cuenta asociada al correo.
             */
            if (
                $customer !== null
                && $customer->getStatus()
                    === CustomerStatus::INACTIVE
            ) {
                $service =
                    new SendCustomerReactivationEmail(
                        new AccountReactivationRepository()
                    );

                $service->execute($customer);
            }

            self::redirectLogin(
                'reactivation_sent'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error solicitando reactivación: '
                . $exception->getMessage()
            );

            self::redirectLogin(
                'reactivation_error'
            );
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
            $service =
                new ReactivateCustomerAccount(
                    new AccountReactivationRepository(),
                    new CustomerRepository()
                );

            $service->execute($token);

            self::redirectLogin(
                'reactivated'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error confirmando reactivación: '
                . $exception->getMessage()
            );

            self::redirectLogin(
                'reactivation_invalid'
            );
        }
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
