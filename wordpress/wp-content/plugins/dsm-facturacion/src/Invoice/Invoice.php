<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

final class Invoice
{
    /**
     * @param InvoiceItem[] $items
     */
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly int $paymentId,
        private readonly string $documentType,
        private readonly string $status,
        private readonly string $series,
        private readonly int $sequenceNumber,
        private readonly int $fiscalYear,
        private readonly string $fullNumber,
        private readonly string $issuedAt,
        private readonly string $currency,
        private readonly bool $pricesIncludeTax,
        private readonly float $subtotal,
        private readonly float $taxTotal,
        private readonly float $total,
        private readonly string $taxType,
        private readonly float $taxRate,
        private readonly string $sellerFiscalName,
        private readonly string $sellerTaxId,
        private readonly string $customerFiscalName,
        private readonly ?string $customerTaxId,
        private readonly ?string $pdfRelativePath,
        private readonly ?string $pdfGeneratedAt,
        private readonly string $createdAt,
        private readonly string $updatedAt,
        private readonly array $items = []
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function getFiscalYear(): int
    {
        return $this->fiscalYear;
    }

    public function getFullNumber(): string
    {
        return $this->fullNumber;
    }

    public function getIssuedAt(): string
    {
        return $this->issuedAt;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function pricesIncludeTax(): bool
    {
        return $this->pricesIncludeTax;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getTaxTotal(): float
    {
        return $this->taxTotal;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getSellerFiscalName(): string
    {
        return $this->sellerFiscalName;
    }

    public function getSellerTaxId(): string
    {
        return $this->sellerTaxId;
    }

    public function getCustomerFiscalName(): string
    {
        return $this->customerFiscalName;
    }

    public function getCustomerTaxId(): ?string
    {
        return $this->customerTaxId;
    }

    public function getPdfRelativePath(): ?string
    {
        return $this->pdfRelativePath;
    }

    public function getPdfGeneratedAt(): ?string
    {
        return $this->pdfGeneratedAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * @return InvoiceItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
