<?php

declare(strict_types=1);

namespace DSM\Whatsapp\Application;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class WhatsAppLinkBuilder
{
    public function build(
        string $phone,
        string $message
    ): string {
        $phone =
            $this->normalizePhone(
                $phone
            );

        if ($phone === '') {
            throw new RuntimeException(
                'No se pudo obtener un teléfono válido para WhatsApp.'
            );
        }

        $message =
            trim(
                $message
            );

        if ($message === '') {
            throw new RuntimeException(
                'El mensaje de WhatsApp no puede estar vacío.'
            );
        }

        return sprintf(
            'https://wa.me/%s?text=%s',
            $phone,
            rawurlencode(
                $message
            )
        );
    }

    public function buildForCustomer(
        int $customerId,
        string $message
    ): string {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El cliente destinatario no es válido.'
            );
        }

        $contact =
            apply_filters(
                'dsm_customer_whatsapp_contact_by_id',
                null,
                $customerId
            );

        if (!is_array($contact)) {
            throw new RuntimeException(
                'El cliente no tiene información de WhatsApp disponible.'
            );
        }

        if (
            empty(
                $contact[
                    'allow_whatsapp'
                ]
            )
        ) {
            throw new RuntimeException(
                'El cliente no permite contacto por WhatsApp.'
            );
        }

        $phone =
            trim(
                (string) (
                    $contact['phone']
                    ?? ''
                )
            );

        return $this->build(
            $phone,
            $message
        );
    }

    private function normalizePhone(
        string $phone
    ): string {
        $phone =
            trim(
                $phone
            );

        if ($phone === '') {
            return '';
        }

        /*
         * wa.me utiliza el número en formato
         * internacional, pero sin +, espacios
         * ni caracteres de formato.
         */
        $phone =
            preg_replace(
                '/[^0-9]/',
                '',
                $phone
            );

        if (!is_string($phone)) {
            return '';
        }

        return $phone;
    }
}
