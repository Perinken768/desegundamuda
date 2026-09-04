<?php

declare(strict_types=1);

namespace DSM\Facturacion\Verifactu;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuRecord
{
    public function __construct(
        private readonly int $id,
        private readonly string $recordUuid,
        private readonly int $chainId,
        private readonly int $chainSequence,
        private readonly int $invoiceId,
        private readonly string $recordType,
        private readonly string $generationType,
        private readonly int $generationSequence,
        private readonly ?int $sourceRecordId,
        private readonly ?string $subsanacion,
        private readonly ?string $rechazoPrevio,
        private readonly ?string $sinRegistroPrevio,
        private readonly string $fiscalStatus,
        private readonly string $environment,
        private readonly string $issuerFiscalName,
        private readonly string $issuerTaxId,
        private readonly string $invoiceNumber,
        private readonly string $invoiceDate,
        private readonly ?string $invoiceType,
        private readonly ?string $description,
        private readonly string $currency,
        private readonly ?string $taxType,
        private readonly ?float $taxRate,
        private readonly float $taxBase,
        private readonly float $taxAmount,
        private readonly float $totalAmount,
        private readonly ?int $previousRecordId,
        private readonly ?string $previousHash,
        private readonly string $hashAlgorithm,
        private readonly ?string $hashInput,
        private readonly ?string $hashValue,
        private readonly string $generatedAt,
        private readonly string $generatedAtIso,
        private readonly ?string $aeatRecordStatus,
        private readonly string $createdAt,
        private readonly string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRecordUuid(): string
    {
        return $this->recordUuid;
    }

    public function getChainId(): int
    {
        return $this->chainId;
    }

    public function getChainSequence(): int
    {
        return $this->chainSequence;
    }

    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    public function getGenerationType(): string
    {
        return $this->generationType;
    }

    public function getGenerationSequence(): int
    {
        return $this->generationSequence;
    }

    public function getSourceRecordId(): ?int
    {
        return $this->sourceRecordId;
    }

    public function getSubsanacion(): ?string
    {
        return $this->subsanacion;
    }

    public function getRechazoPrevio(): ?string
    {
        return $this->rechazoPrevio;
    }

    public function getSinRegistroPrevio(): ?string
    {
        return $this->sinRegistroPrevio;
    }

    public function getFiscalStatus(): string
    {
        return $this->fiscalStatus;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getIssuerFiscalName(): string
    {
        return $this->issuerFiscalName;
    }

    public function getIssuerTaxId(): string
    {
        return $this->issuerTaxId;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function getInvoiceDate(): string
    {
        return $this->invoiceDate;
    }

    public function getInvoiceType(): ?string
    {
        return $this->invoiceType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTaxType(): ?string
    {
        return $this->taxType;
    }

    public function getTaxRate(): ?float
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

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getPreviousRecordId(): ?int
    {
        return $this->previousRecordId;
    }

    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    public function getHashAlgorithm(): string
    {
        return $this->hashAlgorithm;
    }

    public function getHashInput(): ?string
    {
        return $this->hashInput;
    }

    public function getHashValue(): ?string
    {
        return $this->hashValue;
    }

    public function getGeneratedAt(): string
    {
        return $this->generatedAt;
    }

    public function getGeneratedAtIso(): string
    {
        return $this->generatedAtIso;
    }

    public function getAeatRecordStatus(): ?string
    {
        return $this->aeatRecordStatus;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
