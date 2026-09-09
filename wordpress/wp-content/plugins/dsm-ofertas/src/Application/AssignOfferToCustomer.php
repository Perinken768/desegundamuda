<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DSM\Clientes\Customer\CustomerRepository;
use DSM\Ofertas\Offer\OfferCustomerRepository;
use DSM\Ofertas\Offer\OfferRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AssignOfferToCustomer
{
    public function execute(
        int $offerId,
        string $customerReference,
        ?string $internalNote = null
    ): int {
        if ($offerId <= 0) {
            throw new RuntimeException(
                'La oferta indicada no es válida.'
            );
        }

        $offer =
            (
                new OfferRepository()
            )->findById(
                $offerId
            );

        if ($offer === null) {
            throw new RuntimeException(
                'No se encontró la oferta.'
            );
        }

        if (
            (string) $offer->scope
            !== 'customers'
        ) {
            throw new RuntimeException(
                'Solo se pueden asignar clientes a una oferta con ámbito "Clientes concretos".'
            );
        }

        $customerReference =
            trim(
                $customerReference
            );

        if ($customerReference === '') {
            throw new RuntimeException(
                'Debes indicar un cliente.'
            );
        }

        $customerRepository =
            new CustomerRepository();

        if (
            ctype_digit(
                $customerReference
            )
        ) {
            $customer =
                $customerRepository
                    ->findById(
                        (int)
                        $customerReference
                    );
        } else {
            $email =
                sanitize_email(
                    $customerReference
                );

            if ($email === '') {
                throw new RuntimeException(
                    'El email indicado no es válido.'
                );
            }

            $customer =
                $customerRepository
                    ->findByEmail(
                        $email
                    );
        }

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        if (
            $customer->getStatus()
            !== 'active'
        ) {
            throw new RuntimeException(
                'El cliente indicado no está activo.'
            );
        }

        return (
            new OfferCustomerRepository()
        )->assign(
            $offerId,
            $customer->getId(),
            $internalNote
        );
    }
}
