<?php

declare(strict_types=1);

use DSM\Promocionar\Frontend\PromotionActionController;
use DSM\Promocionar\Frontend\PromotionPurchaseController;
use DSM\Promocionar\Promotion\PromotionAssignment;
use DSM\Promocionar\Promotion\PromotionPlan;
use DSM\Promocionar\Promotion\PromotionWallet;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<string, mixed> $customerContext
 * @var int $customerId
 * @var array<string, mixed> $advertisement
 * @var int $advertisementId
 * @var PromotionAssignment|null $activeAssignment
 * @var array<int, PromotionWallet> $wallets
 * @var array<int, PromotionPlan> $plans
 * @var string $status
 * @var string $error
 */

$title =
    trim(
        (string) (
            $advertisement['title']
            ?? ''
        )
    );

$advertisementLabel =
    $title !== ''
        ? $title
        : sprintf(
            __(
                'Anuncio #%d',
                'dsm-promocionar'
            ),
            $advertisementId
        );

?>

<section class="dsm-promote-advertisement">

    <header class="dsm-account-header">

        <h1>
            <?php
            esc_html_e(
                'Promocionar anuncio',
                'dsm-promocionar'
            );
            ?>
        </h1>

        <p>
            <?php
            printf(
                esc_html__(
                    'Elige cuánto tiempo quieres destacar «%s».',
                    'dsm-promocionar'
                ),
                esc_html(
                    $advertisementLabel
                )
            );
            ?>
        </p>

    </header>

    <?php if ($status === 'started') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            <?php
            esc_html_e(
                'La promoción se ha iniciado correctamente.',
                'dsm-promocionar'
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if ($status === 'stopped') : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            <?php
            esc_html_e(
                'La promoción se ha detenido correctamente. El tiempo no consumido vuelve a estar disponible.',
                'dsm-promocionar'
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if (
        in_array(
            $status,
            [
                'error',
                'purchase_error',
            ],
            true
        )
        && $error !== ''
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--error
            "
        >
            <?php
            echo esc_html(
                $error
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if ($activeAssignment !== null) : ?>

        <section class="dsm-promotion-section">

            <div class="dsm-promotion-section__header">

                <h2>
                    <?php
                    esc_html_e(
                        'Promoción activa',
                        'dsm-promocionar'
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    esc_html_e(
                        'Este anuncio está disfrutando actualmente de mayor visibilidad.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>

            </div>

            <article
                class="
                    dsm-card
                    dsm-promotion-active-card
                "
            >

                <div class="dsm-promotion-active-card__content">

                    <div>
                        <span class="dsm-promotion-active-card__label">
                            <?php
                            esc_html_e(
                                'Anuncio',
                                'dsm-promocionar'
                            );
                            ?>
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                $advertisementLabel
                            );
                            ?>
                        </strong>
                    </div>

                    <div>
                        <span class="dsm-promotion-active-card__label">
                            <?php
                            esc_html_e(
                                'Inicio',
                                'dsm-promocionar'
                            );
                            ?>
                        </span>

                        <strong>
                            <?php
                            echo esc_html(
                                get_date_from_gmt(
                                    $activeAssignment
                                        ->getStartedAt()
                                        ->format(
                                            'Y-m-d H:i:s'
                                        ),
                                    'd/m/Y H:i'
                                )
                            );
                            ?>
                        </strong>
                    </div>

                </div>

                <form
                    method="post"
                    action="<?php
                    echo esc_url(
                        admin_url(
                            'admin-post.php'
                        )
                    );
                    ?>"
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
                            (string) $advertisementId
                        );
                        ?>"
                    >

                    <input
                        type="hidden"
                        name="assignment_id"
                        value="<?php
                        echo esc_attr(
                            (string) $activeAssignment
                                ->getId()
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        PromotionActionController::
                            getStopNonceAction(
                                $advertisementId,
                                $activeAssignment
                                    ->getId()
                            ),
                        PromotionActionController::
                            NONCE_FIELD
                    );
                    ?>

                    <button
                        type="submit"
                        class="
                            dsm-button
                            dsm-button--secondary
                        "
                        onclick="return confirm('<?php
                        echo esc_js(
                            __(
                                '¿Quieres detener esta promoción? El tiempo no consumido quedará disponible para reutilizarlo.',
                                'dsm-promocionar'
                            )
                        );
                        ?>');"
                    >
                        <?php
                        esc_html_e(
                            'Detener promoción',
                            'dsm-promocionar'
                        );
                        ?>
                    </button>

                </form>

            </article>

        </section>

    <?php else : ?>

        <section class="dsm-promotion-section">

            <div class="dsm-promotion-section__header">

                <h2>
                    <?php
                    esc_html_e(
                        'Saldo disponible',
                        'dsm-promocionar'
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    esc_html_e(
                        'Si tienes tiempo de promoción guardado puedes utilizarlo directamente en este anuncio.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>

            </div>

            <?php if ($wallets === []) : ?>

                <div class="dsm-promotion-empty">

                    <strong>
                        <?php
                        esc_html_e(
                            'No tienes saldo de promoción disponible',
                            'dsm-promocionar'
                        );
                        ?>
                    </strong>

                    <p>
                        <?php
                        esc_html_e(
                            'Puedes contratar uno de los planes disponibles y comenzar a promocionar este anuncio.',
                            'dsm-promocionar'
                        );
                        ?>
                    </p>

                </div>

            <?php else : ?>

                <div class="dsm-promotion-wallets">

                    <?php foreach ($wallets as $wallet) : ?>

                        <article
                            class="
                                dsm-card
                                dsm-promotion-wallet
                            "
                        >

                            <div>

                                <span class="dsm-promotion-wallet__label">
                                    <?php
                                    esc_html_e(
                                        'Tiempo disponible',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </span>

                                <strong class="dsm-promotion-wallet__time">
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $wallet
                                                ->getRemainingSeconds()
                                            / DAY_IN_SECONDS,
                                            2
                                        )
                                    );
                                    ?>

                                    <?php
                                    esc_html_e(
                                        'días',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </strong>

                                <?php if (
                                    $wallet->getPricePaid()
                                    !== null
                                ) : ?>

                                    <span class="dsm-promotion-wallet__origin">
                                        <?php
                                        printf(
                                            esc_html__(
                                                'Saldo adquirido por %1$s %2$s',
                                                'dsm-promocionar'
                                            ),
                                            esc_html(
                                                number_format_i18n(
                                                    $wallet
                                                        ->getPricePaid(),
                                                    2
                                                )
                                            ),
                                            esc_html(
                                                $wallet
                                                    ->getCurrency()
                                                ?? ''
                                            )
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <form
                                method="post"
                                action="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin-post.php'
                                    )
                                );
                                ?>"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="<?php
                                    echo esc_attr(
                                        PromotionActionController::
                                            ACTION_START
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="advertisement_id"
                                    value="<?php
                                    echo esc_attr(
                                        (string) $advertisementId
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="wallet_id"
                                    value="<?php
                                    echo esc_attr(
                                        (string) $wallet->getId()
                                    );
                                    ?>"
                                >

                                <?php
                                wp_nonce_field(
                                    PromotionActionController::
                                        getNonceAction(
                                            $advertisementId,
                                            $wallet->getId()
                                        ),
                                    PromotionActionController::
                                        NONCE_FIELD
                                );
                                ?>

                                <button
                                    type="submit"
                                    class="
                                        dsm-button
                                        dsm-button--primary
                                    "
                                >
                                    <?php
                                    esc_html_e(
                                        'Usar este saldo',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <section class="dsm-promotion-section">

            <div class="dsm-promotion-section__header">

                <h2>
                    <?php
                    esc_html_e(
                        'Planes disponibles',
                        'dsm-promocionar'
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    esc_html_e(
                        'Elige durante cuánto tiempo quieres dar más visibilidad a este anuncio.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>

            </div>

            <?php if ($plans === []) : ?>

                <div class="dsm-promotion-empty">

                    <strong>
                        <?php
                        esc_html_e(
                            'No hay planes de promoción disponibles actualmente.',
                            'dsm-promocionar'
                        );
                        ?>
                    </strong>

                </div>

            <?php else : ?>

                <div class="dsm-promotion-plans__grid">

                    <?php foreach ($plans as $plan) : ?>

                        <?php
                        $durationDays =
                            max(
                                1,
                                (int) round(
                                    $plan
                                        ->getDurationSeconds()
                                    / DAY_IN_SECONDS
                                )
                            );
                        ?>

                        <article class="dsm-card">

                            <h2>
                                <?php
                                echo esc_html(
                                    $plan->getName()
                                );
                                ?>
                            </h2>

                            <p>
                                <?php
                                printf(
                                    esc_html__(
                                        'Destaca «%1$s» durante %2$d días.',
                                        'dsm-promocionar'
                                    ),
                                    esc_html(
                                        $advertisementLabel
                                    ),
                                    $durationDays
                                );
                                ?>
                            </p>

                            <p class="dsm-promotion-plan__price">

                                <strong>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $plan->getPrice(),
                                            2
                                        )
                                    );
                                    ?>

                                    <?php
                                    echo esc_html(
                                        $plan->getCurrency()
                                    );
                                    ?>
                                </strong>

                            </p>

                            <ul>

                                <li>
                                    <?php
                                    printf(
                                        esc_html__(
                                            '%d días de promoción',
                                            'dsm-promocionar'
                                        ),
                                        $durationDays
                                    );
                                    ?>
                                </li>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Mayor visibilidad para tu anuncio',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </li>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'El tiempo no consumido puede reutilizarse',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </li>

                            </ul>

                            <form
                                method="post"
                                action="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin-post.php'
                                    )
                                );
                                ?>"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="<?php
                                    echo esc_attr(
                                        PromotionPurchaseController::
                                            ACTION
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="advertisement_id"
                                    value="<?php
                                    echo esc_attr(
                                        (string) $advertisementId
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="plan_id"
                                    value="<?php
                                    echo esc_attr(
                                        (string) $plan->getId()
                                    );
                                    ?>"
                                >

                                <?php
                                wp_nonce_field(
                                    PromotionPurchaseController::
                                        getNonceAction(
                                            $advertisementId,
                                            $plan->getId()
                                        ),
                                    PromotionPurchaseController::
                                        NONCE_FIELD
                                );
                                ?>

                                <button
                                    type="submit"
                                    class="
                                        dsm-button
                                        dsm-button--primary
                                    "
                                >
                                    <?php
                                    printf(
                                        esc_html__(
                                            'Comprar por %1$s %2$s',
                                            'dsm-promocionar'
                                        ),
                                        esc_html(
                                            number_format_i18n(
                                                $plan->getPrice(),
                                                2
                                            )
                                        ),
                                        esc_html(
                                            $plan->getCurrency()
                                        )
                                    );
                                    ?>
                                </button>

                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</section>
