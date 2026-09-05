<?php

declare(strict_types=1);

use DSM\Pagos\Frontend\PaymentCheckoutController;
use DSM\Pagos\Payment\Payment;
use DSM\Pagos\Provider\PaymentProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Payment $payment
 * @var int $advertisementId
 * @var array<string, PaymentProvider> $providers
 */

/*
 * ============================================================
 * NOMBRE COMERCIAL DEL CONCEPTO
 * ============================================================
 *
 * Los códigos internos del pago se conservan en base de datos,
 * pero nunca deben mostrarse directamente al cliente.
 */

$paymentReference =
    sanitize_key(
        (string) (
            $payment->getSourceReference()
            ?? ''
        )
    );

$paymentPurpose =
    sanitize_key(
        $payment->getPurpose()
    );

$conceptLabels = [
    /*
     * Promociones.
     */
    'promotion_3_days' =>
        __(
            'Promoción 3 días',
            'dsm-pagos'
        ),

    'promotion_5_days' =>
        __(
            'Promoción 5 días',
            'dsm-pagos'
        ),

    'promotion_7_days' =>
        __(
            'Promoción 7 días',
            'dsm-pagos'
        ),

    /*
     * Suscripciones.
     */
    'ads_50' =>
        __(
            '50 anuncios',
            'dsm-pagos'
        ),

    'advertising' =>
        __(
            'Publicidad',
            'dsm-pagos'
        ),

    'multistore' =>
        __(
            'Multitienda',
            'dsm-pagos'
        ),
];

$paymentConcept =
    $conceptLabels[$paymentReference]
    ?? match ($paymentPurpose) {
        'promotion' =>
            __(
                'Promoción de anuncio',
                'dsm-pagos'
            ),

        'subscription' =>
            __(
                'Suscripción',
                'dsm-pagos'
            ),

        default =>
            __(
                'Compra en DeSegundaMuda',
                'dsm-pagos'
            ),
    };

?>

<section class="dsm-payment-checkout">

    <header class="dsm-account-section-header">
        <div class="dsm-account-section-header__content">
            <h1>
                <?php
                esc_html_e(
                    'Finalizar compra',
                    'dsm-pagos'
                );
                ?>
            </h1>

            <p>
                <?php
                esc_html_e(
                    'Revisa los datos del pago antes de continuar.',
                    'dsm-pagos'
                );
                ?>
            </p>
        </div>
    </header>

    <article class="dsm-card">
        <h2>
            <?php
            esc_html_e(
                'Resumen del pago',
                'dsm-pagos'
            );
            ?>
        </h2>

        <dl class="dsm-definition-list">

            <div>
                <dt>
                    <?php
                    esc_html_e(
                        'Pago',
                        'dsm-pagos'
                    );
                    ?>
                </dt>

                <dd>
                    #<?php
                    echo esc_html(
                        (string) $payment->getId()
                    );
                    ?>
                </dd>
            </div>

            <div>
                <dt>
                    <?php
                    esc_html_e(
                        'Importe',
                        'dsm-pagos'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        number_format_i18n(
                            $payment->getAmount(),
                            2
                        )
                        . ' '
                        . $payment->getCurrency()
                    );
                    ?>
                </dd>
            </div>

            <div>
                <dt>
                    <?php
                    esc_html_e(
                        'Estado',
                        'dsm-pagos'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    esc_html_e(
                        'Pendiente de pago',
                        'dsm-pagos'
                    );
                    ?>
                </dd>
            </div>

            <div>
                <dt>
                    <?php
                    esc_html_e(
                        'Concepto',
                        'dsm-pagos'
                    );
                    ?>
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $paymentConcept
                    );
                    ?>
                </dd>
            </div>

        </dl>

        <?php if ($providers === []) : ?>

            <div
                class="dsm-account-notice dsm-account-notice--warning"
            >
                <?php
                esc_html_e(
                    'No hay métodos de pago disponibles actualmente.',
                    'dsm-pagos'
                );
                ?>
            </div>

        <?php else : ?>

            <section class="dsm-payment-methods">

                <h2>
                    <?php
                    esc_html_e(
                        'Selecciona método de pago',
                        'dsm-pagos'
                    );
                    ?>
                </h2>

                <?php foreach (
                    $providers
                    as $code => $provider
                ) : ?>

                    <div class="dsm-card">

                        <h3>
                            <?php
                            echo esc_html(
                                $provider->getName()
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            switch ($code) {
                                case 'stripe':
                                    esc_html_e(
                                        'Paga de forma segura mediante Stripe.',
                                        'dsm-pagos'
                                    );
                                    break;

                                case 'redsys':
                                    esc_html_e(
                                        'Paga mediante el TPV bancario Redsys.',
                                        'dsm-pagos'
                                    );
                                    break;

                                case 'paypal':
                                    esc_html_e(
                                        'Paga utilizando tu cuenta de PayPal.',
                                        'dsm-pagos'
                                    );
                                    break;

                                default:
                                    esc_html_e(
                                        'Método de pago disponible.',
                                        'dsm-pagos'
                                    );
                                    break;
                            }
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
                                    PaymentCheckoutController::
                                        ACTION
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="payment_id"
                                value="<?php
                                echo esc_attr(
                                    (string) $payment->getId()
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
                                name="provider"
                                value="<?php
                                echo esc_attr(
                                    $code
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                PaymentCheckoutController::
                                    getNonceAction(
                                        $payment->getId(),
                                        $code
                                    ),
                                PaymentCheckoutController::
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
                                        'Pagar con %s',
                                        'dsm-pagos'
                                    ),
                                    esc_html(
                                        $provider->getName()
                                    )
                                );
                                ?>
                            </button>
                        </form>

                    </div>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <?php if ($advertisementId > 0) : ?>

            <p>
                <a
                    class="dsm-button dsm-button--secondary"
                    href="<?php
                    echo esc_url(
                        add_query_arg(
                            [
                                'advertisement_id' =>
                                    $advertisementId,
                            ],
                            home_url(
                                '/promocionar-anuncio/'
                            )
                        )
                    );
                    ?>"
                >
                    <?php
                    esc_html_e(
                        'Volver al anuncio',
                        'dsm-pagos'
                    );
                    ?>
                </a>
            </p>

        <?php endif; ?>

    </article>

</section>