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
 * @var int $historyPage
 * @var int $historyPerPage
 * @var int $historyTotal
 * @var int $historyTotalPages
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
                        '%d min',
                        '%d min',
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

$resolveHistoryStatus =
    static function (
        PromotionAssignment $assignment
    ): string {
        return match (
            sanitize_key(
                $assignment->getStatus()
            )
        ) {
            'exhausted' =>
                __(
                    'Agotada',
                    'dsm-promocionar'
                ),

            'stopped' =>
                __(
                    'Detenida',
                    'dsm-promocionar'
                ),

            'active' =>
                __(
                    'Activa',
                    'dsm-promocionar'
                ),

            default =>
                ucfirst(
                    $assignment->getStatus()
                ),
        };
    };

$historyPage =
    max(
        1,
        (int) ($historyPage ?? 1)
    );

$historyTotal =
    max(
        0,
        (int) ($historyTotal ?? 0)
    );

$historyTotalPages =
    max(
        1,
        (int) ($historyTotalPages ?? 1)
    );

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
                    'Gestiona tus promociones y reutiliza el tiempo que todavía tengas disponible.',
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


    <!-- PROMOCIONES ACTIVAS -->

    <section class="dsm-promotion-section">

        <header class="dsm-promotion-section__header">
            <div>
                <div class="dsm-promotion-section__title-row">
                    <h2>
                        <?php
                        esc_html_e(
                            'Promociones activas',
                            'dsm-promocionar'
                        );
                        ?>
                    </h2>

                    <span class="dsm-promotion-section__count">
                        <?php
                        echo esc_html(
                            (string) count(
                                $activePromotions
                            )
                        );
                        ?>
                    </span>
                </div>

                <p>
                    <?php
                    esc_html_e(
                        'Promociones que están dando más visibilidad a tus anuncios.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>
        </header>

        <?php if (
            $activePromotions === []
        ) : ?>

            <div class="dsm-promotion-empty">
                <strong>
                    <?php
                    esc_html_e(
                        'No tienes promociones activas',
                        'dsm-promocionar'
                    );
                    ?>
                </strong>

                <p>
                    <?php
                    esc_html_e(
                        'Cuando promociones uno de tus anuncios aparecerá aquí.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <div
                class="dsm-promotion-carousel"
                data-dsm-promotion-carousel
            >
                <button
                    type="button"
                    class="dsm-promotion-carousel__arrow dsm-promotion-carousel__arrow--previous"
                    data-dsm-carousel-previous
                    aria-label="<?php
                    esc_attr_e(
                        'Promociones anteriores',
                        'dsm-promocionar'
                    );
                    ?>"
                >
                    ‹
                </button>

                <div
                    class="dsm-promotion-carousel__viewport"
                    data-dsm-carousel-viewport
                >

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

                        <article class="dsm-promotion-card dsm-promotion-card--active">

                            <div class="dsm-promotion-card__top">
                                <span class="dsm-promotion-status dsm-promotion-status--active">
                                    <span
                                        class="dsm-promotion-status__dot"
                                        aria-hidden="true"
                                    ></span>

                                    <?php
                                    esc_html_e(
                                        'Activa',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </span>
                            </div>

                            <h3 class="dsm-promotion-card__title">
                                <?php
                                echo esc_html(
                                    $resolveAdvertisementTitle(
                                        $item
                                    )
                                );
                                ?>
                            </h3>

                            <div class="dsm-promotion-card__highlight">
                                <span class="dsm-promotion-card__eyebrow">
                                    <?php
                                    esc_html_e(
                                        'Tiempo restante',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $formatDuration(
                                            $remainingSeconds
                                        )
                                    );
                                    ?>
                                </strong>
                            </div>

                            <dl class="dsm-promotion-card__details">
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
                                            'Consumido',
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

                                <?php if (
                                    $wallet !== null
                                    && $wallet->getPricePaid()
                                        !== null
                                ) : ?>
                                    <div>
                                        <dt>
                                            <?php
                                            esc_html_e(
                                                'Precio',
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

                            <div class="dsm-promotion-card__actions">

                                <a
                                    class="dsm-button dsm-button--primary"
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
                                            'Detener',
                                            'dsm-promocionar'
                                        );
                                        ?>
                                    </button>
                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <button
                    type="button"
                    class="dsm-promotion-carousel__arrow dsm-promotion-carousel__arrow--next"
                    data-dsm-carousel-next
                    aria-label="<?php
                    esc_attr_e(
                        'Promociones siguientes',
                        'dsm-promocionar'
                    );
                    ?>"
                >
                    ›
                </button>
            </div>

        <?php endif; ?>

    </section>


    <!-- SALDO DISPONIBLE -->

    <section class="dsm-promotion-section">

        <header class="dsm-promotion-section__header">
            <div>
                <div class="dsm-promotion-section__title-row">
                    <h2>
                        <?php
                        esc_html_e(
                            'Saldo disponible',
                            'dsm-promocionar'
                        );
                        ?>
                    </h2>

                    <span class="dsm-promotion-section__count">
                        <?php
                        echo esc_html(
                            (string) count(
                                $availableWallets
                            )
                        );
                        ?>
                    </span>
                </div>

                <p>
                    <?php
                    esc_html_e(
                        'Tiempo de promoción que puedes utilizar o reutilizar en tus anuncios.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>
        </header>

        <?php if (
            $availableWallets === []
        ) : ?>

            <div class="dsm-promotion-empty">
                <strong>
                    <?php
                    esc_html_e(
                        'No tienes saldo disponible',
                        'dsm-promocionar'
                    );
                    ?>
                </strong>

                <p>
                    <?php
                    esc_html_e(
                        'El saldo disponible aparecerá aquí cuando compres una promoción o detengas una antes de agotarla.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <div
                class="dsm-promotion-carousel"
                data-dsm-promotion-carousel
            >
                <button
                    type="button"
                    class="dsm-promotion-carousel__arrow dsm-promotion-carousel__arrow--previous"
                    data-dsm-carousel-previous
                    aria-label="<?php
                    esc_attr_e(
                        'Saldos anteriores',
                        'dsm-promocionar'
                    );
                    ?>"
                >
                    ‹
                </button>

                <div
                    class="dsm-promotion-carousel__viewport"
                    data-dsm-carousel-viewport
                >

                    <?php foreach (
                        $availableWallets
                        as $wallet
                    ) : ?>

                        <article class="dsm-promotion-card dsm-promotion-card--balance">

                            <div class="dsm-promotion-card__top">
                                <span class="dsm-promotion-status dsm-promotion-status--available">
                                    <?php
                                    esc_html_e(
                                        'Disponible',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </span>
                            </div>

                            <h3 class="dsm-promotion-card__title">
                                <?php
                                esc_html_e(
                                    'Saldo de promoción',
                                    'dsm-promocionar'
                                );
                                ?>
                            </h3>

                            <div class="dsm-promotion-card__highlight">
                                <span class="dsm-promotion-card__eyebrow">
                                    <?php
                                    esc_html_e(
                                        'Puedes utilizar',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $formatDuration(
                                            $wallet
                                                ->getRemainingSeconds()
                                        )
                                    );
                                    ?>
                                </strong>
                            </div>

                            <dl class="dsm-promotion-card__details">
                                <div>
                                    <dt>
                                        <?php
                                        esc_html_e(
                                            'Adquirido',
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
                                                'Precio',
                                                'dsm-promocionar'
                                            );
                                            ?>
                                        </dt>

                                        <dd>
                                            <?php
                                            if (
                                                (float) $wallet
                                                    ->getPricePaid()
                                                <= 0
                                            ) {
                                                esc_html_e(
                                                    'Gratis',
                                                    'dsm-promocionar'
                                                );
                                            } else {
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
                                            }
                                            ?>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>

                            <div class="dsm-promotion-card__actions">
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

                </div>

                <button
                    type="button"
                    class="dsm-promotion-carousel__arrow dsm-promotion-carousel__arrow--next"
                    data-dsm-carousel-next
                    aria-label="<?php
                    esc_attr_e(
                        'Saldos siguientes',
                        'dsm-promocionar'
                    );
                    ?>"
                >
                    ›
                </button>
            </div>

        <?php endif; ?>

    </section>


    <!-- HISTORIAL -->

    <section class="dsm-promotion-section dsm-promotion-section--history">

        <header class="dsm-promotion-section__header">
            <div>
                <div class="dsm-promotion-section__title-row">
                    <h2>
                        <?php
                        esc_html_e(
                            'Historial',
                            'dsm-promocionar'
                        );
                        ?>
                    </h2>

                    <span class="dsm-promotion-section__count">
                        <?php
                        echo esc_html(
                            (string) $historyTotal
                        );
                        ?>
                    </span>
                </div>

                <p>
                    <?php
                    esc_html_e(
                        'Todas las promociones que has utilizado.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>
        </header>

        <?php if ($history === []) : ?>

            <div class="dsm-promotion-empty">
                <strong>
                    <?php
                    esc_html_e(
                        'Todavía no tienes historial',
                        'dsm-promocionar'
                    );
                    ?>
                </strong>

                <p>
                    <?php
                    esc_html_e(
                        'Las promociones finalizadas aparecerán aquí.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>
            </div>

        <?php else : ?>

            <div class="dsm-promotion-history-grid">

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

                    $status =
                        sanitize_key(
                            $assignment
                                ->getStatus()
                        );
                    ?>

                    <article class="dsm-promotion-card dsm-promotion-card--history">

                        <div class="dsm-promotion-card__top">
                            <span
                                class="dsm-promotion-status dsm-promotion-status--<?php
                                echo esc_attr(
                                    $status
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    $resolveHistoryStatus(
                                        $assignment
                                    )
                                );
                                ?>
                            </span>
                        </div>

                        <h3 class="dsm-promotion-card__title">
                            <?php
                            echo esc_html(
                                $resolveAdvertisementTitle(
                                    $item
                                )
                            );
                            ?>
                        </h3>

                        <div class="dsm-promotion-card__highlight">
                            <span class="dsm-promotion-card__eyebrow">
                                <?php
                                esc_html_e(
                                    'Tiempo utilizado',
                                    'dsm-promocionar'
                                );
                                ?>
                            </span>

                            <strong>
                                <?php
                                echo esc_html(
                                    $formatDuration(
                                        $assignment
                                            ->getConsumedSeconds()
                                    )
                                );
                                ?>
                            </strong>
                        </div>

                        <dl class="dsm-promotion-card__details">
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

            </div>

            <?php if (
                $historyTotalPages > 1
            ) : ?>

                <nav
                    class="dsm-promotion-pagination"
                    aria-label="<?php
                    esc_attr_e(
                        'Páginas del historial de promociones',
                        'dsm-promocionar'
                    );
                    ?>"
                >

                    <?php if (
                        $historyPage > 1
                    ) : ?>
                        <a
                            class="dsm-promotion-pagination__arrow"
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    'promotion_history_page',
                                    $historyPage - 1
                                )
                            );
                            ?>"
                            aria-label="<?php
                            esc_attr_e(
                                'Página anterior',
                                'dsm-promocionar'
                            );
                            ?>"
                        >
                            ‹
                        </a>
                    <?php endif; ?>

                    <?php for (
                        $page = 1;
                        $page <= $historyTotalPages;
                        $page++
                    ) : ?>

                        <?php if (
                            $page === $historyPage
                        ) : ?>

                            <span
                                class="dsm-promotion-pagination__page is-current"
                                aria-current="page"
                            >
                                <?php
                                echo esc_html(
                                    (string) $page
                                );
                                ?>
                            </span>

                        <?php else : ?>

                            <a
                                class="dsm-promotion-pagination__page"
                                href="<?php
                                echo esc_url(
                                    add_query_arg(
                                        'promotion_history_page',
                                        $page
                                    )
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    (string) $page
                                );
                                ?>
                            </a>

                        <?php endif; ?>

                    <?php endfor; ?>

                    <?php if (
                        $historyPage
                        < $historyTotalPages
                    ) : ?>
                        <a
                            class="dsm-promotion-pagination__arrow"
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    'promotion_history_page',
                                    $historyPage + 1
                                )
                            );
                            ?>"
                            aria-label="<?php
                            esc_attr_e(
                                'Página siguiente',
                                'dsm-promocionar'
                            );
                            ?>"
                        >
                            ›
                        </a>
                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </section>

</section>
