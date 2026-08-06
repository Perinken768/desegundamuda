<?php

declare(strict_types=1);

namespace DSM\Clientes\Profile;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Perfil público y privado de un cliente DSM.
 *
 * La ubicación utiliza nombres territoriales neutrales:
 *
 * - area_id
 * - municipality_id
 *
 * Un área puede representar una isla, provincia, región,
 * comarca o cualquier otra división territorial gestionada
 * por DSM Ubicaciones.
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
        private readonly ?int $areaId,
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function allowsPhoneCalls(): bool
    {
        return $this->allowPhoneCalls;
    }

    public function getWhatsappPhone(): ?string
    {
        return $this->whatsappPhone;
    }

    public function allowsWhatsapp(): bool
    {
        return $this->allowWhatsapp;
    }

    public function getAvatarAttachmentId(): ?int
    {
        return $this->avatarAttachmentId;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * Devuelve el área territorial seleccionada.
     *
     * Puede representar una isla, provincia, región,
     * comarca u otra división territorial.
     */
    public function getAreaId(): ?int
    {
        return $this->areaId;
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
     * Indica si existe un teléfono válido y al menos
     * un método de contacto autorizado.
     */
    public function hasValidContactMethod(): bool
    {
        $phone =
            trim(
                (string) $this->phone
            );

        return $phone !== ''
            && (
                $this->allowPhoneCalls
                || $this->allowWhatsapp
            );
    }

    /**
     * Devuelve el teléfono para enlaces tel:.
     */
    public function getPhoneCallUrl(): ?string
    {
        if (
            !$this->allowPhoneCalls
            || !$this->hasValidContactMethod()
        ) {
            return null;
        }

        $phone =
            $this->normalizePhoneForUrl(
                (string) $this->phone
            );

        if ($phone === '') {
            return null;
        }

        return 'tel:'
            . $phone;
    }

    /**
     * Devuelve la URL básica de WhatsApp.
     *
     * El mensaje específico del anuncio se añade desde
     * la integración pública de DSM Clientes.
     */
    public function getWhatsappUrl(): ?string
    {
        if (
            !$this->allowWhatsapp
            || !$this->hasValidContactMethod()
        ) {
            return null;
        }

        $phone =
            $this->normalizePhoneForUrl(
                (string) $this->phone
            );

        if ($phone === '') {
            return null;
        }

        return 'https://wa.me/'
            . ltrim(
                $phone,
                '+'
            );
    }

    /**
     * Normaliza un teléfono al formato internacional.
     *
     * Los números nacionales de nueve cifras se consideran
     * españoles durante el MVP y reciben automáticamente +34.
     *
     * Ejemplos:
     *
     * 645382819       → +34645382819
     * 645 382 819     → +34645382819
     * +34 645382819   → +34645382819
     * 0034 645382819  → +34645382819
     */
    public static function normalizePhone(
        ?string $phone
    ): string {
        if ($phone === null) {
            return '';
        }

        $phone =
            trim($phone);

        if ($phone === '') {
            return '';
        }

        $phone =
            preg_replace(
                '/[\s().-]+/',
                '',
                $phone
            );

        if (!is_string($phone)) {
            return '';
        }

        if (
            str_starts_with(
                $phone,
                '00'
            )
        ) {
            $phone =
                '+'
                . substr(
                    $phone,
                    2
                );
        }

        if (
            !str_starts_with(
                $phone,
                '+'
            )
        ) {
            $digits =
                preg_replace(
                    '/\D+/',
                    '',
                    $phone
                );

            if (
                !is_string($digits)
                || $digits === ''
            ) {
                return '';
            }

            if (strlen($digits) === 9) {
                return '+34'
                    . $digits;
            }

            return $digits;
        }

        $digits =
            preg_replace(
                '/\D+/',
                '',
                $phone
            );

        if (
            !is_string($digits)
            || $digits === ''
        ) {
            return '';
        }

        return '+'
            . $digits;
    }

    /**
     * Convierte el perfil en una estructura neutral
     * para integraciones con otros plugins DSM.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' =>
                $this->id,

            'customer_id' =>
                $this->customerId,

            'display_name' =>
                $this->displayName,

            'phone' =>
                $this->phone,

            'allow_phone_calls' =>
                $this->allowPhoneCalls,

            'whatsapp_phone' =>
                $this->whatsappPhone,

            'allow_whatsapp' =>
                $this->allowWhatsapp,

            'avatar_attachment_id' =>
                $this->avatarAttachmentId,

            'bio' =>
                $this->bio,

            'area_id' =>
                $this->areaId,

            'municipality_id' =>
                $this->municipalityId,

            'created_at' =>
                $this->createdAt,

            'updated_at' =>
                $this->updatedAt,
        ];
    }

    private function normalizePhoneForUrl(
        string $phone
    ): string {
        return self::normalizePhone(
            $phone
        );
    }
}