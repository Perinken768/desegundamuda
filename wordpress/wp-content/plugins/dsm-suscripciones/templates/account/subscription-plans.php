<?php

declare(strict_types=1);

use DSM\Suscripciones\Admin\SubscriptionPlansPage;
use DSM\Suscripciones\Frontend\SubscriptionCancelController;
use DSM\Suscripciones\Frontend\SubscriptionPurchaseController;
use DSM\Suscripciones\Frontend\SubscriptionReactivateController;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, SubscriptionPlan> $plans
 * @var array<string, mixed>|null $customerContext
 * @var int $customerId
 * @var array<int, Subscription> $activeSubscriptionsByPlan
 * @var string $status
 * @var string $error
 */

$pageCopy =
    SubscriptionPlansPage::getPageCopy();

$intervalLabels = [
    'day' =>
        'día',

    'week' =>
        'semana',

    'month' =>
        'mes',

    'year' =>
        'año',
];

?>

<section class="dsm-subscription-plans">

    <header class="dsm-account-header">

        <h1>
            <?php
            echo esc_html(
                $pageCopy['title']
            );
            ?>
        </h1>

        <?php if (
            $pageCopy['description'] !== ''
        ) : ?>

            <p>
                <?php
                echo esc_html(
                    $pageCopy['description']
                );
                ?>
            </p>

        <?php endif; ?>

    </header>

    <?php if (
        in_array(
            $status,
            [
                'purchase_error',
                'subscription_action_error',
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
            <?php echo esc_html($error); ?>
        </div>

    <?php elseif (
        $status === 'retention_offer_accepted'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            La oferta se ha aceptado correctamente.
        </div>

    <?php elseif (
        $status === 'cancellation_requested'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            <?php
            esc_html_e(
                'La renovación automática se ha cancelado correctamente. Mantendrás el servicio hasta que finalice el período ya pagado.',
                'dsm-suscripciones'
            );
            ?>
        </div>

    <?php elseif (
        $status === 'renewal_reactivated'
    ) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--success
            "
        >
            <?php
            esc_html_e(
                'La renovación automática de la suscripción se ha reactivado correctamente.',
                'dsm-suscripciones'
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if ($customerId <= 0) : ?>

        <div
            class="
                dsm-account-notice
                dsm-account-notice--info
            "
        >
            <?php
            esc_html_e(
                'Puedes consultar todos los planes. Inicia sesión para contratar uno.',
                'dsm-suscripciones'
            );
            ?>
        </div>

    <?php endif; ?>

    <?php if ($plans === []) : ?>

        <div class="dsm-account-notice">
            <?php
            esc_html_e(
                'No hay planes disponibles actualmente.',
                'dsm-suscripciones'
            );
            ?>
        </div>

    <?php else : ?>

        <div class="dsm-subscription-plans__grid">

            <?php foreach ($plans as $plan) : ?>

                <?php
                $offerPresentation =
                    apply_filters(
                        'dsm_subscription_plan_offer_presentation',
                        null,
                        $plan,
                        $customerId
                    );
                ?>

                <?php
                $planCopy =
                    SubscriptionPlansPage::getPlanCopy(
                        $plan->getId()
                    );

                $commercialBenefits =
                    is_array(
                        $planCopy['benefits']
                        ?? null
                    )
                        ? $planCopy['benefits']
                        : [];

                $customCtaLabel =
                    trim(
                        (string) (
                            $planCopy['cta_label']
                            ?? ''
                        )
                    );

                $activeSubscription =
                    $activeSubscriptionsByPlan[
                        $plan->getId()
                    ]
                    ?? null;

                $retentionOfferPresentation =
                    $activeSubscription
                    instanceof Subscription
                        ? apply_filters(
                            'dsm_subscription_retention_offer_presentation',
                            null,
                            $plan,
                            $activeSubscription,
                            $customerId
                        )
                        : null;

                $features =
                    $plan->getFeatures();

                $interval =
                    $intervalLabels[
                        $plan->getBillingInterval()
                    ]
                    ?? $plan->getBillingInterval();

                $intervalCount =
                    $plan->getBillingIntervalCount();
                ?>

                <article class="dsm-card">

                    <h2>
                        <?php
                        echo esc_html(
                            $plan->getName()
                        );
                        ?>
                    </h2>

                    <?php if (
                        $plan->getDescription() !== null
                    ) : ?>

                        <p>
                            <?php
                            echo esc_html(
                                $plan->getDescription()
                            );
                            ?>
                        </p>

                    <?php endif; ?>

                    <p class="dsm-subscription-plan__price">

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

                        <?php if (!$plan->isFree()) : ?>

                            <span>
                                /
                                <?php
                                if ($intervalCount === 1) {
                                    echo esc_html(
                                        $interval
                                    );
                                } else {
                                    echo esc_html(
                                        sprintf(
                                            '%d %s',
                                            $intervalCount,
                                            $interval
                                        )
                                    );
                                }
                                ?>
                            </span>

                        <?php endif; ?>

                    </p>

                    <?php if (
                        $commercialBenefits !== []
                    ) : ?>

                        <ul>

                            <?php foreach (
                                $commercialBenefits
                                as $benefit
                            ) : ?>

                                <li>
                                    <?php
                                    echo esc_html(
                                        (string) $benefit
                                    );
                                    ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php elseif (
                        $features !== []
                    ) : ?>

                        <ul>

                            <?php if (
                                array_key_exists(
                                    'max_active_ads',
                                    $features
                                )
                            ) : ?>

                                <?php
                                $maxActiveAds =
                                    (int) $features[
                                        'max_active_ads'
                                    ];
                                ?>

                                <li>
                                    <?php if (
                                        $maxActiveAds === -1
                                    ) : ?>

                                        <?php
                                        esc_html_e(
                                            'Publicaciones ilimitadas',
                                            'dsm-suscripciones'
                                        );
                                        ?>

                                    <?php else : ?>

                                        <?php
                                        printf(
                                            esc_html__(
                                                'Hasta %d anuncios activos',
                                                'dsm-suscripciones'
                                            ),
                                            $maxActiveAds
                                        );
                                        ?>

                                    <?php endif; ?>
                                </li>

                            <?php endif; ?>

                            <?php if (
                                !empty(
                                    $features['advertising']
                                )
                            ) : ?>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Acceso al servicio de publicidad en banners',
                                        'dsm-suscripciones'
                                    );
                                    ?>
                                </li>

                            <?php endif; ?>

                            <?php if (
                                !empty(
                                    $features['multistore']
                                )
                            ) : ?>

                                <li>
                                    <?php
                                    esc_html_e(
                                        'Acceso a Multitienda',
                                        'dsm-suscripciones'
                                    );
                                    ?>
                                </li>

                            <?php endif; ?>

                        </ul>

                    <?php endif; ?>

                    <?php if (
                        !$plan->isFree()
                        && !(
                            $activeSubscription
                            instanceof Subscription
                        )
                        && is_array(
                            $offerPresentation
                        )
                    ) : ?>

                        <div class="dsm-subscription-offer">

                            <strong class="dsm-subscription-offer__headline">
                                <?php
                                echo esc_html(
                                    (string) (
                                        $offerPresentation[
                                            'headline'
                                        ]
                                        ?? ''
                                    )
                                );
                                ?>
                            </strong>

                            <?php if (
                                trim(
                                    (string) (
                                        $offerPresentation[
                                            'description'
                                        ]
                                        ?? ''
                                    )
                                ) !== ''
                            ) : ?>

                                <span class="dsm-subscription-offer__description">
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $offerPresentation[
                                            'description'
                                        ]
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                    <?php if ($plan->isFree()) : ?>

                        <div
                            class="
                                dsm-account-notice
                                dsm-account-notice--success
                            "
                        >
                            <?php
                            esc_html_e(
                                'Plan base incluido',
                                'dsm-suscripciones'
                            );
                            ?>
                        </div>

                    <?php elseif (
                        $activeSubscription
                        instanceof Subscription
                    ) : ?>

                        <div
                            class="
                                dsm-account-notice
                                dsm-account-notice--success
                            "
                        >
                            <strong>
                                <?php
                                esc_html_e(
                                    'Plan activo',
                                    'dsm-suscripciones'
                                );
                                ?>
                            </strong>

                            <?php if (
                                $activeSubscription
                                    ->getEndsAt()
                                !== null
                            ) : ?>

                                <br>

                                <?php
                                if (
                                    $activeSubscription
                                        ->hasCancellationRequested()
                                ) {
                                    printf(
                                        esc_html__(
                                            'Tu suscripción finalizará el %s. No se realizarán más renovaciones.',
                                            'dsm-suscripciones'
                                        ),
                                        esc_html(
                                            $activeSubscription
                                                ->getEndsAt()
                                                ->setTimezone(
                                                    wp_timezone()
                                                )
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        )
                                    );
                                } else {
                                    printf(
                                        esc_html__(
                                            'Activo hasta el %s.',
                                            'dsm-suscripciones'
                                        ),
                                        esc_html(
                                            $activeSubscription
                                                ->getEndsAt()
                                                ->setTimezone(
                                                    wp_timezone()
                                                )
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        )
                                    );
                                }
                                ?>

                            <?php endif; ?>

                            <?php if (
                                is_array(
                                    $retentionOfferPresentation
                                )
                            ) : ?>

                                <div class="dsm-subscription-offer dsm-subscription-offer--retention">

                                    <strong class="dsm-subscription-offer__headline">
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $retentionOfferPresentation[
                                                'headline'
                                            ]
                                        );
                                        ?>
                                    </strong>

                                    <span class="dsm-subscription-offer__description">
                                        <?php
                                        echo esc_html(
                                            (string)
                                            $retentionOfferPresentation[
                                                'description'
                                            ]
                                        );
                                        ?>
                                    </span>

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
                                            value="dsm_offer_accept_retention"
                                        >

                                        <input
                                            type="hidden"
                                            name="subscription_id"
                                            value="<?php
                                            echo esc_attr(
                                                (string)
                                                $activeSubscription
                                                    ->getId()
                                            );
                                            ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            DSM\Ofertas\Integration\RetentionOfferController::
                                                getNonceAction(
                                                    $activeSubscription
                                                        ->getId()
                                                ),
                                            DSM\Ofertas\Integration\RetentionOfferController::
                                                NONCE_FIELD
                                        );
                                        ?>

                                        <button
                                            type="submit"
                                            class="dsm-button dsm-button--secondary"
                                        >
                                            Aceptar oferta
                                        </button>

                                    </form>

                                </div>

                            <?php endif; ?>

                            <?php if (
                                $activeSubscription
                                    ->isProviderManaged()
                                && $activeSubscription
                                    ->getProvider()
                                    === 'stripe'
                                && $activeSubscription
                                    ->isAutoRenew()
                                && !$activeSubscription
                                    ->hasCancellationRequested()
                            ) : ?>

                                <br>

                                <?php
                                esc_html_e(
                                    'Renovación automática activada.',
                                    'dsm-suscripciones'
                                );
                                ?>

                            <?php endif; ?>

                        </div>

                        <?php if (
                            $activeSubscription
                                ->isProviderManaged()
                            && $activeSubscription
                                ->getProvider()
                                === 'stripe'
                        ) : ?>

                            <?php if (
                                $activeSubscription
                                    ->hasCancellationRequested()
                                && !$activeSubscription
                                    ->isAutoRenew()
                            ) : ?>

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
                                            SubscriptionReactivateController::
                                                ACTION
                                        );
                                        ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="subscription_id"
                                        value="<?php
                                        echo esc_attr(
                                            (string) $activeSubscription
                                                ->getId()
                                        );
                                        ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        SubscriptionReactivateController::
                                            getNonceAction(
                                                $activeSubscription
                                                    ->getId()
                                            ),
                                        SubscriptionReactivateController::
                                            NONCE_FIELD
                                    );
                                    ?>

                                    <button
                                        type="submit"
                                        class="
                                            dsm-button
                                            dsm-button--secondary
                                        "
                                    >
                                        <?php
                                        esc_html_e(
                                            'Reactivar renovación',
                                            'dsm-suscripciones'
                                        );
                                        ?>
                                    </button>
                                </form>

                            <?php elseif (
                                $activeSubscription
                                    ->isAutoRenew()
                            ) : ?>

                                <form
                                    method="post"
                                    action="<?php
                                    echo esc_url(
                                        admin_url(
                                            'admin-post.php'
                                        )
                                    );
                                    ?>"
                                    onsubmit="return confirm(
                                        '¿Quieres cancelar la renovación automática? Seguirás teniendo acceso hasta que termine el período que ya has pagado.'
                                    );"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="<?php
                                        echo esc_attr(
                                            SubscriptionCancelController::
                                                ACTION
                                        );
                                        ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="subscription_id"
                                        value="<?php
                                        echo esc_attr(
                                            (string) $activeSubscription
                                                ->getId()
                                        );
                                        ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        SubscriptionCancelController::
                                            getNonceAction(
                                                $activeSubscription
                                                    ->getId()
                                            ),
                                        SubscriptionCancelController::
                                            NONCE_FIELD
                                    );
                                    ?>

                                    <button
                                        type="submit"
                                        class="
                                            dsm-button
                                            dsm-button--danger
                                        "
                                    >
                                        <?php
                                        esc_html_e(
                                            'Cancelar renovación',
                                            'dsm-suscripciones'
                                        );
                                        ?>
                                    </button>
                                </form>

                            <?php endif; ?>

                        <?php endif; ?>

                    <?php elseif ($customerId <= 0) : ?>

                        <a
                            class="
                                dsm-button
                                dsm-button--primary
                            "
                            href="<?php
                            echo esc_url(
                                add_query_arg(
                                    [
                                        'redirect_to' =>
                                            home_url(
                                                '/suscripciones/'
                                            ),
                                    ],
                                    home_url(
                                        '/iniciar-sesion/'
                                    )
                                )
                            );
                            ?>"
                        >
                            <?php
                            esc_html_e(
                                'Iniciar sesión para contratar',
                                'dsm-suscripciones'
                            );
                            ?>
                        </a>

                    <?php else : ?>

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
                                    SubscriptionPurchaseController::
                                        ACTION
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="plan_id"
                                value="<?php
                                echo esc_attr(
                                    (string) $plan
                                        ->getId()
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                SubscriptionPurchaseController::
                                    getNonceAction(
                                        $plan->getId()
                                    ),
                                SubscriptionPurchaseController::
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
                                <?php if (
                                    $customCtaLabel !== ''
                                ) : ?>

                                    <?php
                                    echo esc_html(
                                        $customCtaLabel
                                    );
                                    ?>

                                <?php elseif (
                                    is_array(
                                        $offerPresentation
                                    )
                                    && (
                                        $offerPresentation[
                                            'benefit_type'
                                        ]
                                        ?? ''
                                    ) === 'free_months'
                                ) : ?>

                                    <?php
                                    $freeMonths =
                                        max(
                                            1,
                                            (int) (
                                                $offerPresentation[
                                                    'benefit_value'
                                                ]
                                                ?? 1
                                            )
                                        );

                                    printf(
                                        esc_html__(
                                            'Empezar %1$d %2$s gratis',
                                            'dsm-suscripciones'
                                        ),
                                        $freeMonths,
                                        $freeMonths === 1
                                            ? esc_html__(
                                                'mes',
                                                'dsm-suscripciones'
                                            )
                                            : esc_html__(
                                                'meses',
                                                'dsm-suscripciones'
                                            )
                                    );
                                    ?>

                                <?php elseif (
                                    is_array(
                                        $offerPresentation
                                    )
                                ) : ?>

                                    <?php
                                    printf(
                                        esc_html__(
                                            'Contratar por %1$s %2$s',
                                            'dsm-suscripciones'
                                        ),
                                        esc_html(
                                            number_format_i18n(
                                                (float) (
                                                    $offerPresentation[
                                                        'promotional_price'
                                                    ]
                                                    ?? $plan
                                                        ->getPrice()
                                                ),
                                                2
                                            )
                                        ),
                                        esc_html(
                                            $plan->getCurrency()
                                        )
                                    );
                                    ?>

                                <?php else : ?>

                                    <?php
                                    printf(
                                        esc_html__(
                                            'Contratar por %1$s %2$s',
                                            'dsm-suscripciones'
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

                                <?php endif; ?>
                            </button>

                        </form>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>
