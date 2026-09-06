<?php

declare(strict_types=1);

use DSM\Directos\Admin\DirectSettingsPage;

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Variables disponibles:
 *
 * $plans
 * $notice
 * $error
 */
?>

<div class="wrap dsm-directos-admin">

    <h1>
        <?php
        echo esc_html__(
            'DSM Directos',
            'dsm-directos'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        echo esc_html__(
            'Configura qué planes de suscripción incluyen acceso a DSM Directos.',
            'dsm-directos'
        );
        ?>
    </p>

    <?php if ($notice === 'saved') : ?>

        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                echo esc_html__(
                    'La configuración de DSM Directos se ha guardado correctamente.',
                    'dsm-directos'
                );
                ?>
            </p>
        </div>

    <?php endif; ?>

    <?php if ($error !== '') : ?>

        <div class="notice notice-error">
            <p>
                <?php
                echo esc_html(
                    $error
                );
                ?>
            </p>
        </div>

    <?php endif; ?>


    <div
        style="
            max-width: 900px;
            margin-top: 24px;
            padding: 24px;
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
        "
    >

        <h2 style="margin-top: 0;">
            <?php
            echo esc_html__(
                'Planes con acceso a Directos',
                'dsm-directos'
            );
            ?>
        </h2>

        <p>
            <?php
            echo esc_html__(
                'Selecciona los planes que deben incluir esta funcionalidad. Los cambios se aplican mediante la prestación directos del sistema de suscripciones.',
                'dsm-directos'
            );
            ?>
        </p>

        <form
            method="post"
            action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        >

            <input
                type="hidden"
                name="action"
                value="<?php
                echo esc_attr(
                    DirectSettingsPage::getSaveAction()
                );
                ?>"
            >

            <?php
            wp_nonce_field(
                DirectSettingsPage::getNonceAction(),
                DirectSettingsPage::getNonceName()
            );
            ?>

            <table
                class="widefat striped"
                style="margin-top: 20px;"
            >
                <thead>
                    <tr>
                        <th style="width: 80px;">
                            <?php
                            echo esc_html__(
                                'Activo',
                                'dsm-directos'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            echo esc_html__(
                                'Plan',
                                'dsm-directos'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            echo esc_html__(
                                'Código',
                                'dsm-directos'
                            );
                            ?>
                        </th>

                        <th>
                            <?php
                            echo esc_html__(
                                'Estado',
                                'dsm-directos'
                            );
                            ?>
                        </th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($plans === []) : ?>

                    <tr>
                        <td colspan="4">
                            <?php
                            echo esc_html__(
                                'No existen planes de suscripción.',
                                'dsm-directos'
                            );
                            ?>
                        </td>
                    </tr>

                <?php else : ?>

                    <?php foreach ($plans as $plan) : ?>

                        <?php
                        $hasDirectAccess =
                            $plan->hasFeature(
                                'directos'
                            )
                            && !in_array(
                                strtolower(
                                    trim(
                                        (string) $plan->getFeature(
                                            'directos',
                                            '0'
                                        )
                                    )
                                ),
                                [
                                    '',
                                    '0',
                                    'false',
                                    'no',
                                    'off',
                                ],
                                true
                            );
                        ?>

                        <tr>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="direct_plan_ids[]"
                                        value="<?php
                                        echo esc_attr(
                                            (string) $plan->getId()
                                        );
                                        ?>"
                                        <?php
                                        checked(
                                            $hasDirectAccess
                                        );
                                        ?>
                                    >
                                </label>
                            </td>

                            <td>
                                <strong>
                                    <?php
                                    echo esc_html(
                                        $plan->getName()
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <code>
                                    <?php
                                    echo esc_html(
                                        $plan->getCode()
                                    );
                                    ?>
                                </code>
                            </td>

                            <td>
                                <?php if ($plan->isActive()) : ?>

                                    <span style="color: #008a20;">
                                        <?php
                                        echo esc_html__(
                                            'Activo',
                                            'dsm-directos'
                                        );
                                        ?>
                                    </span>

                                <?php else : ?>

                                    <span style="color: #646970;">
                                        <?php
                                        echo esc_html__(
                                            'Inactivo',
                                            'dsm-directos'
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

            <?php
            submit_button(
                __(
                    'Guardar configuración',
                    'dsm-directos'
                )
            );
            ?>

        </form>

    </div>

</div>
