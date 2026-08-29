<?php

declare(strict_types=1);

namespace DSM\Anuncios\Integration;

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
            5,
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
        $modules['advertisements'] = [
            'id' =>
                'advertisements',

            'label' =>
                __(
                    'Mis anuncios',
                    'dsm-anuncios'
                ),

            'default_priority' =>
                5,

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

        $total = 0;
        $active = 0;
        $pending = 0;

        try {
            global $wpdb;

            $table =
                $wpdb->prefix
                . 'dsm_ads';

            $rows =
                $wpdb->get_results(
                    $wpdb->prepare(
                        "
                        SELECT
                            status,
                            COUNT(*) AS total
                        FROM {$table}
                        WHERE customer_id = %d
                        GROUP BY status
                        ",
                        $customerId
                    ),
                    ARRAY_A
                );

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $status =
                        isset($row['status'])
                            ? (string) $row['status']
                            : '';

                    $count =
                        isset($row['total'])
                            ? max(
                                0,
                                (int) $row['total']
                            )
                            : 0;

                    $total += $count;

                    if ($status === 'active') {
                        $active += $count;
                    }

                    if ($status === 'pending') {
                        $pending += $count;
                    }
                }
            }
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudo cargar '
                . 'el resumen de Mis anuncios: '
                . $exception->getMessage()
            );
        }

        ?>
        <section class="dsm-account-module">
            <article class="dsm-card">
                <div class="dsm-account-module__content">

                    <div>
                        <h2 class="dsm-card__title">
                            <?php
                            esc_html_e(
                                'Mis anuncios',
                                'dsm-anuncios'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            esc_html_e(
                                'Gestiona los artículos que tienes publicados en DeSegundaMuda.',
                                'dsm-anuncios'
                            );
                            ?>
                        </p>

                        <p class="dsm-account-module__summary">
                            <?php if ($total === 0) : ?>

                                <?php
                                esc_html_e(
                                    'Todavía no tienes ningún anuncio.',
                                    'dsm-anuncios'
                                );
                                ?>

                            <?php else : ?>

                                <?php
                                printf(
                                    esc_html(
                                        _n(
                                            '%d anuncio en total.',
                                            '%d anuncios en total.',
                                            $total,
                                            'dsm-anuncios'
                                        )
                                    ),
                                    $total
                                );
                                ?>

                                <?php if ($active > 0) : ?>
                                    <?php
                                    echo ' ';

                                    printf(
                                        esc_html(
                                            _n(
                                                '%d activo.',
                                                '%d activos.',
                                                $active,
                                                'dsm-anuncios'
                                            )
                                        ),
                                        $active
                                    );
                                    ?>
                                <?php endif; ?>

                                <?php if ($pending > 0) : ?>
                                    <?php
                                    echo ' ';

                                    printf(
                                        esc_html(
                                            _n(
                                                '%d pendiente de revisión.',
                                                '%d pendientes de revisión.',
                                                $pending,
                                                'dsm-anuncios'
                                            )
                                        ),
                                        $pending
                                    );
                                    ?>
                                <?php endif; ?>

                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="dsm-account-module__actions">

                        <a
                            class="dsm-button dsm-button--primary"
                            href="<?php
                            echo esc_url(
                                home_url('/mis-anuncios/')
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Gestionar mis anuncios',
                                'dsm-anuncios'
                            );
                            ?>
                        </a>

                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php
                            echo esc_url(
                                home_url('/publicar-anuncio/')
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Publicar anuncio',
                                'dsm-anuncios'
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
