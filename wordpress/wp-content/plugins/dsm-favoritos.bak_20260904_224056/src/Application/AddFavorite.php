<?php

declare(strict_types=1);

namespace DSM\Favoritos\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Caso de uso para añadir un anuncio a favoritos.
 *
 * Reglas:
 *
 * - el cliente debe existir;
 * - el cliente debe estar activo;
 * - el anuncio debe existir;
 * - el anuncio debe ser público;
 * - el cliente no puede marcar su propio anuncio;
 * - una relación cliente/anuncio no puede duplicarse.
 */
final class AddFavorite
{
    private FavoriteRepository $favoriteRepository;

    private AdvertisementRepository $advertisementRepository;

    public function __construct(
        ?FavoriteRepository $favoriteRepository = null,
        ?AdvertisementRepository $advertisementRepository = null
    ) {
        $this->favoriteRepository =
            $favoriteRepository
            ?? new FavoriteRepository();

        $this->advertisementRepository =
            $advertisementRepository
            ?? new AdvertisementRepository();
    }

    public function execute(
        int $customerId,
        int $advertisementId
    ): Favorite {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente.'
            );
        }

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El anuncio indicado no es válido.'
            );
        }

        /*
         * Validación neutral contra DSM Clientes.
         *
         * DSM Favoritos no conoce CustomerRepository ni
         * las tablas internas de clientes.
         */
        $customerContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $customerId
            );

        if (!is_array($customerContext)) {
            throw new RuntimeException(
                'No se encontró el cliente indicado.'
            );
        }

        $resolvedCustomerId =
            max(
                0,
                (int) (
                    $customerContext['id']
                    ?? 0
                )
            );

        if (
            $resolvedCustomerId <= 0
            || $resolvedCustomerId !== $customerId
        ) {
            throw new RuntimeException(
                'El cliente indicado no es válido.'
            );
        }

        $customerStatus =
            sanitize_key(
                (string) (
                    $customerContext['status']
                    ?? ''
                )
            );

        if ($customerStatus !== 'active') {
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }

        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'El anuncio indicado no existe.'
            );
        }

        if (!$advertisement->isPublic()) {
            throw new RuntimeException(
                'El anuncio no está disponible públicamente.'
            );
        }

        if (
            $advertisement->getCustomerId()
            === $customerId
        ) {
            throw new RuntimeException(
                'No puedes añadir tu propio anuncio a favoritos.'
            );
        }

        return $this->favoriteRepository
            ->create(
                $customerId,
                $advertisementId
            );
    }
}