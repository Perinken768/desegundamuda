<?php

declare(strict_types=1);

namespace DSM\Favoritos\Application;

use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

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
        string $itemType,
        int $itemId
    ): bool {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente.'
            );
        }

        $itemType =
            sanitize_key(
                $itemType
            );

        if (
            !Favorite::isValidType(
                $itemType
            )
        ) {
            throw new RuntimeException(
                'El tipo de favorito indicado no es válido.'
            );
        }

        if ($itemId <= 0) {
            throw new RuntimeException(
                'El elemento indicado no es válido.'
            );
        }

        if (
            !$this->favoriteRepository
                ->existsItem(
                    $customerId,
                    $itemType,
                    $itemId
                )
        ) {
            return true;
        }

        $deleted =
            $this->favoriteRepository
                ->deleteByCustomerAndItem(
                    $customerId,
                    $itemType,
                    $itemId
                );

        if (!$deleted) {
            throw new RuntimeException(
                'No se pudo quitar el elemento de favoritos.'
            );
        }

        return true;
    }

    public function executeAdvertisement(
        int $customerId,
        int $advertisementId
    ): bool {
        return $this->execute(
            $customerId,
            Favorite::TYPE_ADVERTISEMENT,
            $advertisementId
        );
    }
}
