<?php

declare(strict_types=1);

use DSM\Suscripciones\Admin\SubscriptionPlansPage;
use DSM\Suscripciones\Frontend\SubscriptionPurchaseController;
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
        $status === 'purchase_error'
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
                                ?>

                            <?php endif; ?>

                        </div>

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
