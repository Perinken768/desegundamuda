<?php

declare(strict_types=1);

namespace DSM\Publicidad\Integration;

use DSM\Publicidad\Advertising\AdvertisingBannerRepository;
use Throwable;

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
            20,
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
        $modules['advertising'] = [
            'id' =>
                'advertising',

            'label' =>
                __(
                    'Mi publicidad',
                    'dsm-publicidad'
                ),

            'default_priority' =>
                20,

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
        if (!method_exists($customer, 'getId')) {
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

        /*
         * =====================================================
         * DERECHO DE ACCESO A PUBLICIDAD
         * =====================================================
         *
         * DSM Publicidad no conoce la implementación interna
         * de Suscripciones.
         *
         * Únicamente consume el contrato neutral expuesto
         * mediante este filtro.
         */
        $hasAccess =
            (bool) apply_filters(
                'dsm_customer_has_advertising',
                false,
                $customerId
            );

        /*
         * =====================================================
         * CAMPAÑAS EXISTENTES
         * =====================================================
         */

        $count = 0;

        try {
            $repository =
                new AdvertisingBannerRepository();

            $banners =
                $repository->findByCustomer(
                    $customerId
                );

            $count =
                count(
                    $banners
                );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Publicidad] No se pudo cargar '
                . 'el resumen de Mi cuenta: '
                . $exception->getMessage()
            );
        }

        /*
         * Si tiene acceso contratado entra en la gestión.
         *
         * Si no lo tiene, va directamente a los planes.
         */
        $targetUrl =
            $hasAccess
                ? home_url(
                    '/mi-publicidad/'
                )
                : home_url(
                    '/suscripciones/'
                );

        ?>
        <section class="dsm-account-module">

            <article class="dsm-card">

                <div class="dsm-account-module__content">

                    <div>

                        <h2 class="dsm-card__title">
                            <?php
                            esc_html_e(
                                'Mi publicidad',
                                'dsm-publicidad'
                            );
                            ?>
                        </h2>


                        <?php if ($hasAccess) : ?>

                            <p>
                                <?php
                                esc_html_e(
                                    'Gestiona tus campañas publicitarias y banners en DeSegundaMuda.',
                                    'dsm-publicidad'
                                );
                                ?>
                            </p>

                            <p class="dsm-account-module__summary">

                                <?php
                                printf(
                                    esc_html(
                                        _n(
                                            '%d campaña registrada.',
                                            '%d campañas registradas.',
                                            $count,
                                            'dsm-publicidad'
                                        )
                                    ),
                                    $count
                                );
                                ?>

                            </p>

                        <?php else : ?>

                            <p>
                                <?php
                                esc_html_e(
                                    'Promociona tu negocio mediante espacios publicitarios en DeSegundaMuda.',
                                    'dsm-publicidad'
                                );
                                ?>
                            </p>

                            <p class="dsm-account-module__summary">
                                <?php
                                esc_html_e(
                                    'Publicidad no está activa actualmente en tu cuenta.',
                                    'dsm-publicidad'
                                );
                                ?>
                            </p>

                        <?php endif; ?>

                    </div>


                    <div class="dsm-account-module__actions">

                        <a
                            class="
                                dsm-button
                                dsm-button--primary
                            "
                            href="<?php
                            echo esc_url(
                                $targetUrl
                            );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $hasAccess
                                    ? __(
                                        'Gestionar mi publicidad',
                                        'dsm-publicidad'
                                    )
                                    : __(
                                        'Activar Publicidad',
                                        'dsm-publicidad'
                                    )
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
