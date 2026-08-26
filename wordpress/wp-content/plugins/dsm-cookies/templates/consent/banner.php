<?php

declare(strict_types=1);

use DSM\Cookies\Consent\Consent;
use DSM\Cookies\Frontend\ConsentController;

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Variables proporcionadas por TemplateRenderer:
 *
 * @var Consent|null $consent
 * @var bool $hasConsent
 * @var string $cookiePolicyUrl
 */

$currentUrl =
    home_url(
        wp_unslash(
            (string) (
                $_SERVER[
                    'REQUEST_URI'
                ]
                ?? '/'
            )
        )
    );
?>

<div
    class="dsm-cookie-consent"
    data-dsm-cookie-consent
    <?php if ($hasConsent) : ?>
        hidden
    <?php endif; ?>
>

    <div
        class="dsm-cookie-consent__backdrop"
        aria-hidden="true"
    ></div>

    <section
        class="dsm-cookie-consent__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dsm-cookie-title"
    >

        <div class="dsm-cookie-consent__main">

            <h2
                id="dsm-cookie-title"
                class="dsm-cookie-consent__title"
            >
                Tu privacidad importa
            </h2>

            <p class="dsm-cookie-consent__description">
                Utilizamos cookies necesarias para que
                DeSegundaMuda funcione correctamente.
                También puedes permitir cookies de
                preferencias, analítica y marketing.
            </p>

            <p class="dsm-cookie-consent__description">
                Puedes cambiar tu decisión posteriormente
                desde la configuración de cookies.
            </p>

            <?php if ($cookiePolicyUrl !== '') : ?>

                <p class="dsm-cookie-consent__legal">
                    <a
                        href="<?php
                        echo esc_url(
                            $cookiePolicyUrl
                        );
                        ?>"
                    >
                        Consultar la política de cookies
                    </a>
                </p>

            <?php endif; ?>

        </div>


        <form
            class="dsm-cookie-consent__form"
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
                    ConsentController::ACTION
                );
                ?>"
            >

            <input
                type="hidden"
                name="redirect_to"
                value="<?php
                echo esc_attr(
                    $currentUrl
                );
                ?>"
            >

            <?php
            wp_nonce_field(
                ConsentController::NONCE_ACTION,
                ConsentController::NONCE_FIELD
            );
            ?>


            <div
                class="dsm-cookie-consent__preferences"
                data-dsm-cookie-preferences
                hidden
            >

                <div class="dsm-cookie-consent__category">

                    <div>
                        <strong>
                            Necesarias
                        </strong>

                        <p>
                            Imprescindibles para el
                            funcionamiento y la seguridad
                            de la web.
                        </p>
                    </div>

                    <input
                        type="checkbox"
                        checked
                        disabled
                        aria-label="Cookies necesarias activadas"
                    >

                </div>


                <label class="dsm-cookie-consent__category">

                    <div>
                        <strong>
                            Preferencias
                        </strong>

                        <p>
                            Permiten recordar elecciones
                            y personalizar la experiencia.
                        </p>
                    </div>

                    <input
                        type="checkbox"
                        name="preferences"
                        value="1"
                        <?php
                        checked(
                            $consent?->allowsPreferences()
                            ?? false
                        );
                        ?>
                    >

                </label>


                <label class="dsm-cookie-consent__category">

                    <div>
                        <strong>
                            Analítica
                        </strong>

                        <p>
                            Nos ayudan a entender cómo se
                            utiliza DeSegundaMuda.
                        </p>
                    </div>

                    <input
                        type="checkbox"
                        name="analytics"
                        value="1"
                        <?php
                        checked(
                            $consent?->allowsAnalytics()
                            ?? false
                        );
                        ?>
                    >

                </label>


                <label class="dsm-cookie-consent__category">

                    <div>
                        <strong>
                            Marketing
                        </strong>

                        <p>
                            Permiten medir campañas y
                            mostrar publicidad relevante.
                        </p>
                    </div>

                    <input
                        type="checkbox"
                        name="marketing"
                        value="1"
                        <?php
                        checked(
                            $consent?->allowsMarketing()
                            ?? false
                        );
                        ?>
                    >

                </label>

            </div>


            <div class="dsm-cookie-consent__actions">

                <button
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                    type="submit"
                    name="consent_action"
                    value="accept_all"
                >
                    Aceptar todas
                </button>

                <button
                    class="
                        dsm-button
                        dsm-button--secondary
                    "
                    type="submit"
                    name="consent_action"
                    value="reject_optional"
                >
                    Rechazar opcionales
                </button>

                <button
                    class="
                        dsm-button
                        dsm-button--secondary
                    "
                    type="button"
                    data-dsm-cookie-configure
                >
                    Configurar
                </button>

                <button
                    class="
                        dsm-button
                        dsm-button--primary
                        dsm-cookie-consent__save
                    "
                    type="submit"
                    name="consent_action"
                    value="save_preferences"
                    data-dsm-cookie-save
                    hidden
                >
                    Guardar preferencias
                </button>

            </div>

        </form>

    </section>

</div>
