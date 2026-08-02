<?php

declare(strict_types=1);

namespace DSM\Clientes\Admin;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationService;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerImpersonationController
{
    public function register(): void
    {
        add_action(
            'admin_post_dsm_customer_admin_impersonate',
            [$this, 'handleStart']
        );

        add_action(
            'admin_post_dsm_customer_stop_impersonation',
            [$this, 'handleStop']
        );
    }

    public function handleStart(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_impersonate',
            'dsm_customer_admin_nonce'
        );

        $customerId = isset($_POST['customer_id'])
            ? absint($_POST['customer_id'])
            : 0;

        if ($customerId <= 0) {
            wp_die(
                esc_html__(
                    'El identificador del cliente no es válido.',
                    'dsm-clientes'
                )
            );
        }

        $returnUrl = add_query_arg(
            [
                'page' => 'dsm-clientes',
                'action' => 'view',
                'customer_id' => $customerId,
            ],
            admin_url('admin.php')
        );

        try {
            $this->createService()->start(
                $customerId,
                get_current_user_id(),
                $returnUrl
            );

            wp_safe_redirect(
                home_url('/mi-cuenta/')
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error iniciando suplantación: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                add_query_arg(
                    'admin_status',
                    'impersonation_error',
                    $returnUrl
                )
            );

            exit;
        }
    }

    public function handleStop(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_stop_impersonation',
            'dsm_impersonation_nonce'
        );

        try {
            $returnUrl = $this->createService()->stop();

            wp_safe_redirect($returnUrl);

            exit;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] Error finalizando suplantación: '
                . $exception->getMessage()
            );

            wp_safe_redirect(
                admin_url('admin.php?page=dsm-clientes')
            );

            exit;
        }
    }

    private function createService(): CustomerImpersonationService
    {
        return new CustomerImpersonationService(
            new CustomerRepository(),
            new CustomerSessionRepository()
        );
    }

    private function assertAdministrator(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-clientes'
                )
            );
        }
    }
}