<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

use DateTimeImmutable;
use DateTimeZone;
use DSM\Facturacion\Invoice\Invoice;
use DSM\Facturacion\Invoice\InvoiceRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuRegistrationGenerator
{
    private InvoiceRepository $invoiceRepository;

    private VerifactuChainRepository $chainRepository;

    private VerifactuRecordRepository $recordRepository;

    private VerifactuHashGenerator $hashGenerator;

    public function __construct(
        ?InvoiceRepository $invoiceRepository = null,
        ?VerifactuChainRepository $chainRepository = null,
        ?VerifactuRecordRepository $recordRepository = null,
        ?VerifactuHashGenerator $hashGenerator = null
    ) {
        $this->invoiceRepository =
            $invoiceRepository
            ?? new InvoiceRepository();

        $this->chainRepository =
            $chainRepository
            ?? new VerifactuChainRepository();

        $this->recordRepository =
            $recordRepository
            ?? new VerifactuRecordRepository();

        $this->hashGenerator =
            $hashGenerator
            ?? new VerifactuHashGenerator();
    }

    public function generateForInvoice(
        int $invoiceId
    ): VerifactuRecord {
        global $wpdb;

        if ($invoiceId <= 0) {
            throw new RuntimeException(
                'El identificador de factura no es válido.'
            );
        }

        /*
         * Idempotencia antes de iniciar transacción.
         */
        $existing =
            $this->recordRepository
                ->findRegistrationByInvoiceId(
                    $invoiceId
                );

        if ($existing !== null) {
            return $existing;
        }

        $invoice =
            $this->invoiceRepository
                ->findById(
                    $invoiceId
                );

        if ($invoice === null) {
            throw new RuntimeException(
                sprintf(
                    'No existe la factura %d.',
                    $invoiceId
                )
            );
        }

        $this->validateInvoice(
            $invoice
        );

        $settings =
            VerifactuSettings::get();

        $environment =
            (string) $settings[
                'environment'
            ];

        $systemName =
            trim(
                (string) $settings[
                    'system_name'
                ]
            );

        $systemId =
            trim(
                (string) $settings[
                    'system_id'
                ]
            );

        $systemVersion =
            trim(
                (string) $settings[
                    'system_version'
                ]
            );

        $installationId =
            trim(
                (string) $settings[
                    'installation_id'
                ]
            );

        if (
            $systemName === ''
            || $systemId === ''
            || $systemVersion === ''
            || $installationId === ''
        ) {
            throw new RuntimeException(
                'La identificación del sistema VERI*FACTU está incompleta.'
            );
        }

        $generatedDateTime =
            new DateTimeImmutable(
                'now',
                wp_timezone()
            );

        /*
         * MySQL interno:
         * UTC.
         */
        $generatedAt =
            $generatedDateTime
                ->setTimezone(
                    new DateTimeZone(
                        'UTC'
                    )
                )
                ->format(
                    'Y-m-d H:i:s'
                );

        /*
         * Representación fiscal:
         * hora local WordPress con offset real.
         */
        $generatedAtIso =
            $generatedDateTime
                ->format(
                    'Y-m-d\TH:i:sP'
                );

        $invoiceDate =
            $this->formatInvoiceDate(
                $invoice->getIssuedAt()
            );

        $invoiceType =
            $this->resolveInvoiceType(
                $invoice
            );

        $description =
            $this->resolveDescription(
                $invoice
            );

        /*
         * El generador puede ejecutarse dentro de una
         * transacción iniciada por otra capa.
         *
         * Nunca debe confirmar ni revertir una
         * transacción ajena.
         */
        $alreadyInTransaction =
            (int) $wpdb->get_var(
                'SELECT @@in_transaction'
            ) === 1;

        $savepoint =
            'dsm_verifactu_registration';

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
                    'No se pudo iniciar el ámbito transaccional VERI*FACTU: '
                    . $wpdb->last_error
                );
            }

            $transactionStarted =
                true;

            /*
             * Repetimos la comprobación dentro de la
             * transacción.
             */
            $duplicateId =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$wpdb->prefix}dsm_verifactu_records
                        WHERE invoice_id = %d
                          AND record_type = 'alta'
                          AND generation_type = 'normal'
                        ORDER BY id ASC
                        LIMIT 1
                        FOR UPDATE
                        ",
                        $invoiceId
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
                        'No se pudo cerrar el ámbito transaccional del registro VERI*FACTU existente.'
                    );
                }

                $transactionStarted =
                    false;

                $record =
                    $this->recordRepository
                        ->findById(
                            (int) $duplicateId
                        );

                if ($record === null) {
                    throw new RuntimeException(
                        'No se pudo recuperar el registro VERI*FACTU existente.'
                    );
                }

                return $record;
            }

            $chain =
                $this->chainRepository
                    ->lockOrCreate(
                        $environment,
                        $invoice
                            ->getSellerTaxId(),
                        $systemId,
                        $installationId
                    );

            $chainId =
                (int) $chain['id'];

            $chainSequence =
                ((int) $chain[
                    'last_sequence'
                ])
                + 1;

            $previousRecordId =
                isset(
                    $chain[
                        'last_record_id'
                    ]
                )
                && $chain[
                    'last_record_id'
                ] !== null
                    ? (int) $chain[
                        'last_record_id'
                    ]
                    : null;

            $previousHash =
                isset(
                    $chain[
                        'last_hash'
                    ]
                )
                    ? trim(
                        (string) $chain[
                            'last_hash'
                        ]
                    )
                    : '';

            /*
             * Comprobación local del último registro.
             *
             * AEAT exige verificar el encadenamiento
             * antes de generar el siguiente.
             */
            if ($previousRecordId !== null) {
                $this->assertPreviousRecordIntegrity(
                    $previousRecordId,
                    $previousHash
                );
            }

            $hash =
                $this->hashGenerator
                    ->generateRegistrationHash(
                        $invoice
                            ->getSellerTaxId(),
                        $invoice
                            ->getFullNumber(),
                        $invoiceDate,
                        $invoiceType,
                        $invoice
                            ->getTaxTotal(),
                        $invoice
                            ->getTotal(),
                        $previousHash !== ''
                            ? $previousHash
                            : null,
                        $generatedAtIso
                    );

            $now =
                current_time(
                    'mysql',
                    true
                );

            $invoiceSnapshot =
                $this->buildInvoiceSnapshot(
                    $invoice
                );

            $taxBreakdown =
                $this->buildTaxBreakdown(
                    $invoice
                );

            $customerSnapshot = [
                'fiscal_name' =>
                    $invoice
                        ->getCustomerFiscalName(),

                'tax_id' =>
                    $invoice
                        ->getCustomerTaxId(),
            ];

            $systemSnapshot = [
                'system_name' =>
                    $systemName,

                'system_id' =>
                    $systemId,

                'system_version' =>
                    $systemVersion,

                'installation_id' =>
                    $installationId,

                'environment' =>
                    $environment,
            ];

            $previousInvoiceNumber =
                null;

            $previousInvoiceDate =
                null;

            $previousIssuerTaxId =
                null;

            if ($previousRecordId !== null) {
                $previous =
                    $this->recordRepository
                        ->findById(
                            $previousRecordId
                        );

                if ($previous === null) {
                    throw new RuntimeException(
                        'No se pudo recuperar el registro VERI*FACTU anterior.'
                    );
                }

                $previousInvoiceNumber =
                    $previous
                        ->getInvoiceNumber();

                $previousInvoiceDate =
                    $previous
                        ->getInvoiceDate();

                $previousIssuerTaxId =
                    $previous
                        ->getIssuerTaxId();
            }

            $recordId =
                $this->recordRepository
                    ->insert(
                        [
                            'record_uuid' =>
                                wp_generate_uuid4(),

                            'chain_id' =>
                                $chainId,

                            'chain_sequence' =>
                                $chainSequence,

                            'invoice_id' =>
                                $invoiceId,

                            'record_type' =>
                                'alta',

                            'generation_type' =>
                                'normal',

                            'fiscal_status' =>
                                'generated',

                            'environment' =>
                                $environment,

                            'issuer_fiscal_name' =>
                                $invoice
                                    ->getSellerFiscalName(),

                            'issuer_tax_id' =>
                                $invoice
                                    ->getSellerTaxId(),

                            'invoice_number' =>
                                $invoice
                                    ->getFullNumber(),

                            'invoice_date' =>
                                $this
                                    ->invoiceDateToDatabase(
                                        $invoiceDate
                                    ),

                            'invoice_type' =>
                                $invoiceType,

                            'description' =>
                                $description,

                            'currency' =>
                                $invoice
                                    ->getCurrency(),

                            'tax_type' =>
                                $invoice
                                    ->getTaxType(),

                            'tax_rate' =>
                                number_format(
                                    $invoice
                                        ->getTaxRate(),
                                    4,
                                    '.',
                                    ''
                                ),

                            'tax_base' =>
                                number_format(
                                    $invoice
                                        ->getSubtotal(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_amount' =>
                                number_format(
                                    $invoice
                                        ->getTaxTotal(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'total_amount' =>
                                number_format(
                                    $invoice
                                        ->getTotal(),
                                    2,
                                    '.',
                                    ''
                                ),

                            'tax_breakdown_snapshot' =>
                                wp_json_encode(
                                    $taxBreakdown,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'customer_snapshot' =>
                                wp_json_encode(
                                    $customerSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'invoice_snapshot' =>
                                wp_json_encode(
                                    $invoiceSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'system_name' =>
                                $systemName,

                            'system_id' =>
                                $systemId,

                            'system_version' =>
                                $systemVersion,

                            'installation_id' =>
                                $installationId,

                            'system_snapshot' =>
                                wp_json_encode(
                                    $systemSnapshot,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),

                            'previous_record_id' =>
                                $previousRecordId,

                            'previous_issuer_tax_id' =>
                                $previousIssuerTaxId,

                            'previous_invoice_number' =>
                                $previousInvoiceNumber,

                            'previous_invoice_date' =>
                                $previousInvoiceDate,

                            'previous_hash' =>
                                $previousHash !== ''
                                    ? $previousHash
                                    : null,

                            'hash_algorithm' =>
                                VerifactuHashGenerator::HASH_TYPE,

                            'hash_input' =>
                                $hash['input'],

                            'hash_value' =>
                                $hash['hash'],

                            'generated_at' =>
                                $generatedAt,

                            'generated_at_iso' =>
                                $generatedAtIso,

                            'payload_version' =>
                                null,

                            'payload_xml' =>
                                null,

                            'aeat_record_status' =>
                                null,

                            'aeat_error_code' =>
                                null,

                            'aeat_error_message' =>
                                null,

                            'accepted_at' =>
                                null,

                            'created_at' =>
                                $now,

                            'updated_at' =>
                                $now,
                        ]
                    );

            $this->chainRepository
                ->advance(
                    $chainId,
                    $chainSequence,
                    $recordId,
                    $hash['hash'],
                    $generatedAt,
                    $generatedAtIso
                );

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
                    'No se pudo confirmar el registro VERI*FACTU.'
                );
            }

            $transactionStarted =
                false;

            $record =
                $this->recordRepository
                    ->findById(
                        $recordId
                    );

            if ($record === null) {
                throw new RuntimeException(
                    'El registro VERI*FACTU se creó pero no pudo recuperarse.'
                );
            }

            return $record;
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
                $this->recordRepository
                    ->findRegistrationByInvoiceId(
                        $invoiceId
                    );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function validateInvoice(
        Invoice $invoice
    ): void {
        if (
            $invoice->getStatus()
            !== 'issued'
        ) {
            throw new RuntimeException(
                'Solo pueden registrarse facturas emitidas.'
            );
        }

        if (
            trim(
                $invoice->getSellerTaxId()
            ) === ''
        ) {
            throw new RuntimeException(
                'La factura no tiene NIF del emisor.'
            );
        }

        if (
            trim(
                $invoice->getFullNumber()
            ) === ''
        ) {
            throw new RuntimeException(
                'La factura no tiene número.'
            );
        }

        if ($invoice->getTotal() <= 0) {
            throw new RuntimeException(
                'El importe de la factura no es válido.'
            );
        }

        if ($invoice->getItems() === []) {
            throw new RuntimeException(
                'La factura no tiene líneas.'
            );
        }
    }

    private function resolveInvoiceType(
        Invoice $invoice
    ): string {
        /*
         * DSM emite actualmente factura completa
         * con receptor identificado.
         *
         * Antes del XML definitivo verificaremos
         * todas las reglas F1/F2 contra el XSD y
         * validaciones de AEAT.
         */
        return 'F1';
    }

    private function resolveDescription(
        Invoice $invoice
    ): string {
        $descriptions = [];

        foreach (
            $invoice->getItems()
            as $item
        ) {
            $description =
                trim(
                    $item->getDescription()
                );

            if ($description !== '') {
                $descriptions[] =
                    $description;
            }
        }

        $description =
            implode(
                ' | ',
                $descriptions
            );

        if ($description === '') {
            return 'Servicios DeSegundaMuda';
        }

        return mb_substr(
            $description,
            0,
            500
        );
    }

    /**
     * Convierte UTC de la factura al formato fiscal
     * DD-MM-YYYY.
     */
    private function formatInvoiceDate(
        string $issuedAt
    ): string {
        $date =
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $issuedAt,
                new DateTimeZone(
                    'UTC'
                )
            );

        if ($date === false) {
            throw new RuntimeException(
                'La fecha de emisión de la factura no es válida.'
            );
        }

        return $date
            ->format(
                'd-m-Y'
            );
    }

    private function invoiceDateToDatabase(
        string $invoiceDate
    ): string {
        $date =
            DateTimeImmutable::createFromFormat(
                '!d-m-Y',
                $invoiceDate
            );

        if ($date === false) {
            throw new RuntimeException(
                'No se pudo convertir la fecha fiscal de la factura.'
            );
        }

        return $date
            ->format(
                'Y-m-d'
            );
    }

    private function assertPreviousRecordIntegrity(
        int $previousRecordId,
        string $expectedHash
    ): void {
        $previous =
            $this->recordRepository
                ->findById(
                    $previousRecordId
                );

        if ($previous === null) {
            throw new RuntimeException(
                'La cadena VERI*FACTU apunta a un registro inexistente.'
            );
        }

        $storedHash =
            $previous
                ->getHashValue();

        if (
            $storedHash === null
            || !hash_equals(
                $storedHash,
                $expectedHash
            )
        ) {
            throw new RuntimeException(
                'La huella del último registro VERI*FACTU no coincide con la cadena.'
            );
        }

        /*
         * Verificación adicional:
         * recalculamos la huella desde la cadena original
         * almacenada.
         */
        $hashInput =
            $previous
                ->getHashInput();

        if ($hashInput === null) {
            throw new RuntimeException(
                'El registro anterior no conserva la entrada utilizada para su huella.'
            );
        }

        $recalculated =
            strtoupper(
                hash(
                    'sha256',
                    $hashInput
                )
            );

        if (
            !hash_equals(
                $storedHash,
                $recalculated
            )
        ) {
            throw new RuntimeException(
                'La huella almacenada del registro VERI*FACTU anterior no supera la comprobación de integridad.'
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTaxBreakdown(
        Invoice $invoice
    ): array {
        $rows = [];

        foreach (
            $invoice->getItems()
            as $item
        ) {
            $rows[] = [
                'tax_type' =>
                    $item
                        ->getTaxType(),

                'tax_rate' =>
                    $item
                        ->getTaxRate(),

                'tax_base' =>
                    $item
                        ->getTaxBase(),

                'tax_amount' =>
                    $item
                        ->getTaxAmount(),

                'line_total' =>
                    $item
                        ->getLineTotal(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInvoiceSnapshot(
        Invoice $invoice
    ): array {
        $items = [];

        foreach (
            $invoice->getItems()
            as $item
        ) {
            $items[] = [
                'description' =>
                    $item
                        ->getDescription(),

                'quantity' =>
                    $item
                        ->getQuantity(),

                'unit_price' =>
                    $item
                        ->getUnitPrice(),

                'tax_base' =>
                    $item
                        ->getTaxBase(),

                'tax_rate' =>
                    $item
                        ->getTaxRate(),

                'tax_amount' =>
                    $item
                        ->getTaxAmount(),

                'line_total' =>
                    $item
                        ->getLineTotal(),
            ];
        }

        return [
            'invoice_id' =>
                $invoice->getId(),

            'customer_id' =>
                $invoice->getCustomerId(),

            'payment_id' =>
                $invoice->getPaymentId(),

            'document_type' =>
                $invoice->getDocumentType(),

            'full_number' =>
                $invoice->getFullNumber(),

            'issued_at' =>
                $invoice->getIssuedAt(),

            'currency' =>
                $invoice->getCurrency(),

            'subtotal' =>
                $invoice->getSubtotal(),

            'tax_total' =>
                $invoice->getTaxTotal(),

            'total' =>
                $invoice->getTotal(),

            'tax_type' =>
                $invoice->getTaxType(),

            'tax_rate' =>
                $invoice->getTaxRate(),

            'seller_fiscal_name' =>
                $invoice->getSellerFiscalName(),

            'seller_tax_id' =>
                $invoice->getSellerTaxId(),

            'customer_fiscal_name' =>
                $invoice->getCustomerFiscalName(),

            'customer_tax_id' =>
                $invoice->getCustomerTaxId(),

            'items' =>
                $items,
        ];
    }
}
