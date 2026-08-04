<?php

declare(strict_types=1);

namespace DSM\Clientes\Profile;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Perfil público y de contacto de un cliente.
 *
 * Para el MVP:
 *
 * - phone es el único número de contacto utilizado;
 * - allow_phone_calls indica si acepta llamadas;
 * - allow_whatsapp indica si acepta WhatsApp;
 * - whatsapp_phone se conserva únicamente por compatibilidad
 *   y posibles integraciones futuras.
 */
final class CustomerProfile
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly string $displayName,
        private readonly ?string $phone,
        private readonly bool $allowPhoneCalls,
        private readonly ?string $whatsappPhone,
        private readonly bool $allowWhatsapp,
        private readonly ?int $avatarAttachmentId,
        private readonly ?string $bio,
        private readonly ?int $islandId,
        private readonly ?int $municipalityId,
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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Devuelve el teléfono de contacto en formato internacional.
     *
     * Ejemplo:
     *
     * +34600123456
     */
    public function getPhone(): ?string
    {
        $phone =
            self::normalizePhone(
                $this->phone
            );

        return $phone !== ''
            ? $phone
            : null;
    }

    public function allowsPhoneCalls(): bool
    {
        return $this->allowPhoneCalls;
    }

    /**
     * Campo reservado para futuras integraciones.
     *
     * En el MVP no se utiliza para generar enlaces de WhatsApp.
     */
    public function getWhatsappPhone(): ?string
    {
        $value =
            trim(
                (string) $this->whatsappPhone
            );

        return $value !== ''
            ? $value
            : null;
    }

    public function allowsWhatsapp(): bool
    {
        return $this->allowWhatsapp;
    }

    /**
     * Indica si el cliente tiene un teléfono válido y ha
     * autorizado al menos un método de contacto.
     */
    public function hasValidContactMethod(): bool
    {
        return $this->getPhone() !== null
            && (
                $this->allowsPhoneCalls()
                || $this->allowsWhatsapp()
            );
    }

    /**
     * Devuelve el número preparado para enlaces tel:.
     */
    public function getPhoneCallUri(): ?string
    {
        if (
            !$this->allowsPhoneCalls()
            || $this->getPhone() === null
        ) {
            return null;
        }

        return 'tel:'
            . $this->getPhone();
    }

    /**
     * Devuelve el número preparado para wa.me.
     *
     * WhatsApp no admite el símbolo + en la URL.
     */
    public function getWhatsappNumber(): ?string
    {
        if (
            !$this->allowsWhatsapp()
            || $this->getPhone() === null
        ) {
            return null;
        }

        return ltrim(
            $this->getPhone(),
            '+'
        );
    }

    public function getAvatarAttachmentId(): ?int
    {
        return $this->avatarAttachmentId;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getIslandId(): ?int
    {
        return $this->islandId;
    }

    public function getMunicipalityId(): ?int
    {
        return $this->municipalityId;
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
     * Normaliza números españoles e internacionales.
     *
     * Ejemplos:
     *
     * 600 123 456
     * 600-123-456
     * 0034 600 123 456
     * +34 600 123 456
     *
     * Resultado:
     *
     * +34600123456
     */
    public static function normalizePhone(
        ?string $phone
    ): string {
        $phone =
            trim(
                (string) $phone
            );

        if ($phone === '') {
            return '';
        }

        /*
         * Conserva únicamente números y un posible prefijo +.
         */
        $phone =
            preg_replace(
                '/(?!^\+)[^\d]/',
                '',
                $phone
            );

        if (!is_string($phone)) {
            return '';
        }

        /*
         * Convierte prefijos internacionales del tipo 0034.
         */
        if (str_starts_with($phone, '00')) {
            $phone =
                '+'
                . substr(
                    $phone,
                    2
                );
        }

        /*
         * Si no hay prefijo internacional, asumimos España.
         */
        if (!str_starts_with($phone, '+')) {
            $phone =
                '+34'
                . $phone;
        }

        /*
         * Elimina cualquier carácter que no sea número
         * después del símbolo + inicial.
         */
        $digits =
            preg_replace(
                '/\D/',
                '',
                $phone
            );

        if (!is_string($digits)) {
            return '';
        }

        if ($digits === '') {
            return '';
        }

        return '+'
            . $digits;
    }
}