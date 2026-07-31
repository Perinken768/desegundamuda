<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Profile\CustomerProfile;
use DSM\Clientes\Profile\CustomerProfileRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

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
        ?string $whatsappPhone,
        ?string $bio
    ): CustomerProfile {
        $displayName = trim($displayName);

        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible no puede estar vacío.'
            );
        }

        if (mb_strlen($displayName) > 150) {
            throw new RuntimeException(
                'El nombre visible es demasiado largo.'
            );
        }

        $phone = $this->validatePhone(
            $phone,
            'El número de teléfono no es válido.'
        );

        $whatsappPhone = $this->validatePhone(
            $whatsappPhone,
            'El número de WhatsApp no es válido.'
        );

        $bio = $bio !== null
            ? trim($bio)
            : null;

        if ($bio !== null && mb_strlen($bio) > 2000) {
            throw new RuntimeException(
                'La biografía es demasiado larga.'
            );
        }

        return $this->profileRepository->update(
            $customerId,
            $displayName,
            $phone,
            $whatsappPhone,
            $bio
        );
    }

    private function validatePhone(
        ?string $phone,
        string $errorMessage
    ): ?string {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        if (
            preg_match(
                '/^[0-9+\s().-]{6,30}$/',
                $phone
            ) !== 1
        ) {
            throw new RuntimeException(
                $errorMessage
            );
        }

        return $phone;
    }
}