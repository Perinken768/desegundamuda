<?php

declare(strict_types=1);

namespace DSM\Promocionar\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class AdvertisementContext
{
    /**
     * Exige que el anuncio:
     *
     * - exista;
     * - pertenezca al cliente indicado;
     * - esté activo;
     * - sea público;
     * - no esté reservado;
     * - no esté cerrado.
     *
     * @return array<string, mixed>
     */
    public static function requirePromotable(
        int $advertisementId,
        int $customerId
    ): array {
        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $context =
            apply_filters(
                'dsm_advertisement_context_by_id',
                null,
                $advertisementId
            );

        if (!is_array($context)) {
            throw new RuntimeException(
                'No se encontró el anuncio indicado.'
            );
        }

        $resolvedAdvertisementId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if (
            $resolvedAdvertisementId <= 0
            || $resolvedAdvertisementId
                !== $advertisementId
        ) {
            throw new RuntimeException(
                'El anuncio indicado no es válido.'
            );
        }

        $advertisementCustomerId =
            max(
                0,
                (int) (
                    $context['customer_id']
                    ?? 0
                )
            );

        if (
            $advertisementCustomerId <= 0
            || $advertisementCustomerId
                !== $customerId
        ) {
            throw new RuntimeException(
                'El anuncio no pertenece al cliente indicado.'
            );
        }

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            throw new RuntimeException(
                'Solo se pueden promocionar anuncios activos.'
            );
        }

        if (empty($context['is_public'])) {
            throw new RuntimeException(
                'El anuncio no está disponible públicamente.'
            );
        }

        if (!empty($context['is_reserved'])) {
            throw new RuntimeException(
                'Un anuncio reservado no puede iniciar una promoción.'
            );
        }

        if (!empty($context['is_closed'])) {
            throw new RuntimeException(
                'Un anuncio cerrado no puede promocionarse.'
            );
        }

        return $context;
    }

    private function __construct()
    {
    }
}
