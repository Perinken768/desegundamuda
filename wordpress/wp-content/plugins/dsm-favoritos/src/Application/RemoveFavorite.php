<?php

declare(strict_types=1);

namespace DSM\Favoritos\Application;

use DSM\Favoritos\Favorite\FavoriteRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Caso de uso para quitar un anuncio de favoritos.
 *
 * La eliminación se realiza siempre mediante la pareja:
 *
 * customer_id + advertisement_id
 *
 * De esta forma un cliente nunca puede eliminar
 * accidentalmente el favorito perteneciente a otro cliente.
 *
 * La operación es idempotente:
 * si el favorito ya no existe, el estado final sigue siendo
 * correcto y no se considera un error.
 */
final class RemoveFavorite
{
    private FavoriteRepository $favoriteRepository;

    public function __construct(
        ?FavoriteRepository $favoriteRepository = null
    ) {
        $this->favoriteRepository =
            $favoriteRepository
            ?? new FavoriteRepository();
    }

    public function execute(
        int $customerId,
        int $advertisementId
    ): bool {
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
         * Si no existe, consideramos que el objetivo
         * del caso de uso ya está cumplido.
         */
        if (
            !$this->favoriteRepository
                ->exists(
                    $customerId,
                    $advertisementId
                )
        ) {
            return true;
        }

        $deleted =
            $this->favoriteRepository
                ->deleteByCustomerAndAdvertisement(
                    $customerId,
                    $advertisementId
                );

        if (!$deleted) {
            throw new RuntimeException(
                'No se pudo quitar el anuncio de favoritos.'
            );
        }

        return true;
    }
}
