<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoiceRepository
{
    private string $invoicesTable;

    private string $itemsTable;

    private InvoiceNumberGenerator $numberGenerator;

    public function __construct(
        ?InvoiceNumberGenerator $numberGenerator = null
    ) {
        global $wpdb;

        $this->invoicesTable =
            $wpdb->prefix
            . 'dsm_invoices';

        $this->itemsTable =
            $wpdb->prefix
            . 'dsm_invoice_items';

        $this->numberGenerator =
            $numberGenerator
            ?? new InvoiceNumberGenerator();
    }

    public function findById(
        int $invoiceId
    ): ?Invoice {
        global $wpdb;

        if ($invoiceId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->invoicesTable}
                    WHERE id = %d
                    LIMIT 1
                    ",
                    $invoiceId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    public function findByPaymentId(
        int $paymentId
    ): ?Invoice {
        global $wpdb;

        if ($paymentId <= 0) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->invoicesTable}
                    WHERE payment_id = %d
                    LIMIT 1
                    ",
                    $paymentId
                ),
                ARRAY_A
            );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate(
            $row
        );
    }

    /**
     * @return Invoice[]
     */
    public function findByCustomerId(
        int $customerId
    ): array {
        global $wpdb;

        if ($customerId <= 0) {
            return [];
        }

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->invoicesTable}
                    WHERE customer_id = %d
                    ORDER BY issued_at DESC, id DESC
                    ",
                    $customerId
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $invoices = [];

        foreach ($rows as $row) {
            $invoices[] =
                $this->hydrate(
                    $row
                );
        }

        return $invoices;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $items
     */
    public function create(
        array $data,
        array $items
    ): Invoice {
        global $wpdb;

        $paymentId =
            (int) (
                $data['payment_id']
                ?? 0
            );

        if ($paymentId <= 0) {
            throw new RuntimeException(
                'El pago asociado a la factura no es válido.'
            );
        }

        $existing =
            $this->findByPaymentId(
                $paymentId
            );

        if ($existing !== null) {
            return $existing;
        }

        if ($items === []) {
            throw new RuntimeException(
                'La factura debe contener al menos una línea.'
            );
        }

        $series =
            trim(
                (string) (
                    $data['series']
                    ?? ''
                )
            );

        $issuedAt =
            (string) (
                $data['issued_at']
                ?? current_time(
                    'mysql',
                    true
                )
            );

        $issuedTimestamp =
            strtotime(
                $issuedAt . ' UTC'
            );

        if ($issuedTimestamp === false) {
            throw new RuntimeException(
                'La fecha de emisión no es válida.'
            );
        }

        $fiscalYear =
            (int) gmdate(
                'Y',
                $issuedTimestamp
            );

        $now =
            current_time(
                'mysql',
                true
            );

        /*
         * create() puede ejecutarse dentro de una
         * transacción iniciada por una capa superior.
         *
         * Nunca debe hacer COMMIT ni ROLLBACK sobre
         * una transacción que pertenece al llamador.
         */
        $alreadyInTransaction =
            (int) $wpdb->get_var(
                'SELECT @@in_transaction'
            ) === 1;

        $savepoint =
            'dsm_invoice_create';

        $transactionStarted =
            false;

        try {
            if ($alreadyInTransaction) {
                $started =
                    $wpdb->query(
                        'SAVEPOINT '
                        . $savepoint
                    );
            } else {
                $started =
                    $wpdb->query(
                        'START TRANSACTION'
                    );
            }

            if ($started === false) {
                throw new RuntimeException(
                    'No se pudo iniciar el ámbito transaccional de facturación: '
                    . $wpdb->last_error
                );
            }

            $transactionStarted =
                true;

            $duplicateId =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$this->invoicesTable}
                        WHERE payment_id = %d
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $paymentId
                    )
                );

            if ($duplicateId !== null) {
                if ($alreadyInTransaction) {
                    $closed =
                        $wpdb->query(
                            'RELEASE SAVEPOINT '
                            . $savepoint
                        );
                } else {
                    $closed =
                        $wpdb->query(
                            'COMMIT'
                        );
                }

                if ($closed === false) {
                    throw new RuntimeException(
                        'No se pudo cerrar el ámbito transaccional de la factura existente.'
                    );
                }

                $transactionStarted =
                    false;

                $invoice =
                    $this->findById(
                        (int) $duplicateId
                    );

                if ($invoice === null) {
                    throw new RuntimeException(
                        'No se pudo recuperar la factura existente.'
                    );
                }

                return $invoice;
            }

            $number =
                $this->numberGenerator
                    ->next(
                        $series,
                        $fiscalYear
                    );

            $invoiceValues = [
                'customer_id' =>
                    (int) (
                        $data['customer_id']
                        ?? 0
                    ),

                'payment_id' =>
                    $paymentId,

                'document_type' =>
                    (string) (
                        $data['document_type']
                        ?? 'invoice'
                    ),

                'status' =>
                    (string) (
                        $data['status']
                        ?? 'issued'
                    ),

                'series' =>
                    $number['series'],

                'sequence_number' =>
                    $number[
                        'sequence_number'
                    ],

                'fiscal_year' =>
                    $number[
                        'fiscal_year'
                    ],

                'full_number' =>
                    $number[
                        'full_number'
                    ],

                'issued_at' =>
                    $issuedAt,

                'currency' =>
                    strtoupper(
                        (string) (
                            $data['currency']
                            ?? 'EUR'
                        )
                    ),

                'prices_include_tax' =>
                    !empty(
                        $data[
                            'prices_include_tax'
                        ]
                    )
                        ? 1
                        : 0,

                'subtotal' =>
                    self::money(
                        $data['subtotal']
                        ?? 0
                    ),

                'tax_total' =>
                    self::money(
                        $data['tax_total']
                        ?? 0
                    ),

                'total' =>
                    self::money(
                        $data['total']
                        ?? 0
                    ),

                'tax_type' =>
                    (string) (
                        $data['tax_type']
                        ?? 'IGIC'
                    ),

                'tax_rate' =>
                    self::decimal(
                        $data['tax_rate']
                        ?? 0,
                        4
                    ),

                'tax_exemption_code' =>
                    self::nullable(
                        $data[
                            'tax_exemption_code'
                        ]
                        ?? null
                    ),

                'tax_exemption_reason' =>
                    self::nullable(
                        $data[
                            'tax_exemption_reason'
                        ]
                        ?? null
                    ),

                'seller_fiscal_name' =>
                    (string) (
                        $data[
                            'seller_fiscal_name'
                        ]
                        ?? ''
                    ),

                'seller_tax_id' =>
                    (string) (
                        $data[
                            'seller_tax_id'
                        ]
                        ?? ''
                    ),

                'seller_address_line_1' =>
                    self::nullable(
                        $data[
                            'seller_address_line_1'
                        ]
                        ?? null
                    ),

                'seller_address_line_2' =>
                    self::nullable(
                        $data[
                            'seller_address_line_2'
                        ]
                        ?? null
                    ),

                'seller_postal_code' =>
                    self::nullable(
                        $data[
                            'seller_postal_code'
                        ]
                        ?? null
                    ),

                'seller_city' =>
                    self::nullable(
                        $data[
                            'seller_city'
                        ]
                        ?? null
                    ),

                'seller_province' =>
                    self::nullable(
                        $data[
                            'seller_province'
                        ]
                        ?? null
                    ),

                'seller_country_code' =>
                    strtoupper(
                        (string) (
                            $data[
                                'seller_country_code'
                            ]
                            ?? 'ES'
                        )
                    ),

                'seller_email' =>
                    self::nullable(
                        $data[
                            'seller_email'
                        ]
                        ?? null
                    ),

                'customer_type' =>
                    (string) (
                        $data[
                            'customer_type'
                        ]
                        ?? 'individual'
                    ),

                'customer_fiscal_name' =>
                    (string) (
                        $data[
                            'customer_fiscal_name'
                        ]
                        ?? ''
                    ),

                'customer_tax_id' =>
                    self::nullable(
                        $data[
                            'customer_tax_id'
                        ]
                        ?? null
                    ),

                'customer_address_line_1' =>
                    self::nullable(
                        $data[
                            'customer_address_line_1'
                        ]
                        ?? null
                    ),

                'customer_address_line_2' =>
                    self::nullable(
                        $data[
                            'customer_address_line_2'
                        ]
                        ?? null
                    ),

                'customer_postal_code' =>
                    self::nullable(
                        $data[
                            'customer_postal_code'
                        ]
                        ?? null
                    ),

                'customer_city' =>
                    self::nullable(
                        $data[
                            'customer_city'
                        ]
                        ?? null
                    ),

                'customer_province' =>
                    self::nullable(
                        $data[
                            'customer_province'
                        ]
                        ?? null
                    ),

                'customer_country_code' =>
                    strtoupper(
                        (string) (
                            $data[
                                'customer_country_code'
                            ]
                            ?? 'ES'
                        )
                    ),

                'customer_email' =>
                    self::nullable(
                        $data[
                            'customer_email'
                        ]
                        ?? null
                    ),

                'payment_provider' =>
                    self::nullable(
                        $data[
                            'payment_provider'
                        ]
                        ?? null
                    ),

                'payment_provider_reference' =>
                    self::nullable(
                        $data[
                            'payment_provider_reference'
                        ]
                        ?? null
                    ),

                'payment_purpose' =>
                    self::nullable(
                        $data[
                            'payment_purpose'
                        ]
                        ?? null
                    ),

                'payment_source_type' =>
                    self::nullable(
                        $data[
                            'payment_source_type'
                        ]
                        ?? null
                    ),

                'payment_source_id' =>
                    isset(
                        $data[
                            'payment_source_id'
                        ]
                    )
                        ? (int) $data[
                            'payment_source_id'
                        ]
                        : null,

                'payment_source_reference' =>
                    self::nullable(
                        $data[
                            'payment_source_reference'
                        ]
                        ?? null
                    ),

                'pdf_relative_path' =>
                    null,

                'pdf_generated_at' =>
                    null,

                'design_snapshot' =>
                    isset(
                        $data[
                            'design_snapshot'
                        ]
                    )
                        ? wp_json_encode(
                            $data[
                                'design_snapshot'
                            ],
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        )
                        : null,

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ];

            if (
                $invoiceValues[
                    'customer_id'
                ] <= 0
            ) {
                throw new RuntimeException(
                    'El cliente de la factura no es válido.'
                );
            }

            if (
                trim(
                    $invoiceValues[
                        'seller_fiscal_name'
                    ]
                ) === ''
                || trim(
                    $invoiceValues[
                        'seller_tax_id'
                    ]
                ) === ''
            ) {
                throw new RuntimeException(
                    'Los datos fiscales del emisor están incompletos.'
                );
            }

            if (
                trim(
                    $invoiceValues[
                        'customer_fiscal_name'
                    ]
                ) === ''
            ) {
                throw new RuntimeException(
                    'Los datos fiscales del cliente están incompletos.'
                );
            }

            $inserted =
                $wpdb->insert(
                    $this->invoicesTable,
                    $invoiceValues
                );

            if ($inserted === false) {
                throw new RuntimeException(
                    'No se pudo crear la factura: '
                    . $wpdb->last_error
                );
            }

            $invoiceId =
                (int) $wpdb->insert_id;

            foreach (
                $items
                as $index => $item
            ) {
                $description =
                    trim(
                        (string) (
                            $item[
                                'description'
                            ]
                            ?? ''
                        )
                    );

                if ($description === '') {
                    throw new RuntimeException(
                        'Una línea de factura no tiene descripción.'
                    );
                }

                $lineInserted =
                    $wpdb->insert(
                        $this->itemsTable,
                        [
                            'invoice_id' =>
                                $invoiceId,

                            'description' =>
                                $description,

                            'quantity' =>
                                self::decimal(
                                    $item[
                                        'quantity'
                                    ]
                                    ?? 1,
                                    4
                                ),

                            'unit_price' =>
                                self::decimal(
                                    $item[
                                        'unit_price'
                                    ]
                                    ?? 0,
                                    4
                                ),

                            'line_subtotal' =>
                                self::money(
                                    $item[
                                        'line_subtotal'
                                    ]
                                    ?? 0
                                ),

                            'tax_type' =>
                                (string) (
                                    $item[
                                        'tax_type'
                                    ]
                                    ?? 'IGIC'
                                ),

                            'tax_rate' =>
                                self::decimal(
                                    $item[
                                        'tax_rate'
                                    ]
                                    ?? 0,
                                    4
                                ),

                            'tax_base' =>
                                self::money(
                                    $item[
                                        'tax_base'
                                    ]
                                    ?? 0
                                ),

                            'tax_amount' =>
                                self::money(
                                    $item[
                                        'tax_amount'
                                    ]
                                    ?? 0
                                ),

                            'line_total' =>
                                self::money(
                                    $item[
                                        'line_total'
                                    ]
                                    ?? 0
                                ),

                            'sort_order' =>
                                isset(
                                    $item[
                                        'sort_order'
                                    ]
                                )
                                    ? max(
                                        0,
                                        (int) $item[
                                            'sort_order'
                                        ]
                                    )
                                    : $index,

                            'created_at' =>
                                $now,
                        ]
                    );

                if ($lineInserted === false) {
                    throw new RuntimeException(
                        'No se pudo guardar una línea de factura: '
                        . $wpdb->last_error
                    );
                }
            }

            if ($alreadyInTransaction) {
                $committed =
                    $wpdb->query(
                        'RELEASE SAVEPOINT '
                        . $savepoint
                    );
            } else {
                $committed =
                    $wpdb->query(
                        'COMMIT'
                    );
            }

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la factura.'
                );
            }

            $transactionStarted =
                false;

            $invoice =
                $this->findById(
                    $invoiceId
                );

            if ($invoice === null) {
                throw new RuntimeException(
                    'La factura se creó pero no pudo recuperarse.'
                );
            }

            return $invoice;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                if ($alreadyInTransaction) {
                    $wpdb->query(
                        'ROLLBACK TO SAVEPOINT '
                        . $savepoint
                    );

                    $wpdb->query(
                        'RELEASE SAVEPOINT '
                        . $savepoint
                    );
                } else {
                    $wpdb->query(
                        'ROLLBACK'
                    );
                }
            }

            $existing =
                $this->findByPaymentId(
                    $paymentId
                );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    /**
     * @return InvoiceItem[]
     */
    private function findItems(
        int $invoiceId
    ): array {
        global $wpdb;

        $rows =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->itemsTable}
                    WHERE invoice_id = %d
                    ORDER BY sort_order ASC, id ASC
                    ",
                    $invoiceId
                ),
                ARRAY_A
            );

        if (!is_array($rows)) {
            return [];
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] =
                new InvoiceItem(
                    id:
                        (int) $row['id'],

                    invoiceId:
                        (int) $row[
                            'invoice_id'
                        ],

                    description:
                        (string) $row[
                            'description'
                        ],

                    quantity:
                        (float) $row[
                            'quantity'
                        ],

                    unitPrice:
                        (float) $row[
                            'unit_price'
                        ],

                    lineSubtotal:
                        (float) $row[
                            'line_subtotal'
                        ],

                    taxType:
                        (string) $row[
                            'tax_type'
                        ],

                    taxRate:
                        (float) $row[
                            'tax_rate'
                        ],

                    taxBase:
                        (float) $row[
                            'tax_base'
                        ],

                    taxAmount:
                        (float) $row[
                            'tax_amount'
                        ],

                    lineTotal:
                        (float) $row[
                            'line_total'
                        ],

                    sortOrder:
                        (int) $row[
                            'sort_order'
                        ],

                    createdAt:
                        (string) $row[
                            'created_at'
                        ]
                );
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): Invoice {
        $invoiceId =
            (int) $row['id'];

        return new Invoice(
            id:
                $invoiceId,

            customerId:
                (int) $row[
                    'customer_id'
                ],

            paymentId:
                (int) $row[
                    'payment_id'
                ],

            documentType:
                (string) $row[
                    'document_type'
                ],

            status:
                (string) $row[
                    'status'
                ],

            series:
                (string) $row[
                    'series'
                ],

            sequenceNumber:
                (int) $row[
                    'sequence_number'
                ],

            fiscalYear:
                (int) $row[
                    'fiscal_year'
                ],

            fullNumber:
                (string) $row[
                    'full_number'
                ],

            issuedAt:
                (string) $row[
                    'issued_at'
                ],

            currency:
                (string) $row[
                    'currency'
                ],

            pricesIncludeTax:
                ((int) $row[
                    'prices_include_tax'
                ]) === 1,

            subtotal:
                (float) $row[
                    'subtotal'
                ],

            taxTotal:
                (float) $row[
                    'tax_total'
                ],

            total:
                (float) $row[
                    'total'
                ],

            taxType:
                (string) $row[
                    'tax_type'
                ],

            taxRate:
                (float) $row[
                    'tax_rate'
                ],

            sellerFiscalName:
                (string) $row[
                    'seller_fiscal_name'
                ],

            sellerTaxId:
                (string) $row[
                    'seller_tax_id'
                ],

            customerFiscalName:
                (string) $row[
                    'customer_fiscal_name'
                ],

            customerTaxId:
                self::nullable(
                    $row[
                        'customer_tax_id'
                    ]
                    ?? null
                ),

            pdfRelativePath:
                self::nullable(
                    $row[
                        'pdf_relative_path'
                    ]
                    ?? null
                ),

            pdfGeneratedAt:
                self::nullable(
                    $row[
                        'pdf_generated_at'
                    ]
                    ?? null
                ),

            createdAt:
                (string) $row[
                    'created_at'
                ],

            updatedAt:
                (string) $row[
                    'updated_at'
                ],

            items:
                $this->findItems(
                    $invoiceId
                )
        );
    }

    private static function money(
        mixed $value
    ): string {
        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    private static function decimal(
        mixed $value,
        int $precision
    ): string {
        return number_format(
            (float) $value,
            $precision,
            '.',
            ''
        );
    }

    private static function nullable(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}
