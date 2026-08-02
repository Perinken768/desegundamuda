<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Impersonation\CustomerImpersonationCookie;
use DSM\Clientes\Impersonation\CustomerImpersonationService;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerImpersonationBar
{
    private static bool $rendered = false;

    public static function register(): void
    {
        add_action(
            'template_redirect',
            [self::class, 'enforce'],
            1
        );

        add_filter(
            'show_admin_bar',
            [self::class, 'filterAdminBar']
        );

        add_action(
            'wp_body_open',
            [self::class, 'render']
        );

        add_action(
            'wp_footer',
            [self::class, 'render']
        );
    }

    public static function enforce(): void
    {
        self::createService()->enforceCurrentSession();
    }

    public static function filterAdminBar(
        bool $showAdminBar
    ): bool {
        if (CustomerImpersonationCookie::isActive()) {
            return false;
        }

        return $showAdminBar;
    }

    public static function render(): void
    {
        if (
            self::$rendered
            || !CustomerImpersonationCookie::isActive()
        ) {
            return;
        }

        $payload = CustomerImpersonationCookie::get();

        if ($payload === null) {
            return;
        }

        $customer = (new CustomerRepository())->findById(
            $payload['customer_id']
        );

        if ($customer === null) {
            return;
        }

        self::$rendered = true;
        ?>
        <div class="dsm-impersonation-bar">
            <div class="dsm-impersonation-bar__content">
                <div>
                    <strong>
                        <?php
                        esc_html_e(
                            'Modo administrador:',
                            'dsm-clientes'
                        );
                        ?>
                    </strong>

                    <?php
                    printf(
                        esc_html__(
                            'estás navegando como %s.',
                            'dsm-clientes'
                        ),
                        esc_html($customer->getEmail())
                    );
                    ?>
                </div>

                <form
                    method="post"
                    action="<?php echo esc_url(
                        admin_url('admin-post.php')
                    ); ?>"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="dsm_customer_stop_impersonation"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_customer_stop_impersonation',
                        'dsm_impersonation_nonce'
                    );
                    ?>

                    <button type="submit">
                        <?php
                        esc_html_e(
                            'Salir y volver al panel',
                            'dsm-clientes'
                        );
                        ?>
                    </button>
                </form>
            </div>
        </div>

        <style>
            .dsm-impersonation-bar {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 999999;
                padding: 12px 20px;
                color: #fff;
                background: #1f3a5f;
                box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.25);
            }

            .dsm-impersonation-bar__content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                max-width: 1200px;
                margin: 0 auto;
            }

            .dsm-impersonation-bar form {
                margin: 0;
            }

            .dsm-impersonation-bar button {
                padding: 8px 14px;
                color: #1f3a5f;
                font-weight: 700;
                background: #fff;
                border: 0;
                border-radius: 4px;
                cursor: pointer;
            }

            @media (max-width: 700px) {
                .dsm-impersonation-bar__content {
                    align-items: stretch;
                    flex-direction: column;
                }

                .dsm-impersonation-bar button {
                    width: 100%;
                }
            }
        </style>
        <?php
    }

    private static function createService(): CustomerImpersonationService
    {
        return new CustomerImpersonationService(
            new CustomerRepository(),
            new CustomerSessionRepository()
        );
    }

    private function __construct()
    {
    }
}