<?php

declare(strict_types=1);

namespace DSM\Facturacion\Billing;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingProfile
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $customerType,
        private readonly string $fiscalName,
        private readonly ?string $taxId,
        private readonly ?string $addressLine1,
        private readonly ?string $addressLine2,
        private readonly ?string $postalCode,
        private readonly ?string $city,
        private readonly ?string $province,
        private readonly string $countryCode,
        private readonly ?string $billingEmail,
        private readonly string $createdAt,
        private readonly string $updatedAt
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

    public function getCustomerType(): string
    {
        return $this->customerType;
    }

    public function getFiscalName(): string
    {
        return $this->fiscalName;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isComplete(): bool
    {
        if (
            trim($this->fiscalName) === ''
            || trim((string) $this->addressLine1) === ''
            || trim((string) $this->postalCode) === ''
            || trim((string) $this->city) === ''
            || trim((string) $this->province) === ''
        ) {
            return false;
        }

        /*
         * Mientras DSM emita facturas españolas F1,
         * un destinatario español debe disponer de
         * identificación fiscal válida.
         */
        if (
            strtoupper(
                trim(
                    $this->countryCode
                )
            ) === 'ES'
        ) {
            return SpanishTaxIdValidator::isValid(
                (string) $this->taxId
            );
        }

        return true;
    }
}
