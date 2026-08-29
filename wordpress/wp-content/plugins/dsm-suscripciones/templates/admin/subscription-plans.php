<?php

declare(strict_types=1);

use DSM\Suscripciones\Admin\SubscriptionPlansPage;
use DSM\Suscripciones\Subscription\SubscriptionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, SubscriptionPlan> $plans
 * @var array<string, string> $pageCopy
 * @var array<int, array<string, mixed>> $planCopies
 * @var string $notice
 * @var string $error
 */

?>

<div class="wrap">

    <h1>
        Planes
    </h1>

    <p class="description">
        Modifica los textos comerciales que verá el cliente
        en la página de suscripciones.
    </p>

    <hr class="wp-header-end">


    <?php if ($notice === 'page-updated') : ?>

        <div class="notice notice-success is-dismissible">
            <p>
                Los textos generales se actualizaron correctamente.
            </p>
        </div>

    <?php elseif ($notice === 'plan-updated') : ?>

        <div class="notice notice-success is-dismissible">
            <p>
                El plan se actualizó correctamente.
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


    <div
        style="
            max-width:1000px;
            margin-top:24px;
        "
    >

        <div
            style="
                background:#fff;
                border:1px solid #dcdcde;
                padding:24px;
                margin-bottom:24px;
            "
        >

            <h2>
                Textos generales
            </h2>

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
                        SubscriptionPlansPage::
                            SAVE_PAGE_COPY_ACTION
                    );
                    ?>"
                >

                <?php
                wp_nonce_field(
                    SubscriptionPlansPage::
                        SAVE_PAGE_COPY_ACTION,
                    SubscriptionPlansPage::
                        NONCE_NAME
                );
                ?>

                <table class="form-table">

                    <tr>
                        <th>
                            <label for="dsm-page-title">
                                Título
                            </label>
                        </th>

                        <td>
                            <input
                                id="dsm-page-title"
                                type="text"
                                class="regular-text"
                                name="page_title"
                                value="<?php
                                echo esc_attr(
                                    $pageCopy['title']
                                );
                                ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for="dsm-page-description">
                                Texto introductorio
                            </label>
                        </th>

                        <td>
                            <textarea
                                id="dsm-page-description"
                                class="large-text"
                                rows="3"
                                name="page_description"
                            ><?php
                            echo esc_textarea(
                                $pageCopy[
                                    'description'
                                ]
                            );
                            ?></textarea>
                        </td>
                    </tr>

                </table>

                <?php
                submit_button(
                    'Guardar textos generales'
                );
                ?>

            </form>

        </div>


        <?php foreach ($plans as $plan) : ?>

            <?php
            $copy =
                $planCopies[
                    $plan->getId()
                ]
                ?? [
                    'benefits' =>
                        [],

                    'cta_label' =>
                        '',
                ];

            $benefits =
                is_array(
                    $copy['benefits']
                    ?? null
                )
                    ? $copy['benefits']
                    : [];
            ?>

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:24px;
                    margin-bottom:24px;
                "
            >

                <h2>
                    <?php
                    echo esc_html(
                        $plan->getName()
                    );
                    ?>

                    <code>
                        <?php
                        echo esc_html(
                            $plan->getCode()
                        );
                        ?>
                    </code>
                </h2>

                <p>
                    <strong>
                        Precio técnico:
                    </strong>

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
                            SubscriptionPlansPage::
                                SAVE_ACTION
                        );
                        ?>"
                    >

                    <input
                        type="hidden"
                        name="plan_id"
                        value="<?php
                        echo esc_attr(
                            (string)
                            $plan->getId()
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        SubscriptionPlansPage::
                            getSaveNonceAction(
                                $plan->getId()
                            ),
                        SubscriptionPlansPage::
                            NONCE_NAME
                    );
                    ?>

                    <table class="form-table">

                        <tr>
                            <th>
                                Nombre
                            </th>

                            <td>
                                <input
                                    type="text"
                                    class="regular-text"
                                    name="name"
                                    value="<?php
                                    echo esc_attr(
                                        $plan->getName()
                                    );
                                    ?>"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Descripción
                            </th>

                            <td>
                                <textarea
                                    class="large-text"
                                    name="description"
                                    rows="3"
                                ><?php
                                echo esc_textarea(
                                    $plan->getDescription()
                                    ?? ''
                                );
                                ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Ventajas visibles
                            </th>

                            <td>
                                <textarea
                                    class="large-text"
                                    name="benefits"
                                    rows="6"
                                    placeholder="Una ventaja por línea"
                                ><?php
                                echo esc_textarea(
                                    implode(
                                        "\n",
                                        $benefits
                                    )
                                );
                                ?></textarea>

                                <p class="description">
                                    Escribe una ventaja por línea.
                                    Estas frases son únicamente comerciales
                                    y no modifican los permisos reales del plan.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Texto del botón
                            </th>

                            <td>
                                <input
                                    type="text"
                                    class="regular-text"
                                    name="cta_label"
                                    value="<?php
                                    echo esc_attr(
                                        (string) (
                                            $copy[
                                                'cta_label'
                                            ]
                                            ?? ''
                                        )
                                    );
                                    ?>"
                                    placeholder="Ej. Contratar Publicidad"
                                >

                                <p class="description">
                                    Déjalo vacío para utilizar el texto
                                    automático de contratación.
                                </p>
                            </td>
                        </tr>

                    </table>

                    <?php
                    submit_button(
                        'Guardar plan'
                    );
                    ?>

                </form>

            </div>

        <?php endforeach; ?>

    </div>

</div>
