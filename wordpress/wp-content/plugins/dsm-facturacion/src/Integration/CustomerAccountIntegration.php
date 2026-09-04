<?php

declare(strict_types=1);

namespace DSM\Facturacion\Integration;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAccountIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_customer_account_modules',
            [
                self::class,
                'registerModules',
            ],
            40,
            1
        );
    }

    /**
     * @param array<string, array<string, mixed>> $modules
     *
     * @return array<string, array<string, mixed>>
     */
    public static function registerModules(
        array $modules
    ): array {
        $modules['billing'] = [
            'id' =>
                'billing',

            'label' =>
                __(
                    'Datos fiscales',
                    'dsm-facturacion'
                ),

            'default_priority' =>
                40,

            'callback' => [
                self::class,
                'renderBilling',
            ],
        ];

        $modules['invoices'] = [
            'id' =>
                'invoices',

            'label' =>
                __(
                    'Mis facturas',
                    'dsm-facturacion'
                ),

            'default_priority' =>
                45,

            'callback' => [
                self::class,
                'renderInvoices',
            ],
        ];

        return $modules;
    }

    public static function renderBilling(
        object $customer,
        ?object $profile = null
    ): void {
        if (!self::isValidCustomer($customer)) {
            return;
        }

        ?>
        <section class="dsm-account-module">

            <article class="dsm-card">

                <div class="dsm-account-module__content">

                    <div>

                        <h2 class="dsm-card__title">
                            <?php
                            esc_html_e(
                                'Datos fiscales',
                                'dsm-facturacion'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            esc_html_e(
                                'Configura los datos fiscales que utilizaremos para emitir tus facturas.',
                                'dsm-facturacion'
                            );
                            ?>
                        </p>

                        <p class="dsm-account-module__summary">
                            <?php
                            esc_html_e(
                                'Puedes indicar tus datos como particular o empresa y mantenerlos actualizados.',
                                'dsm-facturacion'
                            );
                            ?>
                        </p>

                    </div>

                    <div class="dsm-account-module__actions">

                        <a
                            class="
                                dsm-button
                                dsm-button--primary
                            "
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/datos-fiscales/'
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Gestionar datos fiscales',
                                'dsm-facturacion'
                            );
                            ?>
                        </a>

                    </div>

                </div>

            </article>

        </section>
        <?php
    }

    public static function renderInvoices(
        object $customer,
        ?object $profile = null
    ): void {
        if (!self::isValidCustomer($customer)) {
            return;
        }

        ?>
        <section class="dsm-account-module">

            <article class="dsm-card">

                <div class="dsm-account-module__content">

                    <div>

                        <h2 class="dsm-card__title">
                            <?php
                            esc_html_e(
                                'Mis facturas',
                                'dsm-facturacion'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            esc_html_e(
                                'Consulta las facturas emitidas por los servicios contratados en DeSegundaMuda.',
                                'dsm-facturacion'
                            );
                            ?>
                        </p>

                        <p class="dsm-account-module__summary">
                            <?php
                            esc_html_e(
                                'Revisa tus importes, fechas y conceptos facturados.',
                                'dsm-facturacion'
                            );
                            ?>
                        </p>

                    </div>

                    <div class="dsm-account-module__actions">

                        <a
                            class="
                                dsm-button
                                dsm-button--primary
                            "
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/mis-facturas/'
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Ver mis facturas',
                                'dsm-facturacion'
                            );
                            ?>
                        </a>

                    </div>

                </div>

            </article>

        </section>
        <?php
    }

    private static function isValidCustomer(
        object $customer
    ): bool {
        if (
            !method_exists(
                $customer,
                'getId'
            )
        ) {
            return false;
        }

        return (int) $customer->getId() > 0;
    }

    private function __construct()
    {
    }
}
