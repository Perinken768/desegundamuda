<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Category\Category;
use DSM\Anuncios\Category\CategoryRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Proporciona al formulario público los datos necesarios.
 *
 * Responsabilidades:
 *
 * - categorías activas permitidas en el marketplace;
 * - islas aportadas por la integración existente;
 * - municipios aportados por la integración existente.
 */
final class AdvertisementFormIntegration
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function register(): void
    {
        add_filter(
            'dsm_advertisement_form_categories',
            [
                $this,
                'provideCategories',
            ],
            10,
            1
        );

        add_filter(
            'dsm_advertisement_form_locations',
            [
                $this,
                'provideLocations',
            ],
            10,
            1
        );
    }

    /**
     * @param mixed $currentCategories
     *
     * @return array<int, array<string, mixed>>
     */
    public function provideCategories(
        mixed $currentCategories
    ): array {
        if (
            is_array($currentCategories)
            && $currentCategories !== []
        ) {
            return $currentCategories;
        }

        try {
            $categories =
                $this->categoryRepository
                    ->findAll();

            $result = [];

            foreach ($categories as $category) {
                if (!($category instanceof Category)) {
                    continue;
                }

                if (
                    !$category->isActive()
                    || !$category
                        ->canBeUsedInMarketplace()
                ) {
                    continue;
                }

                $result[] = [
                    'id' =>
                        $category->getId(),

                    'parent_id' =>
                        $category->getParentId(),

                    'name' =>
                        $category->getName(),

                    'slug' =>
                        $category->getSlug(),

                    'description' =>
                        $category->getDescription()
                        ?? '',

                    'sort_order' =>
                        $category->getSortOrder(),
                ];
            }

            usort(
                $result,
                static function (
                    array $left,
                    array $right
                ): int {
                    $sortComparison =
                        ((int) (
                            $left['sort_order']
                            ?? 0
                        ))
                        <=>
                        ((int) (
                            $right['sort_order']
                            ?? 0
                        ));

                    if ($sortComparison !== 0) {
                        return $sortComparison;
                    }

                    return strcasecmp(
                        (string) (
                            $left['name']
                            ?? ''
                        ),
                        (string) (
                            $right['name']
                            ?? ''
                        )
                    );
                }
            );

            return $result;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudieron cargar '
                . 'las categorías del formulario: '
                . $exception->getMessage()
            );

            return [];
        }
    }

    /**
     * Devuelve conjuntamente islas y municipios.
     *
     * Formato:
     *
     * [
     *     'islands' => [
     *         [
     *             'id'   => 1,
     *             'name' => 'Gran Canaria',
     *         ],
     *     ],
     *
     *     'municipalities' => [
     *         [
     *             'id'        => 1,
     *             'island_id' => 1,
     *             'name'      => 'Las Palmas de Gran Canaria',
     *         ],
     *     ],
     * ]
     *
     * @param mixed $currentLocations
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function provideLocations(
        mixed $currentLocations
    ): array {
        if (
            is_array($currentLocations)
            && $currentLocations !== []
        ) {
            return $currentLocations;
        }

        try {
            $islands =
                apply_filters(
                    'dsm_marketplace_islands',
                    []
                );

            if (!is_array($islands)) {
                $islands = [];
            }

            $normalizedIslands = [];

            foreach ($islands as $island) {
                if (!is_array($island)) {
                    continue;
                }

                $islandId =
                    max(
                        0,
                        (int) (
                            $island['id']
                            ?? 0
                        )
                    );

                $islandName =
                    sanitize_text_field(
                        (string) (
                            $island['name']
                            ?? ''
                        )
                    );

                if (
                    $islandId <= 0
                    || $islandName === ''
                ) {
                    continue;
                }

                $normalizedIslands[] = [
                    'id' =>
                        $islandId,

                    'name' =>
                        $islandName,
                ];
            }

            /*
             * Se solicitan todos los municipios pasando 0.
             *
             * La integración que gestione ubicaciones deberá
             * interpretar 0 como "sin filtrar por isla".
             */
            $municipalities =
                apply_filters(
                    'dsm_marketplace_municipalities',
                    [],
                    0
                );

            if (!is_array($municipalities)) {
                $municipalities = [];
            }

            $normalizedMunicipalities = [];

            foreach ($municipalities as $municipality) {
                if (!is_array($municipality)) {
                    continue;
                }

                $municipalityId =
                    max(
                        0,
                        (int) (
                            $municipality['id']
                            ?? 0
                        )
                    );

                $islandId =
                    max(
                        0,
                        (int) (
                            $municipality['island_id']
                            ?? 0
                        )
                    );

                $municipalityName =
                    sanitize_text_field(
                        (string) (
                            $municipality['name']
                            ?? ''
                        )
                    );

                if (
                    $municipalityId <= 0
                    || $islandId <= 0
                    || $municipalityName === ''
                ) {
                    continue;
                }

                $normalizedMunicipalities[] = [
                    'id' =>
                        $municipalityId,

                    'island_id' =>
                        $islandId,

                    'name' =>
                        $municipalityName,
                ];
            }

            return [
                'islands' =>
                    $normalizedIslands,

                'municipalities' =>
                    $normalizedMunicipalities,
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudieron cargar '
                . 'las ubicaciones del formulario: '
                . $exception->getMessage()
            );

            return [
                'islands' =>
                    [],

                'municipalities' =>
                    [],
            ];
        }
    }
}