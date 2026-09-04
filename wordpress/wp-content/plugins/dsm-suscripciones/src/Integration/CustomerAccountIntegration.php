<?php

declare(strict_types=1);

namespace DSM\Suscripciones\Integration;

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
                'registerModule',
            ],
            15,
            1
        );
    }

    /**
     * @param array<string, array<string, mixed>> $modules
     *
     * @return array<string, array<string, mixed>>
     */
    public static function registerModule(
        array $modules
    ): array {
        $modules['subscriptions'] = [
            'id' =>
                'subscriptions',

            'label' =>
                __(
                    'Mis suscripciones',
                    'dsm-suscripciones'
                ),

            /*
             * Promociones usa 10.
             * Publicidad usa 20.
             *
             * Colocamos Suscripciones entre ambos.
             */
            'default_priority' =>
                15,

            'callback' => [
                self::class,
                'render',
            ],
        ];

        return $modules;
    }

    public static function render(
        object $customer,
        ?object $profile = null
    ): void {
        if (
            !method_exists(
                $customer,
                'getId'
            )
        ) {
            return;
        }

        $customerId =
            max(
                0,
                (int) $customer->getId()
            );

        if ($customerId <= 0) {
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
                                'Mis suscripciones',
                                'dsm-suscripciones'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            esc_html_e(
                                'Consulta y gestiona tus planes de suscripción en DeSegundaMuda.',
                                'dsm-suscripciones'
                            );
                            ?>
                        </p>

                        <p class="dsm-account-module__summary">
                            <?php
                            esc_html_e(
                                'Revisa tus planes activos, renovaciones y fechas de vencimiento.',
                                'dsm-suscripciones'
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
                                    '/suscripciones/'
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Gestionar mis suscripciones',
                                'dsm-suscripciones'
                            );
                            ?>
                        </a>

                    </div>

                </div>

            </article>

        </section>
        <?php
    }

    private function __construct()
    {
    }
}
