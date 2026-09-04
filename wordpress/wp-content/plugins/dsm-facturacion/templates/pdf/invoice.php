<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables disponibles:
 *
 * @var array<string, mixed> $invoice
 * @var array<int, array<string, mixed>> $items
 * @var string|null $logoDataUri
 * @var string $footerText
 */

$money =
    static function (
        mixed $value
    ): string {
        return number_format(
            (float) $value,
            2,
            ',',
            '.'
        )
        . ' €';
    };

$text =
    static function (
        mixed $value
    ): string {
        return trim(
            (string) (
                $value
                ?? ''
            )
        );
    };

$issuedTimestamp =
    strtotime(
        (string) $invoice[
            'issued_at'
        ]
        . ' UTC'
    );

$issuedDate =
    $issuedTimestamp !== false
        ? wp_date(
            'd/m/Y',
            $issuedTimestamp
        )
        : (string) $invoice[
            'issued_at'
        ];

$sellerAddress = array_filter(
    [
        $text(
            $invoice[
                'seller_address_line_1'
            ]
            ?? ''
        ),

        $text(
            $invoice[
                'seller_address_line_2'
            ]
            ?? ''
        ),

        trim(
            $text(
                $invoice[
                    'seller_postal_code'
                ]
                ?? ''
            )
            . ' '
            . $text(
                $invoice[
                    'seller_city'
                ]
                ?? ''
            )
        ),

        $text(
            $invoice[
                'seller_province'
            ]
            ?? ''
        ),
    ]
);

$customerAddress = array_filter(
    [
        $text(
            $invoice[
                'customer_address_line_1'
            ]
            ?? ''
        ),

        $text(
            $invoice[
                'customer_address_line_2'
            ]
            ?? ''
        ),

        trim(
            $text(
                $invoice[
                    'customer_postal_code'
                ]
                ?? ''
            )
            . ' '
            . $text(
                $invoice[
                    'customer_city'
                ]
                ?? ''
            )
        ),

        $text(
            $invoice[
                'customer_province'
            ]
            ?? ''
        ),
    ]
);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 30px 38px 42px 38px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.45;
            color: #171717;
        }

        .header {
            width: 100%;
            margin-bottom: 28px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-cell {
            width: 55%;
        }

        .invoice-cell {
            width: 45%;
            text-align: right;
        }

        .logo {
            max-width: 180px;
            max-height: 70px;
        }

        .brand-name {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
        }

        .document-title {
            margin: 0 0 5px 0;
            font-size: 25px;
            font-weight: bold;
        }

        .document-number {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
        }

        .document-date {
            margin-top: 5px;
            color: #555;
        }

        .parties {
            width: 100%;
            margin-bottom: 28px;
            border-collapse: collapse;
        }

        .parties td {
            width: 50%;
            vertical-align: top;
        }

        .parties td:first-child {
            padding-right: 14px;
        }

        .parties td:last-child {
            padding-left: 14px;
        }

        .party-box {
            min-height: 128px;
            padding: 14px;
            border: 1px solid #dddddd;
            border-radius: 5px;
        }

        .party-label {
            margin-bottom: 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
        }

        .party-name {
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: bold;
        }

        .party-line {
            margin-bottom: 2px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .items th {
            padding: 9px 7px;
            border-bottom: 2px solid #222;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        .items td {
            padding: 10px 7px;
            border-bottom: 1px solid #dddddd;
            vertical-align: top;
        }

        .items .number {
            text-align: right;
            white-space: nowrap;
        }

        .summary-wrap {
            width: 100%;
            margin-top: 20px;
        }

        .summary {
            width: 250px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .summary td {
            padding: 5px 7px;
        }

        .summary .label {
            text-align: left;
        }

        .summary .amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary .tax-row td {
            border-bottom: 1px solid #cccccc;
        }

        .summary .total-row td {
            padding-top: 9px;
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #222;
        }

        .tax-note {
            margin-top: 16px;
            font-size: 9px;
            color: #555;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -20px;
            padding-top: 8px;
            border-top: 1px solid #dddddd;
            font-size: 8px;
            text-align: center;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="header">

        <table class="header-table">
            <tr>

                <td class="brand-cell">

                    <?php if ($logoDataUri !== null) : ?>

                        <img
                            class="logo"
                            src="<?php
                            echo esc_attr(
                                $logoDataUri
                            );
                            ?>"
                            alt=""
                        >

                    <?php else : ?>

                        <p class="brand-name">
                            DeSegundaMuda
                        </p>

                    <?php endif; ?>

                </td>

                <td class="invoice-cell">

                    <p class="document-title">
                        FACTURA
                    </p>

                    <p class="document-number">
                        <?php
                        echo esc_html(
                            $invoice[
                                'full_number'
                            ]
                        );
                        ?>
                    </p>

                    <div class="document-date">
                        Fecha:
                        <?php
                        echo esc_html(
                            $issuedDate
                        );
                        ?>
                    </div>

                </td>

            </tr>
        </table>

    </div>

    <table class="parties">
        <tr>

            <td>
                <div class="party-box">

                    <div class="party-label">
                        Emisor
                    </div>

                    <div class="party-name">
                        <?php
                        echo esc_html(
                            $invoice[
                                'seller_fiscal_name'
                            ]
                        );
                        ?>
                    </div>

                    <div class="party-line">
                        NIF/CIF:
                        <?php
                        echo esc_html(
                            $invoice[
                                'seller_tax_id'
                            ]
                        );
                        ?>
                    </div>

                    <?php foreach ($sellerAddress as $line) : ?>

                        <div class="party-line">
                            <?php
                            echo esc_html(
                                $line
                            );
                            ?>
                        </div>

                    <?php endforeach; ?>

                    <?php
                    $sellerEmail =
                        $text(
                            $invoice[
                                'seller_email'
                            ]
                            ?? ''
                        );
                    ?>

                    <?php if ($sellerEmail !== '') : ?>

                        <div class="party-line">
                            <?php
                            echo esc_html(
                                $sellerEmail
                            );
                            ?>
                        </div>

                    <?php endif; ?>

                </div>
            </td>

            <td>
                <div class="party-box">

                    <div class="party-label">
                        Cliente
                    </div>

                    <div class="party-name">
                        <?php
                        echo esc_html(
                            $invoice[
                                'customer_fiscal_name'
                            ]
                        );
                        ?>
                    </div>

                    <?php
                    $customerTaxId =
                        $text(
                            $invoice[
                                'customer_tax_id'
                            ]
                            ?? ''
                        );
                    ?>

                    <?php if ($customerTaxId !== '') : ?>

                        <div class="party-line">
                            NIF/CIF:
                            <?php
                            echo esc_html(
                                $customerTaxId
                            );
                            ?>
                        </div>

                    <?php endif; ?>

                    <?php foreach ($customerAddress as $line) : ?>

                        <div class="party-line">
                            <?php
                            echo esc_html(
                                $line
                            );
                            ?>
                        </div>

                    <?php endforeach; ?>

                    <?php
                    $customerEmail =
                        $text(
                            $invoice[
                                'customer_email'
                            ]
                            ?? ''
                        );
                    ?>

                    <?php if ($customerEmail !== '') : ?>

                        <div class="party-line">
                            <?php
                            echo esc_html(
                                $customerEmail
                            );
                            ?>
                        </div>

                    <?php endif; ?>

                </div>
            </td>

        </tr>
    </table>

    <table class="items">

        <thead>
            <tr>

                <th>
                    Concepto
                </th>

                <th class="number">
                    Base
                </th>

                <th class="number">
                    IGIC
                </th>

                <th class="number">
                    Total
                </th>

            </tr>
        </thead>

        <tbody>

            <?php foreach ($items as $item) : ?>

                <tr>

                    <td>
                        <?php
                        echo esc_html(
                            $item[
                                'description'
                            ]
                        );
                        ?>
                    </td>

                    <td class="number">
                        <?php
                        echo esc_html(
                            $money(
                                $item[
                                    'tax_base'
                                ]
                            )
                        );
                        ?>
                    </td>

                    <td class="number">
                        <?php
                        echo esc_html(
                            number_format(
                                (float) $item[
                                    'tax_rate'
                                ],
                                2,
                                ',',
                                '.'
                            )
                        );
                        ?>
                        %
                        <br>
                        <?php
                        echo esc_html(
                            $money(
                                $item[
                                    'tax_amount'
                                ]
                            )
                        );
                        ?>
                    </td>

                    <td class="number">
                        <strong>
                            <?php
                            echo esc_html(
                                $money(
                                    $item[
                                        'line_total'
                                    ]
                                )
                            );
                            ?>
                        </strong>
                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

    <div class="summary-wrap">

        <table class="summary">

            <tr>
                <td class="label">
                    Base imponible
                </td>

                <td class="amount">
                    <?php
                    echo esc_html(
                        $money(
                            $invoice[
                                'subtotal'
                            ]
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr class="tax-row">
                <td class="label">
                    <?php
                    echo esc_html(
                        $invoice[
                            'tax_type'
                        ]
                    );
                    ?>
                    <?php
                    echo esc_html(
                        number_format(
                            (float) $invoice[
                                'tax_rate'
                            ],
                            2,
                            ',',
                            '.'
                        )
                    );
                    ?>
                    %
                </td>

                <td class="amount">
                    <?php
                    echo esc_html(
                        $money(
                            $invoice[
                                'tax_total'
                            ]
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr class="total-row">
                <td class="label">
                    TOTAL
                </td>

                <td class="amount">
                    <?php
                    echo esc_html(
                        $money(
                            $invoice[
                                'total'
                            ]
                        )
                    );
                    ?>
                </td>
            </tr>

        </table>

    </div>

    <div class="tax-note">
        Importes expresados en
        <?php
        echo esc_html(
            $invoice[
                'currency'
            ]
        );
        ?>.
        <?php
        echo esc_html(
            $invoice[
                'tax_type'
            ]
        );
        ?>
        incluido en el precio.
    </div>

    <?php if ($footerText !== '') : ?>

        <div class="footer">
            <?php
            echo nl2br(
                esc_html(
                    $footerText
                )
            );
            ?>
        </div>

    <?php endif; ?>

</body>
</html>
