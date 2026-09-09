<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (
    !isset($offer)
    || !is_object($offer)
    || (int) $offer->id <= 0
    || (string) $offer->scope !== 'customers'
) {
    return;
}

$currentOfferId =
    (int) $offer->id;
?>

<div class="dsm-offers-card dsm-offers-customers-card">

    <div class="dsm-offers-card__header">
        <div>
            <h2>
                Clientes asignados
            </h2>

            <p>
                Esta oferta solo puede aplicarse a los clientes que figuren como asignados.
            </p>
        </div>
    </div>

    <form
        method="post"
        action="<?php
        echo esc_url(
            admin_url(
                'admin-post.php'
            )
        );
        ?>"
        class="dsm-offers-customer-form"
    >

        <input
            type="hidden"
            name="action"
            value="dsm_offer_assign_customer"
        >

        <input
            type="hidden"
            name="offer_id"
            value="<?php
            echo esc_attr(
                (string) $currentOfferId
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            'dsm_offer_assign_customer_'
            . $currentOfferId,
            'dsm_offer_customer_nonce'
        );
        ?>

        <label class="dsm-offers-field">
            <span>
                Cliente
            </span>

            <input
                type="text"
                name="customer_reference"
                required
                placeholder="Email o ID del cliente"
            >

            <small>
                Puedes indicar el ID interno DSM o el email.
            </small>
        </label>

        <label class="dsm-offers-field">
            <span>
                Nota interna
            </span>

            <input
                type="text"
                name="internal_note"
                placeholder="Ej.: retención por bajo volumen"
            >
        </label>

        <button
            type="submit"
            class="button button-primary"
        >
            Asignar cliente
        </button>

    </form>

    <?php if ($assignedCustomers === []) : ?>

        <p class="dsm-offers-empty">
            Todavía no hay clientes asignados.
        </p>

    <?php else : ?>

        <div class="dsm-offers-customer-list">

            <?php foreach (
                $assignedCustomers
                as $assignedCustomer
            ) : ?>

                <div class="dsm-offers-customer">

                    <div class="dsm-offers-customer__info">

                        <strong>
                            <?php
                            echo esc_html(
                                (string)
                                $assignedCustomer->email
                            );
                            ?>
                        </strong>

                        <span>
                            Cliente #
                            <?php
                            echo esc_html(
                                (string)
                                $assignedCustomer
                                    ->customer_id
                            );
                            ?>
                        </span>

                        <span>
                            Estado de asignación:
                            <strong>
                                <?php
                                echo esc_html(
                                    (string)
                                    $assignedCustomer
                                        ->status
                                );
                                ?>
                            </strong>
                        </span>

                        <?php if (
                            trim(
                                (string) (
                                    $assignedCustomer
                                        ->internal_note
                                    ?? ''
                                )
                            ) !== ''
                        ) : ?>

                            <small>
                                <?php
                                echo esc_html(
                                    (string)
                                    $assignedCustomer
                                        ->internal_note
                                );
                                ?>
                            </small>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        (string)
                        $assignedCustomer->status
                        === 'assigned'
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
                                value="dsm_offer_revoke_customer"
                            >

                            <input
                                type="hidden"
                                name="offer_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $currentOfferId
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="customer_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $assignedCustomer
                                        ->customer_id
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                'dsm_offer_revoke_customer_'
                                . $currentOfferId
                                . '_'
                                . (int)
                                $assignedCustomer
                                    ->customer_id,
                                'dsm_offer_customer_nonce'
                            );
                            ?>

                            <button
                                type="submit"
                                class="button"
                            >
                                Revocar
                            </button>

                        </form>

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
                                value="dsm_offer_assign_customer"
                            >

                            <input
                                type="hidden"
                                name="offer_id"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $currentOfferId
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="customer_reference"
                                value="<?php
                                echo esc_attr(
                                    (string)
                                    $assignedCustomer
                                        ->customer_id
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="internal_note"
                                value="<?php
                                echo esc_attr(
                                    (string) (
                                        $assignedCustomer
                                            ->internal_note
                                        ?? ''
                                    )
                                );
                                ?>"
                            >

                            <?php
                            wp_nonce_field(
                                'dsm_offer_assign_customer_'
                                . $currentOfferId,
                                'dsm_offer_customer_nonce'
                            );
                            ?>

                            <button
                                type="submit"
                                class="button"
                            >
                                Reactivar
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
