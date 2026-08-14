<?php

declare(strict_types=1);

use DSM\Promocionar\Admin\PromotionGrantPage;
use DSM\Promocionar\Promotion\PromotionPlan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array<int, PromotionPlan> $plans
 * @var string $notice
 * @var string $error
 * @var string $customerSearchNonce
 */

$walletId =
    isset($_GET['wallet_id'])
        ? absint(
            wp_unslash(
                (string) $_GET['wallet_id']
            )
        )
        : 0;

?>

<div class="wrap">

    <h1>
        <?php
        esc_html_e(
            'Conceder promoción',
            'dsm-promocionar'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        esc_html_e(
            'Concede gratuitamente tiempo de promoción a un cliente sin generar ningún pago.',
            'dsm-promocionar'
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
                        'Promoción concedida correctamente. Wallet creado: #%d.',
                        $walletId
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
                PromotionGrantPage::
                    getGrantAction()
            );
            ?>"
        >

        <?php
        wp_nonce_field(
            PromotionGrantPage::
                getNonceAction(),
            PromotionGrantPage::
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

                    <p class="description">
                        Busca por nombre, correo, teléfono o ID interno.
                    </p>

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
                                        '%s — %s días — %0.2f %s',
                                        $plan->getName(),
                                        number_format(
                                            $plan->getDurationSeconds()
                                            / DAY_IN_SECONDS,
                                            0,
                                            ',',
                                            '.'
                                        ),
                                        $plan->getPrice(),
                                        $plan->getCurrency()
                                    )
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <p class="description">
                        El precio mostrado es el precio comercial.
                        La concesión tendrá coste 0,00.
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
                        placeholder="Ej.: compensación comercial"
                    >

                    <p class="description">
                        Quedará registrado como origen de la concesión.
                    </p>

                </td>

            </tr>

        </table>

        <?php
        submit_button(
            __(
                'Conceder promoción gratis',
                'dsm-promocionar'
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

                    const name =
                        customer.display_name
                        || 'Sin nombre';

                    button.innerHTML =
                        '<strong>'
                        + escapeHtml(name)
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

            customerIdInput.value =
                '';

            selectedContainer.style.display =
                'none';

            selectedContainer.innerHTML =
                '';

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
                    PromotionGrantPage::
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
                                response
                                && response.data
                                && response.data.message
                                    ? response.data.message
                                    : 'No se pudo realizar la búsqueda.'
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