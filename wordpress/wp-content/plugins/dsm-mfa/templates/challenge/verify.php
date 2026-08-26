<?php

declare(strict_types=1);

use DSM\Mfa\Challenge\MfaChallenge;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var string $token
 * @var MfaChallenge|null $challenge
 * @var string $redirectTo
 * @var string $error
 * @var bool $sent
 */

get_header();
?>

<main class="dsm-site-main dsm-mfa">

    <div class="dsm-container">

        <section class="dsm-mfa-challenge">

            <header class="dsm-mfa-challenge__header">

                <h1 class="dsm-mfa-challenge__title">
                    <?php
                    esc_html_e(
                        'Verifica tu acceso',
                        'dsm-mfa'
                    );
                    ?>
                </h1>

                <?php if ($challenge !== null) : ?>

                    <p class="dsm-mfa-challenge__description">
                        <?php
                        esc_html_e(
                            'Hemos enviado un código de 6 dígitos a tu correo electrónico.',
                            'dsm-mfa'
                        );
                        ?>
                    </p>

                <?php endif; ?>

            </header>


            <?php if ($challenge === null) : ?>

                <div class="dsm-mfa-challenge__message dsm-mfa-challenge__message--error">

                    <p>
                        <?php
                        esc_html_e(
                            'Este código de verificación ya no está disponible. Vuelve a iniciar sesión para solicitar uno nuevo.',
                            'dsm-mfa'
                        );
                        ?>
                    </p>

                    <a
                        href="<?php echo esc_url(home_url('/iniciar-sesion/')); ?>"
                    >
                        <?php
                        esc_html_e(
                            'Volver a iniciar sesión',
                            'dsm-mfa'
                        );
                        ?>
                    </a>

                </div>

            <?php else : ?>


                <?php if ($sent) : ?>

                    <div class="dsm-mfa-challenge__message dsm-mfa-challenge__message--success">

                        <?php
                        esc_html_e(
                            'Te hemos enviado un nuevo código de verificación.',
                            'dsm-mfa'
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <?php if ($error === 'invalid_code') : ?>

                    <div class="dsm-mfa-challenge__message dsm-mfa-challenge__message--error">

                        <?php
                        esc_html_e(
                            'El código introducido no es correcto.',
                            'dsm-mfa'
                        );
                        ?>

                    </div>

                <?php elseif ($error === 'challenge_error') : ?>

                    <div class="dsm-mfa-challenge__message dsm-mfa-challenge__message--error">

                        <?php
                        esc_html_e(
                            'No hemos podido completar la verificación. Vuelve a iniciar sesión.',
                            'dsm-mfa'
                        );
                        ?>

                    </div>

                <?php elseif ($error === 'resend_error') : ?>

                    <div class="dsm-mfa-challenge__message dsm-mfa-challenge__message--error">

                        <?php
                        esc_html_e(
                            'No se ha podido enviar otro código. Puede que debas esperar un minuto o que hayas alcanzado el máximo de reenvíos.',
                            'dsm-mfa'
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <form
                    class="dsm-mfa-challenge__form"
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="dsm_mfa_verify"
                    >

                    <input
                        type="hidden"
                        name="token"
                        value="<?php echo esc_attr($token); ?>"
                    >

                    <input
                        type="hidden"
                        name="redirect_to"
                        value="<?php echo esc_attr($redirectTo); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_mfa_verify_'
                        . $token,
                        'dsm_mfa_nonce'
                    );
                    ?>

                    <label
                        for="dsm-mfa-code"
                        class="dsm-mfa-challenge__label"
                    >
                        <?php
                        esc_html_e(
                            'Código de verificación',
                            'dsm-mfa'
                        );
                        ?>
                    </label>

                    <input
                        id="dsm-mfa-code"
                        class="dsm-mfa-challenge__code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        minlength="6"
                        required
                        autofocus
                    >

                    <button
                        class="dsm-mfa-challenge__submit"
                        type="submit"
                    >
                        <?php
                        esc_html_e(
                            'Verificar acceso',
                            'dsm-mfa'
                        );
                        ?>
                    </button>

                </form>


                <form
                    class="dsm-mfa-challenge__resend"
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="dsm_mfa_resend"
                    >

                    <input
                        type="hidden"
                        name="token"
                        value="<?php echo esc_attr($token); ?>"
                    >

                    <input
                        type="hidden"
                        name="redirect_to"
                        value="<?php echo esc_attr($redirectTo); ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_mfa_resend_'
                        . $token,
                        'dsm_mfa_resend_nonce'
                    );
                    ?>

                    <button
                        class="dsm-mfa-challenge__resend-button"
                        type="submit"
                    >
                        <?php
                        esc_html_e(
                            'Reenviar código',
                            'dsm-mfa'
                        );
                        ?>
                    </button>

                </form>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php
get_footer();
