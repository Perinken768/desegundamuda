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

?>

<section class="dsm-promote-advertisement">

    <header class="dsm-account-section-header">
        <div class="dsm-account-section-header__content">
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
                echo esc_html(
                    $title !== ''
                        ? $title
                        : sprintf(
                            __(
                                'Anuncio #%d',
                                'dsm-promocionar'
                            ),
                            $advertisementId
                        )
                );
                ?>
            </p>
        </div>
    </header>

    <?php if ($status === 'started') : ?>
        <div
            class="dsm-account-notice dsm-account-notice--success"
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
            class="dsm-account-notice dsm-account-notice--success"
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
            class="dsm-account-notice dsm-account-notice--error"
        >
            <?php
            echo esc_html(
                $error
            );
            ?>
        </div>
    <?php endif; ?>

    <?php if ($activeAssignment !== null) : ?>

        <article class="dsm-card">
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
                    'Este anuncio está consumiendo saldo de promoción.',
                    'dsm-promocionar'
                );
                ?>
            </p>

            <p>
                <strong>
                    <?php
                    esc_html_e(
                        'Inicio:',
                        'dsm-promocionar'
                    );
                    ?>
                </strong>

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
            </p>

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
                    class="dsm-button dsm-button--secondary"
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

    <?php else : ?>

        <article class="dsm-card">
            <h2>
                <?php
                esc_html_e(
                    'Usar saldo disponible',
                    'dsm-promocionar'
                );
                ?>
            </h2>

            <?php if ($wallets === []) : ?>

                <p>
                    <?php
                    esc_html_e(
                        'No tienes saldo de promoción disponible.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>

            <?php else : ?>

                <?php foreach ($wallets as $wallet) : ?>

                    <form
                        method="post"
                        action="<?php
                        echo esc_url(
                            admin_url(
                                'admin-post.php'
                            )
                        );
                        ?>"
                        style="margin-bottom: 16px;"
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

                        <div class="dsm-card">
                            <p>
                                <strong>
                                    <?php
                                    esc_html_e(
                                        'Saldo disponible:',
                                        'dsm-promocionar'
                                    );
                                    ?>
                                </strong>

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
                            </p>

                            <?php if (
                                $wallet->getPricePaid()
                                !== null
                            ) : ?>
                                <p>
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $wallet->getPricePaid(),
                                            2
                                        )
                                        . ' '
                                        . (
                                            $wallet->getCurrency()
                                            ?? ''
                                        )
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>

                            <button
                                type="submit"
                                class="dsm-button dsm-button--primary"
                            >
                                <?php
                                esc_html_e(
                                    'Usar este saldo',
                                    'dsm-promocionar'
                                );
                                ?>
                            </button>
                        </div>
                    </form>

                <?php endforeach; ?>

            <?php endif; ?>
        </article>

        <article class="dsm-card">
            <h2>
                <?php
                esc_html_e(
                    'Planes disponibles',
                    'dsm-promocionar'
                );
                ?>
            </h2>

            <?php if ($plans === []) : ?>

                <p>
                    <?php
                    esc_html_e(
                        'No hay planes de promoción disponibles actualmente.',
                        'dsm-promocionar'
                    );
                    ?>
                </p>

            <?php else : ?>

                <?php foreach ($plans as $plan) : ?>
                    <div class="dsm-card">
                        <h3>
                            <?php
                            echo esc_html(
                                $plan->getName()
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo esc_html(
                                number_format_i18n(
                                    $plan
                                        ->getDurationSeconds()
                                    / DAY_IN_SECONDS,
                                    0
                                )
                                . ' '
                                . __(
                                    'días',
                                    'dsm-promocionar'
                                )
                            );
                            ?>
                        </p>

                        <p>
                            <strong>
                                <?php
                                echo esc_html(
                                    number_format_i18n(
                                        $plan->getPrice(),
                                        2
                                    )
                                    . ' '
                                    . $plan->getCurrency()
                                );
                                ?>
                            </strong>
                        </p>

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
                                class="dsm-button dsm-button--primary"
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
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </article>

    <?php endif; ?>

</section>