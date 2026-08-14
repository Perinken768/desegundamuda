<?php

declare(strict_types=1);

namespace DSM\Promocionar\Integration;

use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAccountPromotionIntegration
{
    public static function register(): void
    {
        add_action(
            'dsm_customer_account_sections',
            [
                self::class,
                'render',
            ],
            10,
            2
        );
    }

    public static function render(
        mixed $customer,
        mixed $profile = null
    ): void {
        if (
            !is_object($customer)
            || !method_exists(
                $customer,
                'getId'
            )
        ) {
            return;
        }

        $customerId =
            (int) $customer->getId();

        if ($customerId <= 0) {
            return;
        }

        try {
            $walletRepository =
                new PromotionWalletRepository();

            $assignmentRepository =
                new PromotionAssignmentRepository();

            $availableWallets =
                $walletRepository
                    ->findAvailableByCustomer(
                        $customerId
                    );

            $assignments =
                $assignmentRepository
                    ->findByCustomer(
                        $customerId
                    );

            $activeCount = 0;

            foreach ($assignments as $assignment) {
                if ($assignment->isActive()) {
                    $activeCount++;
                }
            }

            ?>
            <section class="dsm-account__promotions">
                <article class="dsm-card">
                    <h2 class="dsm-card__title">
                        <?php
                        esc_html_e(
                            'Promociones',
                            'dsm-promocionar'
                        );
                        ?>
                    </h2>

                    <p>
                        <?php
                        printf(
                            esc_html__(
                                'Tienes %1$d promociones activas y %2$d saldos disponibles.',
                                'dsm-promocionar'
                            ),
                            $activeCount,
                            count(
                                $availableWallets
                            )
                        );
                        ?>
                    </p>

                    <div class="dsm-card__actions">
                        <a
                            class="dsm-button dsm-button--primary"
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/mis-promociones/'
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Mis promociones',
                                'dsm-promocionar'
                            );
                            ?>
                        </a>
                    </div>
                </article>
            </section>
            <?php
        } catch (Throwable) {
            /*
             * La cuenta del cliente debe seguir funcionando
             * aunque el módulo de promociones no pueda consultar
             * temporalmente sus datos.
             */
            return;
        }
    }

    private function __construct()
    {
    }
}
