<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Profile\CustomerProfile;
use DSM\Clientes\Profile\CustomerProfileRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Actualiza los datos públicos y de contacto del cliente.
 *
 * Para el MVP:
 *
 * - phone es el único número de contacto;
 * - allow_phone_calls habilita llamadas;
 * - allow_whatsapp habilita WhatsApp;
 * - whatsapp_phone queda reservado y no se utiliza.
 */
final class UpdateCustomerProfile
{
    public function __construct(
        private readonly CustomerProfileRepository $profileRepository
    ) {
    }

    public function execute(
        int $customerId,
        string $displayName,
        ?string $phone,
        bool $allowPhoneCalls,
        bool $allowWhatsapp,
        ?string $bio
    ): CustomerProfile {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $displayName =
            sanitize_text_field(
                trim($displayName)
            );

        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $displayName
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre visible es demasiado largo.'
            );
        }

        $phone =
            $this->normalizeNullablePhone(
                $phone
            );

        $bio =
            $this->normalizeNullableBio(
                $bio
            );

        if (
            $bio !== null
            && mb_strlen($bio) > 2000
        ) {
            throw new RuntimeException(
                'La biografía es demasiado larga.'
            );
        }

        /*
         * Si se activa algún método de contacto,
         * debe existir un teléfono.
         */
        if (
            (
                $allowPhoneCalls
                || $allowWhatsapp
            )
            && $phone === null
        ) {
            throw new RuntimeException(
                'Debes indicar un número de teléfono para activar llamadas o WhatsApp.'
            );
        }

        /*
         * Si no hay teléfono, no puede quedar ningún
         * método de contacto activo.
         */
        if ($phone === null) {
            $allowPhoneCalls =
                false;

            $allowWhatsapp =
                false;
        }

        return $this->profileRepository->update(
            $customerId,
            $displayName,
            $phone,
            $allowPhoneCalls,
            $allowWhatsapp,
            $bio
        );
    }

    private function normalizeNullablePhone(
        ?string $phone
    ): ?string {
        if ($phone === null) {
            return null;
        }

        $phone =
            sanitize_text_field(
                trim($phone)
            );

        if ($phone === '') {
            return null;
        }

        /*
         * La normalización definitiva y la validación E.164
         * se realizan en CustomerProfileRepository.
         *
         * Aquí solo se bloquean caracteres claramente inválidos.
         */
        if (
            preg_match(
                '/^[0-9+\s().-]{6,30}$/',
                $phone
            ) !== 1
        ) {
            throw new RuntimeException(
                'El número de teléfono no es válido.'
            );
        }

        return $phone;
    }

    private function normalizeNullableBio(
        ?string $bio
    ): ?string {
        if ($bio === null) {
            return null;
        }

        $bio =
            sanitize_textarea_field(
                trim($bio)
            );

        return $bio === ''
            ? null
            : $bio;
    }
}