<?php

declare(strict_types=1);

namespace DSM\Multitienda\Integration;

use DSM\Multitienda\Application\MultistoreAccessService;
use DSM\Multitienda\Store\StoreRepository;
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
            30,
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
        $modules['multistore'] = [
            'id' =>
                'multistore',

            'label' =>
                __(
                    'Mi tienda',
                    'dsm-multitienda'
                ),

            'default_priority' =>
                30,

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

        $hasAccess = false;
        $store = null;

        try {
            $accessService =
                new MultistoreAccessService();

            $hasAccess =
                $accessService->hasAccess(
                    $customerId
                );

            if ($hasAccess) {
                $repository =
                    new StoreRepository();

                $store =
                    $repository->findByCustomerId(
                        $customerId
                    );
            }
        } catch (Throwable $exception) {
            error_log(
                '[DSM Multitienda] No se pudo cargar '
                . 'el resumen de Mi tienda: '
                . $exception->getMessage()
            );
        }

        $targetUrl =
            $hasAccess
                ? home_url('/mi-tienda/')
                : home_url('/suscripciones/');

        ?>
        <section class="dsm-account-module">
            <article class="dsm-card">
                <div class="dsm-account-module__content">

                    <div>
                        <h2 class="dsm-card__title">
                            Mi tienda
                        </h2>

                        <?php if ($hasAccess) : ?>

                            <p>
                                Gestiona tus productos, stock,
                                variantes y reservas.
                            </p>

                            <p class="dsm-account-module__summary">
                                <?php if ($store !== null) : ?>
                                    Tienda:
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $store->getName()
                                        );
                                        ?>
                                    </strong>
                                <?php else : ?>
                                    Tu acceso Multitienda está activo.
                                    Puedes crear tu tienda.
                                <?php endif; ?>
                            </p>

                        <?php else : ?>

                            <p>
                                Crea tu propia tienda dentro de
                                DeSegundaMuda y gestiona catálogo,
                                stock y reservas.
                            </p>

                            <p class="dsm-account-module__summary">
                                Multitienda no está activa
                                actualmente en tu cuenta.
                            </p>

                        <?php endif; ?>
                    </div>

                    <div class="dsm-account-module__actions">

                        <a
                            class="dsm-button dsm-button--primary"
                            href="<?php
                            echo esc_url(
                                $targetUrl
                            );
                            ?>"
                        >
                            <?php
                            echo esc_html(
                                $hasAccess
                                    ? 'Gestionar mi tienda'
                                    : 'Activar Multitienda'
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
