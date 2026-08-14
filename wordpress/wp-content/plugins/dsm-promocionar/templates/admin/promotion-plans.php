<?php

declare(strict_types=1);

use DSM\Promocionar\Promotion\PromotionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, PromotionPlan> $plans
 * @var string $notice
 * @var string $error
 */

?>

<div class="wrap">
    <h1>
        <?php
        esc_html_e(
            'Planes de promoción',
            'dsm-promocionar'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        esc_html_e(
            'Configura la duración, precio, moneda, estado y orden de los planes disponibles para promocionar anuncios.',
            'dsm-promocionar'
        );
        ?>
    </p>

    <hr class="wp-header-end">

    <?php if ($notice === 'plan_updated'): ?>
        <div
            class="notice notice-success is-dismissible"
        >
            <p>
                <?php
                esc_html_e(
                    'El plan de promoción se ha actualizado correctamente.',
                    'dsm-promocionar'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div
            class="notice notice-error"
        >
            <p>
                <?php
                echo esc_html(
                    $error
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($plans === []): ?>
        <div class="notice notice-warning">
            <p>
                <?php
                esc_html_e(
                    'No existen planes de promoción configurados.',
                    'dsm-promocionar'
                );
                ?>
            </p>
        </div>
    <?php else: ?>

        <div
            style="
                display: grid;
                grid-template-columns:
                    repeat(
                        auto-fit,
                        minmax(320px, 1fr)
                    );
                gap: 20px;
                margin-top: 20px;
                max-width: 1200px;
            "
        >
            <?php foreach ($plans as $plan): ?>

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

                <div
                    class="postbox"
                    style="
                        margin: 0;
                        padding: 0;
                    "
                >
                    <div
                        class="postbox-header"
                    >
                        <h2
                            class="hndle"
                            style="padding: 0 12px;"
                        >
                            <?php
                            echo esc_html(
                                $plan->getName()
                            );
                            ?>
                        </h2>
                    </div>

                    <div
                        class="inside"
                        style="padding: 16px;"
                    >
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
                                value="dsm_promotion_plan_save"
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
                                'dsm_promotion_plan_save',
                                'dsm_promotion_plan_nonce'
                            );
                            ?>

                            <table class="form-table">
                                <tbody>

                                    <tr>
                                        <th scope="row">
                                            <?php
                                            esc_html_e(
                                                'Código',
                                                'dsm-promocionar'
                                            );
                                            ?>
                                        </th>

                                        <td>
                                            <code>
                                                <?php
                                                echo esc_html(
                                                    $plan->getCode()
                                                );
                                                ?>
                                            </code>

                                            <p class="description">
                                                <?php
                                                esc_html_e(
                                                    'Referencia interna. No se modifica.',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label
                                                for="dsm-plan-name-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Nombre',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="text"
                                                id="dsm-plan-name-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                                name="name"
                                                class="regular-text"
                                                value="<?php
                                                echo esc_attr(
                                                    $plan->getName()
                                                );
                                                ?>"
                                                maxlength="120"
                                                required
                                            >
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label
                                                for="dsm-plan-duration-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Duración',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="number"
                                                id="dsm-plan-duration-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                                name="duration_days"
                                                value="<?php
                                                echo esc_attr(
                                                    (string) $durationDays
                                                );
                                                ?>"
                                                min="1"
                                                step="1"
                                                required
                                            >

                                            <?php
                                            esc_html_e(
                                                'días',
                                                'dsm-promocionar'
                                            );
                                            ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label
                                                for="dsm-plan-price-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Precio',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="number"
                                                id="dsm-plan-price-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                                name="price"
                                                value="<?php
                                                echo esc_attr(
                                                    number_format(
                                                        $plan->getPrice(),
                                                        2,
                                                        '.',
                                                        ''
                                                    )
                                                );
                                                ?>"
                                                min="0"
                                                step="0.01"
                                                required
                                            >
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label
                                                for="dsm-plan-currency-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Moneda',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="text"
                                                id="dsm-plan-currency-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                                name="currency"
                                                value="<?php
                                                echo esc_attr(
                                                    $plan->getCurrency()
                                                );
                                                ?>"
                                                maxlength="3"
                                                size="5"
                                                required
                                            >
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label
                                                for="dsm-plan-order-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                            >
                                                <?php
                                                esc_html_e(
                                                    'Orden',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="number"
                                                id="dsm-plan-order-<?php
                                                echo esc_attr(
                                                    (string) $plan->getId()
                                                );
                                                ?>"
                                                name="sort_order"
                                                value="<?php
                                                echo esc_attr(
                                                    (string) $plan->getSortOrder()
                                                );
                                                ?>"
                                                min="0"
                                                step="1"
                                            >
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <?php
                                            esc_html_e(
                                                'Disponible',
                                                'dsm-promocionar'
                                            );
                                            ?>
                                        </th>

                                        <td>
                                            <label>
                                                <input
                                                    type="checkbox"
                                                    name="is_active"
                                                    value="1"
                                                    <?php
                                                    checked(
                                                        $plan->isActive()
                                                    );
                                                    ?>
                                                >

                                                <?php
                                                esc_html_e(
                                                    'Permitir que este plan pueda comprarse.',
                                                    'dsm-promocionar'
                                                );
                                                ?>
                                            </label>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>

                            <?php
                            submit_button(
                                __(
                                    'Guardar plan',
                                    'dsm-promocionar'
                                )
                            );
                            ?>
                        </form>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>
