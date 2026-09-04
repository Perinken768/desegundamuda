<?php

declare(strict_types=1);

use DSM\Facturacion\Frontend\InvoiceDownloadController;
use DSM\Facturacion\Invoice\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var Invoice[] $invoices
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalInvoices
 * @var int $firstInvoiceNumber
 * @var int $lastInvoiceNumber
 */

$paginationBase =
    str_replace(
        '999999999',
        '%#%',
        esc_url_raw(
            add_query_arg(
                'facturas_page',
                999999999,
                home_url(
                    '/mis-facturas/'
                )
            )
        )
    );
?>

<div class="dsm-invoices">

    <header class="dsm-invoices__header">

        <div>

            <p class="dsm-invoices__eyebrow">
                Facturación
            </p>

            <h1 class="dsm-invoices__title">
                Mis facturas
            </h1>

            <p class="dsm-invoices__description">
                Consulta y descarga las facturas de los
                servicios contratados en DeSegundaMuda.
            </p>

        </div>

        <a
            class="
                dsm-button
                dsm-button--secondary
                dsm-invoices__billing-button
            "
            href="<?php
            echo esc_url(
                home_url(
                    '/datos-fiscales/'
                )
            );
            ?>"
        >
            Datos fiscales
        </a>

    </header>

    <section class="dsm-invoices__card">

        <?php if ($totalInvoices === 0) : ?>

            <div class="dsm-invoices__empty">

                <div class="dsm-invoices__empty-icon">
                    PDF
                </div>

                <h2>
                    Todavía no tienes facturas
                </h2>

                <p>
                    Cuando contrates un servicio de
                    DeSegundaMuda, sus facturas aparecerán aquí.
                </p>

            </div>

        <?php else : ?>

            <div class="dsm-invoices__card-header">

                <div>

                    <h2>
                        Historial de facturas
                    </h2>

                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                'Mostrando %1$d-%2$d de %3$d facturas',
                                $firstInvoiceNumber,
                                $lastInvoiceNumber,
                                $totalInvoices
                            )
                        );
                        ?>
                    </p>

                </div>

            </div>

            <div class="dsm-invoices__table-wrapper">

                <table class="dsm-invoices__table">

                    <thead>

                        <tr>

                            <th>
                                Factura
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Concepto
                            </th>

                            <th class="dsm-invoices__number">
                                Base
                            </th>

                            <th class="dsm-invoices__number">
                                IGIC
                            </th>

                            <th class="dsm-invoices__number">
                                Total
                            </th>

                            <th class="dsm-invoices__actions-heading">
                                Acción
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($invoices as $invoice) : ?>

                            <?php
                            $items =
                                $invoice->getItems();

                            $description =
                                isset($items[0])
                                    ? $items[0]
                                        ->getDescription()
                                    : 'Servicio DeSegundaMuda';

                            $issuedTimestamp =
                                strtotime(
                                    $invoice->getIssuedAt()
                                    . ' UTC'
                                );

                            $issuedDate =
                                $issuedTimestamp !== false
                                    ? wp_date(
                                        'd/m/Y',
                                        $issuedTimestamp
                                    )
                                    : $invoice
                                        ->getIssuedAt();

                            $downloadUrl =
                                InvoiceDownloadController
                                    ::getDownloadUrl(
                                        $invoice
                                    );
                            ?>

                            <tr>

                                <td
                                    data-label="Factura"
                                >
                                    <strong class="dsm-invoices__invoice-number">
                                        <?php
                                        echo esc_html(
                                            $invoice
                                                ->getFullNumber()
                                        );
                                        ?>
                                    </strong>
                                </td>

                                <td
                                    data-label="Fecha"
                                >
                                    <?php
                                    echo esc_html(
                                        $issuedDate
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="Concepto"
                                    class="dsm-invoices__concept"
                                >
                                    <?php
                                    echo esc_html(
                                        $description
                                    );
                                    ?>
                                </td>

                                <td
                                    data-label="Base"
                                    class="dsm-invoices__number"
                                >
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $invoice
                                                ->getSubtotal(),
                                            2
                                        )
                                    );
                                    ?>
                                    €
                                </td>

                                <td
                                    data-label="IGIC"
                                    class="dsm-invoices__number"
                                >
                                    <?php
                                    echo esc_html(
                                        number_format_i18n(
                                            $invoice
                                                ->getTaxTotal(),
                                            2
                                        )
                                    );
                                    ?>
                                    €
                                </td>

                                <td
                                    data-label="Total"
                                    class="dsm-invoices__number"
                                >
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                $invoice
                                                    ->getTotal(),
                                                2
                                            )
                                        );
                                        ?>
                                        €
                                    </strong>
                                </td>

                                <td
                                    data-label="Acción"
                                    class="dsm-invoices__actions"
                                >
                                    <a
                                        class="
                                            dsm-button
                                            dsm-button--secondary
                                            dsm-invoices__download
                                        "
                                        href="<?php
                                        echo esc_url(
                                            $downloadUrl
                                        );
                                        ?>"
                                    >
                                        Descargar PDF
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalPages > 1) : ?>

                <nav
                    class="dsm-invoices__pagination"
                    aria-label="Paginación de facturas"
                >
                    <?php
                    echo wp_kses_post(
                        paginate_links(
                            [
                                'base' =>
                                    $paginationBase,

                                'format' =>
                                    '',

                                'current' =>
                                    $currentPage,

                                'total' =>
                                    $totalPages,

                                'mid_size' =>
                                    2,

                                'end_size' =>
                                    1,

                                'prev_text' =>
                                    '← Anterior',

                                'next_text' =>
                                    'Siguiente →',

                                'type' =>
                                    'list',
                            ]
                        )
                    );
                    ?>
                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </section>

</div>
