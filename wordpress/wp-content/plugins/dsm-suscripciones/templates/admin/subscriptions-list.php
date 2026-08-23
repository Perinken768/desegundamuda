<?php

declare(strict_types=1);

use DSM\Clientes\Customer\Customer;
use DSM\Suscripciones\Admin\SubscriptionsPage;
use DSM\Suscripciones\Subscription\Subscription;
use DSM\Suscripciones\Subscription\SubscriptionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, array{
 *     subscription: Subscription,
 *     plan: ?SubscriptionPlan,
 *     customer: ?Customer
 * }> $rows
 * @var string $notice
 * @var string $error
 */

$statusLabels = [
    'active' =>
        'Activa',

    'pending' =>
        'Pendiente',

    'cancelled' =>
        'Cancelada',

    'expired' =>
        'Caducada',
];

?>

<div class="wrap">

    <h1>
        <?php
        esc_html_e(
            'Suscripciones',
            'dsm-suscripciones'
        );
        ?>
    </h1>

    <p>
        <a
            class="button button-primary"
            href="<?php
            echo esc_url(
                admin_url(
                    'admin.php?page=dsm-suscripciones-grant'
                )
            );
            ?>"
        >
            <?php
            esc_html_e(
                'Conceder suscripción',
                'dsm-suscripciones'
            );
            ?>
        </a>
    </p>

    <?php if ($notice === 'cancelled') : ?>

        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                esc_html_e(
                    'La suscripción se canceló correctamente.',
                    'dsm-suscripciones'
                );
                ?>
            </p>
        </div>

    <?php endif; ?>

    <?php if ($error !== '') : ?>

        <div class="notice notice-error">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>

    <?php endif; ?>

    <?php if ($rows === []) : ?>

        <p>
            <?php
            esc_html_e(
                'Todavía no hay suscripciones registradas.',
                'dsm-suscripciones'
            );
            ?>
        </p>

    <?php else : ?>

        <div
            style="
                width: 100%;
                overflow-x: auto;
            "
        >

            <table
                class="widefat striped"
                style="
                    min-width: 1100px;
                "
            >

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Origen</th>
                        <th>Importe</th>
                        <th>Renovación</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($rows as $row) : ?>

                        <?php
                        $subscription =
                            $row['subscription'];

                        $plan =
                            $row['plan'];

                        $customer =
                            $row['customer'];

                        $status =
                            $subscription
                                ->getStatus();

                        $statusLabel =
                            $statusLabels[$status]
                            ?? $status;

                        $startsAt =
                            $subscription
                                ->getStartsAt()
                                ->setTimezone(
                                    wp_timezone()
                                );

                        $endsAt =
                            $subscription
                                ->getEndsAt();

                        if ($endsAt !== null) {
                            $endsAt =
                                $endsAt
                                    ->setTimezone(
                                        wp_timezone()
                                    );
                        }
                        ?>

                        <tr>

                            <td>
                                #<?php
                                echo esc_html(
                                    (string) $subscription
                                        ->getId()
                                );
                                ?>
                            </td>

                            <td>

                                <?php if (
                                    $customer !== null
                                ) : ?>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $customer
                                                ->getEmail()
                                        );
                                        ?>
                                    </strong>

                                    <br>

                                    <small>
                                        Cliente #<?php
                                        echo esc_html(
                                            (string) $customer
                                                ->getId()
                                        );
                                        ?>
                                    </small>

                                <?php else : ?>

                                    Cliente #<?php
                                    echo esc_html(
                                        (string) $subscription
                                            ->getCustomerId()
                                    );
                                    ?>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if (
                                    $plan !== null
                                ) : ?>

                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $plan
                                                ->getName()
                                        );
                                        ?>
                                    </strong>

                                    <br>

                                    <code>
                                        <?php
                                        echo esc_html(
                                            $plan
                                                ->getCode()
                                        );
                                        ?>
                                    </code>

                                <?php else : ?>

                                    Plan #<?php
                                    echo esc_html(
                                        (string) $subscription
                                            ->getPlanId()
                                    );
                                    ?>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $statusLabel
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $startsAt
                                        ->format(
                                            'd/m/Y H:i'
                                        )
                                );
                                ?>
                            </td>

                            <td>

                                <?php if (
                                    $endsAt !== null
                                ) : ?>

                                    <?php
                                    echo esc_html(
                                        $endsAt
                                            ->format(
                                                'd/m/Y H:i'
                                            )
                                    );
                                    ?>

                                <?php else : ?>

                                    <?php
                                    esc_html_e(
                                        'Sin fecha',
                                        'dsm-suscripciones'
                                    );
                                    ?>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php
                                $sourceType =
                                    $subscription
                                        ->getSourceType();

                                if (
                                    $sourceType
                                    === 'admin_grant'
                                ) {
                                    esc_html_e(
                                        'Concesión administrativa',
                                        'dsm-suscripciones'
                                    );
                                } elseif (
                                    $sourceType
                                    === 'payment'
                                ) {
                                    esc_html_e(
                                        'Pago',
                                        'dsm-suscripciones'
                                    );
                                } else {
                                    echo esc_html(
                                        $sourceType
                                        ?? '—'
                                    );
                                }
                                ?>

                                <?php if (
                                    $subscription
                                        ->getSourceReference()
                                    !== null
                                ) : ?>

                                    <br>

                                    <small>
                                        <?php
                                        echo esc_html(
                                            $subscription
                                                ->getSourceReference()
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if (
                                    $subscription
                                        ->getPricePaid()
                                    !== null
                                ) : ?>

                                    <?php
                                    echo esc_html(
                                        number_format(
                                            $subscription
                                                ->getPricePaid(),
                                            2,
                                            ',',
                                            '.'
                                        )
                                    );
                                    ?>

                                    <?php
                                    echo esc_html(
                                        $subscription
                                            ->getCurrency()
                                        ?? ''
                                    );
                                    ?>

                                <?php else : ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td>
                                <?php
                                echo $subscription
                                    ->isAutoRenew()
                                    ? esc_html__(
                                        'Sí',
                                        'dsm-suscripciones'
                                    )
                                    : esc_html__(
                                        'No',
                                        'dsm-suscripciones'
                                    );
                                ?>
                            </td>

                            <td>

                                <?php if (
                                    $subscription
                                        ->getStatus()
                                    === 'active'
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
                                                SubscriptionsPage::
                                                    getCancelAction()
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="subscription_id"
                                            value="<?php
                                            echo esc_attr(
                                                (string) $subscription
                                                    ->getId()
                                            );
                                            ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            SubscriptionsPage::
                                                getNonceAction(
                                                    $subscription
                                                        ->getId()
                                                ),
                                            SubscriptionsPage::
                                                getNonceName()
                                        );
                                        ?>

                                        <button
                                            type="submit"
                                            class="button button-secondary"
                                        >
                                            <?php
                                            esc_html_e(
                                                'Cancelar',
                                                'dsm-suscripciones'
                                            );
                                            ?>
                                        </button>

                                    </form>

                                <?php else : ?>

                                    —

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>