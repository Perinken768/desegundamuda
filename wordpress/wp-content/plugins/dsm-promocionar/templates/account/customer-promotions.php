<?php

declare(strict_types=1);

use DSM\Promocionar\Frontend\PromotionActionController;
use DSM\Promocionar\Promotion\PromotionAssignment;
use DSM\Promocionar\Promotion\PromotionWallet;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var int $customerId
 * @var array<int, PromotionWallet> $wallets
 * @var array<int, PromotionWallet> $availableWallets
 * @var array<int, array<string, mixed>> $activePromotions
 * @var array<int, array<string, mixed>> $history
 */

$formatDuration =
    static function (
        int $seconds
    ): string {
        $seconds =
            max(
                0,
                $seconds
            );

        $days =
            intdiv(
                $seconds,
                DAY_IN_SECONDS
            );

        $seconds %=
            DAY_IN_SECONDS;

        $hours =
            intdiv(
                $seconds,
                HOUR_IN_SECONDS
            );

        $seconds %=
            HOUR_IN_SECONDS;

        $minutes =
            intdiv(
                $seconds,
                MINUTE_IN_SECONDS
            );

        $parts = [];

        if ($days > 0) {
            $parts[] =
                sprintf(
                    _n(
                        '%d día',
                        '%d días',
                        $days,
                        'dsm-promocionar'
                    ),
                    $days
                );
        }

        if ($hours > 0) {
            $parts[] =
                sprintf(
                    _n(
                        '%d hora',
                        '%d horas',
                        $hours,
                        'dsm-promocionar'
                    ),
                    $hours
                );
        }

        if (
            $minutes > 0
            || $parts === []
        ) {
            $parts[] =
                sprintf(
                    _n(
                        '%d minuto',
                        '%d minutos',
                        $minutes,
                        'dsm-promocionar'
                    ),
                    $minutes
                );
        }

        return implode(
            ' ',
            $parts
        );
    };

$formatDate =
    static function (
        ?DateTimeImmutable $date
    ): string {
        if ($date === null) {
            return '—';
        }

        return get_date_from_gmt(
            $date->format(
                'Y-m-d H:i:s'
            ),
            'd/m/Y H:i'
        );
    };

$resolveAdvertisementTitle =
    static function (
        array $item
    ): string {
        $advertisement =
            $item['advertisement']
            ?? null;

        $assignment =
            $item['assignment']
            ?? null;

        if (
            is_array($advertisement)
            && trim(
                (string) (
                    $advertisement['title']
                    ?? ''
                )
            ) !== ''
        ) {
            return (string) $advertisement[
                'title'
            ];
        }

        if (
            $assignment
            instanceof PromotionAssignment
        ) {
            return sprintf(
                __(
                    'Anuncio #%d',
                    'dsm-promocionar'
                ),
                $assignment
                    ->getAdvertisementId()
            );
        }

        return __(
            'Anuncio',
            'dsm-promocionar'
        );
    };

?>

<section class="dsm-customer-promotions">

    <header class="dsm-account-section-header">
        <div class="dsm-account-section-header__content">
            <h1>
                <?php
                esc_html_e(
                    'Mis promociones',
                    'dsm-promocionar'
                );
                ?>
            </h1>

            <p>
                <?php
                esc_html_e(
                    'Consulta tu saldo de promoción, promociones activas e historial de uso.',
                    'dsm-promocionar'
                );
                ?>
            </p>
        </div>

        <div class="dsm-account-section-header__actions">
            <a
                class="dsm-button dsm-button--primary"
                href="<?php
                echo esc_url(
                    home_url(
                        '/mis-anuncios/'
                    )
                );
                ?>"
            >
                <?php
                esc_html_e(
                    'Ir a mis anuncios',
                    'dsm-promocionar'
                );
                ?>
            </a>
        </div>
    </header>

    <section>
        <h2>
            <?php
            esc_html_e(
                'Promociones activas',
                'dsm-promocionar'
            );
            ?>
        </h2>

        <?php if (
            $activePromotions === []
        ) : ?>

            <div class="dsm-card">
                <p>
                    <?php
                    esc_html_e(
                        'No tienes ninguna promoción activa.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <?php foreach (
                $activePromotions
                as $item
            ) : ?>

                <?php
                /** @var PromotionAssignment $assignment */
                $assignment =
                    $item['assignment'];

                /** @var PromotionWallet|null $wallet */
                $wallet =
                    $item['wallet']
                    ?? null;

                $remainingSeconds =
                    (int) (
                        $item[
                            'live_remaining_seconds'
                        ]
                        ?? 0
                    );

                $consumedSeconds =
                    (int) (
                        $item[
                            'live_consumed_seconds'
                        ]
                        ?? 0
                    );
                ?>

                <article class="dsm-card">
                    <h3>
                        <?php
                        echo esc_html(
                            $resolveAdvertisementTitle(
                                $item
                            )
                        );
                        ?>
                    </h3>

                    <dl class="dsm-definition-list">
                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Inicio',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDate(
                                        $assignment
                                            ->getStartedAt()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Tiempo consumido',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $consumedSeconds
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Tiempo restante',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $remainingSeconds
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <?php if (
                            $wallet !== null
                            && $wallet->getPricePaid()
                                !== null
                        ) : ?>
                            <div>
                                <dt>
                                    <?php
                                    esc_html_e(
                                        'Compra',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </dt>

                                <dd>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $wallet
                                                ->getPricePaid(),
                                            2
                                        )
                                        . ' '
                                        . (
                                            $wallet
                                                ->getCurrency()
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>

                    <div class="dsm-card__actions">
                        <a
                            class="dsm-button dsm-button--secondary"
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    [
                                        'advertisement_id' =>
                                            $assignment
                                                ->getAdvertisementId(),
                                    ],
                                    home_url(
                                        '/promocionar-anuncio/'
                                    )
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Ver promoción',
                                'dsm-promocionar'
                            );
                            ?>
                        </a>

                        <form
                            method="post"
                            action="<?php
                            echo esc_url(
                                admin_url(
                                    'admin-post.php'
                                )
                            );
                            ?>"
                            onsubmit="return confirm('<?php
                            echo esc_js(
                                __(
                                    '¿Quieres detener esta promoción? El tiempo no consumido quedará disponible para reutilizarlo.',
                                    'dsm-promocionar'
                                )
                            );
                            ?>');"
                        >
                            <input
                                type="hidden"
                                name="action"
                                value="<?php
                                echo esc_attr(
                                    PromotionActionController::
                                        ACTION_STOP
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="advertisement_id"
                                value="<?php
                                echo esc_attr(
                                    (string) $assignment
                                        ->getAdvertisementId()
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="assignment_id"
                                value="<?php
                                echo esc_attr(
                                    (string) $assignment
                                        ->getId()
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                PromotionActionController::
                                    getStopNonceAction(
                                        $assignment
                                            ->getAdvertisementId(),
                                        $assignment
                                            ->getId()
                                    ),
                                PromotionActionController::
                                    NONCE_FIELD
                            );
                            ?>

                            <button
                                type="submit"
                                class="dsm-button dsm-button--secondary"
                            >
                                <?php
                                esc_html_e(
                                    'Detener promoción',
                                    'dsm-promocionar'
                                );
                                ?>
                            </button>
                        </form>
                    </div>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section>
        <h2>
            <?php
            esc_html_e(
                'Saldo disponible',
                'dsm-promocionar'
            );
            ?>
        </h2>

        <?php if (
            $availableWallets === []
        ) : ?>

            <div class="dsm-card">
                <p>
                    <?php
                    esc_html_e(
                        'No tienes saldo disponible para reutilizar.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <?php foreach (
                $availableWallets
                as $wallet
            ) : ?>

                <article class="dsm-card">
                    <h3>
                        <?php
                        esc_html_e(
                            'Saldo de promoción',
                            'dsm-promocionar'
                        );
                        ?>
                    </h3>

                    <dl class="dsm-definition-list">
                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Tiempo restante',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $wallet
                                            ->getRemainingSeconds()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Tiempo adquirido',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $wallet
                                            ->getPurchasedSeconds()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <?php if (
                            $wallet->getPricePaid()
                            !== null
                        ) : ?>
                            <div>
                                <dt>
                                    <?php
                                    esc_html_e(
                                        'Precio pagado',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </dt>

                                <dd>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $wallet
                                                ->getPricePaid(),
                                            2
                                        )
                                        . ' '
                                        . (
                                            $wallet
                                                ->getCurrency()
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>

                    <div class="dsm-card__actions">
                        <a
                            class="dsm-button dsm-button--primary"
                            href="<?php
                            echo esc_url(
                                home_url(
                                    '/mis-anuncios/'
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Elegir anuncio',
                                'dsm-promocionar'
                            );
                            ?>
                        </a>
                    </div>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section>
        <h2>
            <?php
            esc_html_e(
                'Historial',
                'dsm-promocionar'
            );
            ?>
        </h2>

        <?php if ($history === []) : ?>

            <div class="dsm-card">
                <p>
                    <?php
                    esc_html_e(
                        'Todavía no tienes promociones finalizadas.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <?php foreach (
                $history
                as $item
            ) : ?>

                <?php
                /** @var PromotionAssignment $assignment */
                $assignment =
                    $item['assignment'];

                /** @var PromotionWallet|null $wallet */
                $wallet =
                    $item['wallet']
                    ?? null;
                ?>

                <article class="dsm-card">
                    <h3>
                        <?php
                        echo esc_html(
                            $resolveAdvertisementTitle(
                                $item
                            )
                        );
                        ?>
                    </h3>

                    <dl class="dsm-definition-list">
                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Estado',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $assignment
                                        ->getStatus()
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Inicio',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDate(
                                        $assignment
                                            ->getStartedAt()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Finalización',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDate(
                                        $assignment
                                            ->getStoppedAt()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php
                                esc_html_e(
                                    'Tiempo consumido',
                                    'dsm-promocionar'
                                );
                                ?>
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $assignment
                                            ->getConsumedSeconds()
                                    )
                                );
                                ?>
                            </dd>
                        </div>

                        <?php if (
                            $wallet !== null
                        ) : ?>
                            <div>
                                <dt>
                                    <?php
                                    esc_html_e(
                                        'Saldo actual',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </dt>

                                <dd>
                                    <?php
                                    echo esc_html(
                                        $formatDuration(
                                            $wallet
                                                ->getRemainingSeconds()
                                        )
                                    );
                                    ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</section>
