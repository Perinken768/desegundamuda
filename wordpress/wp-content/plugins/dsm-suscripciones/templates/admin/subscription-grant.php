<?php

declare(strict_types=1);

use DSM\Suscripciones\Admin\SubscriptionGrantPage;
use DSM\Suscripciones\Subscription\SubscriptionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, SubscriptionPlan> $plans
 * @var string $notice
 * @var string $error
 * @var int $subscriptionId
 * @var string $customerSearchNonce
 */

?>

<div class="wrap">

    <h1>
        <?php
        esc_html_e(
            'Conceder suscripción',
            'dsm-suscripciones'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        esc_html_e(
            'Concede gratuitamente una suscripción temporal a un cliente sin generar ningún pago.',
            'dsm-suscripciones'
        );
        ?>
    </p>

    <hr class="wp-header-end">

    <?php if ($notice === 'granted') : ?>

        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        'Suscripción concedida correctamente. Suscripción #%d.',
                        $subscriptionId
                    )
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
                SubscriptionGrantPage::
                    getGrantAction()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            SubscriptionGrantPage::
                getNonceAction(),
            SubscriptionGrantPage::
                getNonceName()
        );
        ?>

        <table
            class="form-table"
            role="presentation"
        >

            <tr>

                <th scope="row">
                    <label for="dsm-customer-search">
                        Cliente
                    </label>
                </th>

                <td>

                    <input
                        type="search"
                        id="dsm-customer-search"
                        class="regular-text"
                        placeholder="Nombre, correo, teléfono o ID"
                        autocomplete="off"
                    >

                    <input
                        type="hidden"
                        id="customer_id"
                        name="customer_id"
                        value=""
                        required
                    >

                    <div
                        id="dsm-customer-search-status"
                        style="margin-top:8px;"
                    ></div>

                    <div
                        id="dsm-customer-search-results"
                        style="
                            max-width:600px;
                            margin-top:8px;
                        "
                    ></div>

                    <div
                        id="dsm-selected-customer"
                        style="
                            display:none;
                            max-width:600px;
                            margin-top:12px;
                            padding:12px;
                            border:1px solid #ccd0d4;
                            background:#fff;
                        "
                    ></div>

                </td>

            </tr>

            <tr>

                <th scope="row">
                    <label for="plan_id">
                        Plan
                    </label>
                </th>

                <td>

                    <select
                        id="plan_id"
                        name="plan_id"
                        required
                    >

                        <option value="">
                            Selecciona un plan
                        </option>

                        <?php foreach ($plans as $plan) : ?>

                            <option
                                value="<?php
                                echo esc_attr(
                                    (string) $plan->getId()
                                );
                                ?>"
                            >
                                <?php
                                echo esc_html(
                                    sprintf(
                                        '%s — %0.2f %s / %s',
                                        $plan->getName(),
                                        $plan->getPrice(),
                                        $plan->getCurrency(),
                                        $plan->getBillingInterval()
                                    )
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <p class="description">
                        La concesión administrativa tendrá coste 0,00.
                    </p>

                </td>

            </tr>

            <tr>

                <th scope="row">
                    <label for="starts_at">
                        Fecha de inicio
                    </label>
                </th>

                <td>

                    <input
                        type="datetime-local"
                        id="starts_at"
                        name="starts_at"
                        required
                        value="<?php
                        echo esc_attr(
                            wp_date(
                                'Y-m-d\TH:i'
                            )
                        );
                        ?>"
                    >

                </td>

            </tr>

            <tr>

                <th scope="row">
                    <label for="ends_at">
                        Fecha de fin
                    </label>
                </th>

                <td>

                    <input
                        type="datetime-local"
                        id="ends_at"
                        name="ends_at"
                        required
                        value="<?php
                        echo esc_attr(
                            wp_date(
                                'Y-m-d\TH:i',
                                current_time(
                                    'timestamp'
                                )
                                + 30 * DAY_IN_SECONDS
                            )
                        );
                        ?>"
                    >

                    <p class="description">
                        Para una concesión mensual puedes dejar aproximadamente 30 días.
                    </p>

                </td>

            </tr>

            <tr>

                <th scope="row">
                    <label for="reason">
                        Motivo
                    </label>
                </th>

                <td>

                    <input
                        type="text"
                        id="reason"
                        name="reason"
                        maxlength="190"
                        required
                        class="regular-text"
                        placeholder="Ej.: prueba comercial"
                    >

                </td>

            </tr>

        </table>

        <?php
        submit_button(
            __(
                'Conceder suscripción gratis',
                'dsm-suscripciones'
            ),
            'primary'
        );
        ?>

    </form>

</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const searchInput =
            document.getElementById(
                'dsm-customer-search'
            );

        const customerIdInput =
            document.getElementById(
                'customer_id'
            );

        const resultsContainer =
            document.getElementById(
                'dsm-customer-search-results'
            );

        const statusContainer =
            document.getElementById(
                'dsm-customer-search-status'
            );

        const selectedContainer =
            document.getElementById(
                'dsm-selected-customer'
            );

        if (
            !searchInput
            || !customerIdInput
            || !resultsContainer
            || !statusContainer
            || !selectedContainer
        ) {
            return;
        }

        let timeoutId = null;
        let requestController = null;

        const clearResults = function () {
            resultsContainer.innerHTML = '';
        };

        const escapeHtml = function (value) {
            const element =
                document.createElement(
                    'div'
                );

            element.textContent =
                String(
                    value ?? ''
                );

            return element.innerHTML;
        };

        const selectCustomer = function (
            customer
        ) {
            customerIdInput.value =
                String(
                    customer.id
                );

            selectedContainer.style.display =
                'block';

            selectedContainer.innerHTML =
                '<strong>'
                + escapeHtml(
                    customer.display_name
                    || 'Sin nombre'
                )
                + '</strong>'
                + '<br>'
                + escapeHtml(
                    customer.email
                )
                + (
                    customer.phone
                        ? ' · '
                            + escapeHtml(
                                customer.phone
                            )
                        : ''
                )
                + '<br>'
                + '<small>Cliente #'
                + escapeHtml(
                    customer.id
                )
                + '</small>';

            searchInput.value =
                customer.display_name
                || customer.email;

            statusContainer.textContent =
                'Cliente seleccionado.';

            clearResults();
        };

        const renderResults = function (
            customers
        ) {
            clearResults();

            if (
                !Array.isArray(
                    customers
                )
                || customers.length === 0
            ) {
                statusContainer.textContent =
                    'No se encontraron clientes activos.';

                return;
            }

            statusContainer.textContent =
                customers.length
                + (
                    customers.length === 1
                        ? ' resultado.'
                        : ' resultados.'
                );

            customers.forEach(
                function (customer) {
                    const button =
                        document.createElement(
                            'button'
                        );

                    button.type =
                        'button';

                    button.className =
                        'button';

                    button.style.display =
                        'block';

                    button.style.width =
                        '100%';

                    button.style.textAlign =
                        'left';

                    button.style.marginBottom =
                        '6px';

                    button.style.padding =
                        '8px 10px';

                    button.innerHTML =
                        '<strong>'
                        + escapeHtml(
                            customer.display_name
                            || 'Sin nombre'
                        )
                        + '</strong>'
                        + '<br>'
                        + '<small>'
                        + escapeHtml(
                            customer.email
                        )
                        + (
                            customer.phone
                                ? ' · '
                                    + escapeHtml(
                                        customer.phone
                                    )
                                : ''
                        )
                        + ' · #'
                        + escapeHtml(
                            customer.id
                        )
                        + '</small>';

                    button.addEventListener(
                        'click',
                        function () {
                            selectCustomer(
                                customer
                            );
                        }
                    );

                    resultsContainer.appendChild(
                        button
                    );
                }
            );
        };

        const searchCustomers = function () {
            const search =
                searchInput.value.trim();

            customerIdInput.value = '';

            selectedContainer.style.display =
                'none';

            selectedContainer.innerHTML = '';

            clearResults();

            if (search.length < 2) {
                statusContainer.textContent =
                    search.length === 0
                        ? ''
                        : 'Escribe al menos 2 caracteres.';

                return;
            }

            if (requestController) {
                requestController.abort();
            }

            requestController =
                new AbortController();

            statusContainer.textContent =
                'Buscando...';

            const url =
                new URL(
                    <?php
                    echo wp_json_encode(
                        admin_url(
                            'admin-ajax.php'
                        )
                    );
                    ?>
                );

            url.searchParams.set(
                'action',
                <?php
                echo wp_json_encode(
                    SubscriptionGrantPage::
                        CUSTOMER_SEARCH_ACTION
                );
                ?>
            );

            url.searchParams.set(
                'nonce',
                <?php
                echo wp_json_encode(
                    $customerSearchNonce
                );
                ?>
            );

            url.searchParams.set(
                'search',
                search
            );

            fetch(
                url.toString(),
                {
                    method:
                        'GET',

                    credentials:
                        'same-origin',

                    signal:
                        requestController.signal,
                }
            )
                .then(
                    function (response) {
                        if (!response.ok) {
                            throw new Error(
                                'Error HTTP '
                                + response.status
                            );
                        }

                        return response.json();
                    }
                )
                .then(
                    function (response) {
                        if (
                            !response
                            || response.success !== true
                        ) {
                            throw new Error(
                                'No se pudo realizar la búsqueda.'
                            );
                        }

                        renderResults(
                            response.data.customers
                        );
                    }
                )
                .catch(
                    function (error) {
                        if (
                            error.name
                            === 'AbortError'
                        ) {
                            return;
                        }

                        statusContainer.textContent =
                            'No se pudo buscar clientes.';
                    }
                );
        };

        searchInput.addEventListener(
            'input',
            function () {
                clearTimeout(
                    timeoutId
                );

                timeoutId =
                    setTimeout(
                        searchCustomers,
                        300
                    );
            }
        );
    }
);
</script>
