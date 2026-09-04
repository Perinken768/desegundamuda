<?php

declare(strict_types=1);

namespace DSM\Facturacion\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

final class InvoiceItem
{
    public function __construct(
        private readonly int $id,
        private readonly int $invoiceId,
        private readonly string $description,
        private readonly float $quantity,
        private readonly float $unitPrice,
        private readonly float $lineSubtotal,
        private readonly string $taxType,
        private readonly float $taxRate,
        private readonly float $taxBase,
        private readonly float $taxAmount,
        private readonly float $lineTotal,
        private readonly int $sortOrder,
        private readonly string $createdAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getLineSubtotal(): float
    {
        return $this->lineSubtotal;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getTaxBase(): float
    {
        return $this->taxBase;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function getLineTotal(): float
    {
        return $this->lineTotal;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
