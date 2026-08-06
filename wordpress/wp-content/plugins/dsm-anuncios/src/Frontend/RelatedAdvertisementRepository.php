<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene anuncios relacionados para una ficha pública.
 *
 * Prioridad:
 *
 * 1. Misma categoría y misma área.
 * 2. Misma categoría.
 * 3. Misma área.
 * 4. Otros anuncios del mismo vendedor.
 * 5. Anuncios públicos recientes.
 *
 * El anuncio actual siempre queda excluido.
 */
final class RelatedAdvertisementRepository
{
    public const DEFAULT_LIMIT =
        4;

    public const MAX_LIMIT =
        12;

    public function __construct(
        private readonly AdvertisementSearchRepository $repository
    ) {
    }

    /**
     * @param array<string, mixed> $advertisement
     *
     * @return array<int, array<string, mixed>>
     */
    public function findRelated(
        array $advertisement,
        int $limit = self::DEFAULT_LIMIT
    ): array {
        $advertisementId =
            max(
                0,
                (int) (
                    $advertisement['id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            return [];
        }

        $limit =
            min(
                self::MAX_LIMIT,
                max(
                    1,
                    $limit
                )
            );

        $categoryId =
            max(
                0,
                (int) (
                    $advertisement['category_id']
                    ?? 0
                )
            );

        $areaId =
            max(
                0,
                (int) (
                    $advertisement['area_id']
                    ?? 0
                )
            );

        $customerId =
            max(
                0,
                (int) (
                    $advertisement['customer_id']
                    ?? 0
                )
            );

        $related = [];

        /*
         * 1. Misma categoría y misma área.
         */
        if (
            $categoryId > 0
            && $areaId > 0
        ) {
            $related =
                $this->appendSearchResults(
                    $related,
                    [
                        'category_id' =>
                            $categoryId,

                        'area_id' =>
                            $areaId,

                        'orderby' =>
                            'published_at',

                        'order' =>
                            'DESC',
                    ],
                    $advertisementId,
                    $limit
                );
        }

        /*
         * 2. Misma categoría, sin limitar el área.
         */
        if (
            count($related) < $limit
            && $categoryId > 0
        ) {
            $related =
                $this->appendSearchResults(
                    $related,
                    [
                        'category_id' =>
                            $categoryId,

                        'orderby' =>
                            'published_at',

                        'order' =>
                            'DESC',
                    ],
                    $advertisementId,
                    $limit
                );
        }

        /*
         * 3. Misma área.
         */
        if (
            count($related) < $limit
            && $areaId > 0
        ) {
            $related =
                $this->appendSearchResults(
                    $related,
                    [
                        'area_id' =>
                            $areaId,

                        'orderby' =>
                            'published_at',

                        'order' =>
                            'DESC',
                    ],
                    $advertisementId,
                    $limit
                );
        }

        /*
         * 4. Otros anuncios del mismo vendedor.
         *
         * Se utiliza como señal secundaria, porque puede
         * resultar útil mostrar más artículos del vendedor.
         */
        if (
            count($related) < $limit
            && $customerId > 0
        ) {
            $related =
                $this->appendSearchResults(
                    $related,
                    [
                        'customer_id' =>
                            $customerId,

                        'orderby' =>
                            'published_at',

                        'order' =>
                            'DESC',
                    ],
                    $advertisementId,
                    $limit
                );
        }

        /*
         * 5. Últimos anuncios públicos como último recurso.
         */
        if (count($related) < $limit) {
            $related =
                $this->appendSearchResults(
                    $related,
                    [
                        'orderby' =>
                            'published_at',

                        'order' =>
                            'DESC',
                    ],
                    $advertisementId,
                    $limit
                );
        }

        return array_slice(
            array_values(
                $related
            ),
            0,
            $limit
        );
    }

    /**
     * Ejecuta una búsqueda y añade únicamente resultados
     * todavía no incluidos.
     *
     * @param array<int, array<string, mixed>> $currentItems
     * @param array<string, mixed>             $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function appendSearchResults(
        array $currentItems,
        array $filters,
        int $excludedAdvertisementId,
        int $limit
    ): array {
        $missing =
            $limit
            - count($currentItems);

        if ($missing <= 0) {
            return $currentItems;
        }

        /*
         * Pedimos resultados adicionales porque el anuncio
         * actual puede estar incluido en la consulta.
         */
        $queryLimit =
            min(
                AdvertisementSearchRepository::
                    MAX_PER_PAGE,
                max(
                    $missing + 4,
                    $limit + 1
                )
            );

        $result =
            $this->repository
                ->search(
                    $filters,
                    1,
                    $queryLimit
                );

        $items =
            $result['items']
            ?? [];

        if (!is_array($items)) {
            return $currentItems;
        }

        $knownIds =
            $this->extractAdvertisementIds(
                $currentItems
            );

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemId =
                max(
                    0,
                    (int) (
                        $item['id']
                        ?? 0
                    )
                );

            if (
                $itemId <= 0
                || $itemId
                    === $excludedAdvertisementId
                || isset(
                    $knownIds[
                        $itemId
                    ]
                )
            ) {
                continue;
            }

            $currentItems[] =
                $item;

            $knownIds[$itemId] =
                true;

            if (
                count($currentItems)
                >= $limit
            ) {
                break;
            }
        }

        return $currentItems;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, bool>
     */
    private function extractAdvertisementIds(
        array $items
    ): array {
        $ids = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $advertisementId =
                max(
                    0,
                    (int) (
                        $item['id']
                        ?? 0
                    )
                );

            if ($advertisementId <= 0) {
                continue;
            }

            $ids[$advertisementId] =
                true;
        }

        return $ids;
    }
}