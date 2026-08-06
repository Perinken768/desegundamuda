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
 * - países activos;
 * - áreas territoriales activas;
 * - municipios activos.
 *
 * La ubicación utiliza exclusivamente:
 *
 * - country_id;
 * - area_id;
 * - municipality_id.
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
     * Devuelve conjuntamente países, áreas y municipios.
     *
     * Formato:
     *
     * [
     *     'countries' => [
     *         [
     *             'id' => 1,
     *             'name' => 'España',
     *             'iso_code' => 'ES',
     *         ],
     *     ],
     *
     *     'areas' => [
     *         [
     *             'id' => 4,
     *             'country_id' => 1,
     *             'parent_id' => 1,
     *             'name' => 'Gran Canaria',
     *             'area_type' => 'island',
     *         ],
     *     ],
     *
     *     'municipalities' => [
     *         [
     *             'id' => 26,
     *             'area_id' => 4,
     *             'name' => 'Telde',
     *         ],
     *     ],
     * ]
     *
     * @param mixed $currentLocations
     *
     * @return array{
     *     countries: array<int, array<string, mixed>>,
     *     areas: array<int, array<string, mixed>>,
     *     municipalities: array<int, array<string, mixed>>
     * }
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
            $countries =
                apply_filters(
                    'dsm_location_countries',
                    []
                );

            $areas =
                apply_filters(
                    'dsm_location_areas',
                    [],
                    null,
                    null
                );

            $municipalities =
                apply_filters(
                    'dsm_location_municipalities',
                    [],
                    null
                );

            if (!is_array($countries)) {
                $countries = [];
            }

            if (!is_array($areas)) {
                $areas = [];
            }

            if (!is_array($municipalities)) {
                $municipalities = [];
            }

            return [
                'countries' =>
                    $this->normalizeCountries(
                        $countries
                    ),

                'areas' =>
                    $this->normalizeAreas(
                        $areas
                    ),

                'municipalities' =>
                    $this->normalizeMunicipalities(
                        $municipalities
                    ),
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudieron cargar '
                . 'las ubicaciones del formulario: '
                . $exception->getMessage()
            );

            return [
                'countries' =>
                    [],

                'areas' =>
                    [],

                'municipalities' =>
                    [],
            ];
        }
    }

    /**
     * @param array<int, mixed> $countries
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCountries(
        array $countries
    ): array {
        $normalized = [];

        foreach ($countries as $country) {
            if (!is_array($country)) {
                continue;
            }

            $countryId =
                max(
                    0,
                    (int) (
                        $country['id']
                        ?? 0
                    )
                );

            $countryName =
                sanitize_text_field(
                    (string) (
                        $country['name']
                        ?? ''
                    )
                );

            if (
                $countryId <= 0
                || $countryName === ''
            ) {
                continue;
            }

            $normalized[] = [
                'id' =>
                    $countryId,

                'name' =>
                    $countryName,

                'slug' =>
                    sanitize_title(
                        (string) (
                            $country['slug']
                            ?? ''
                        )
                    ),

                'iso_code' =>
                    strtoupper(
                        sanitize_text_field(
                            (string) (
                                $country['iso_code']
                                ?? ''
                            )
                        )
                    ),

                'phone_prefix' =>
                    isset($country['phone_prefix'])
                    && $country['phone_prefix'] !== null
                        ? sanitize_text_field(
                            (string) $country[
                                'phone_prefix'
                            ]
                        )
                        : null,

                'sort_order' =>
                    max(
                        0,
                        (int) (
                            $country['sort_order']
                            ?? 0
                        )
                    ),
            ];
        }

        usort(
            $normalized,
            [
                $this,
                'compareRows',
            ]
        );

        return $normalized;
    }

    /**
     * @param array<int, mixed> $areas
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAreas(
        array $areas
    ): array {
        $normalized = [];

        foreach ($areas as $area) {
            if (!is_array($area)) {
                continue;
            }

            $areaId =
                max(
                    0,
                    (int) (
                        $area['id']
                        ?? 0
                    )
                );

            $countryId =
                max(
                    0,
                    (int) (
                        $area['country_id']
                        ?? 0
                    )
                );

            $areaName =
                sanitize_text_field(
                    (string) (
                        $area['name']
                        ?? ''
                    )
                );

            if (
                $areaId <= 0
                || $countryId <= 0
                || $areaName === ''
            ) {
                continue;
            }

            $parentId =
                isset($area['parent_id'])
                && $area['parent_id'] !== null
                    ? max(
                        0,
                        (int) $area[
                            'parent_id'
                        ]
                    )
                    : null;

            if ($parentId === 0) {
                $parentId = null;
            }

            $normalized[] = [
                'id' =>
                    $areaId,

                'country_id' =>
                    $countryId,

                'parent_id' =>
                    $parentId,

                'name' =>
                    $areaName,

                'slug' =>
                    sanitize_title(
                        (string) (
                            $area['slug']
                            ?? ''
                        )
                    ),

                'area_type' =>
                    sanitize_key(
                        (string) (
                            $area['area_type']
                            ?? 'other'
                        )
                    ),

                'area_type_label' =>
                    sanitize_text_field(
                        (string) (
                            $area['area_type_label']
                            ?? ''
                        )
                    ),

                'code' =>
                    isset($area['code'])
                    && $area['code'] !== null
                        ? sanitize_text_field(
                            (string) $area['code']
                        )
                        : null,

                'sort_order' =>
                    max(
                        0,
                        (int) (
                            $area['sort_order']
                            ?? 0
                        )
                    ),
            ];
        }

        usort(
            $normalized,
            static function (
                array $left,
                array $right
            ): int {
                $countryComparison =
                    ((int) (
                        $left['country_id']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['country_id']
                        ?? 0
                    ));

                if ($countryComparison !== 0) {
                    return $countryComparison;
                }

                $parentComparison =
                    ((int) (
                        $left['parent_id']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['parent_id']
                        ?? 0
                    ));

                if ($parentComparison !== 0) {
                    return $parentComparison;
                }

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

        return $normalized;
    }

    /**
     * @param array<int, mixed> $municipalities
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMunicipalities(
        array $municipalities
    ): array {
        $normalized = [];

        foreach (
            $municipalities
            as $municipality
        ) {
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

            $areaId =
                max(
                    0,
                    (int) (
                        $municipality['area_id']
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
                || $areaId <= 0
                || $municipalityName === ''
            ) {
                continue;
            }

            $normalized[] = [
                'id' =>
                    $municipalityId,

                'area_id' =>
                    $areaId,

                'name' =>
                    $municipalityName,

                'slug' =>
                    sanitize_title(
                        (string) (
                            $municipality['slug']
                            ?? ''
                        )
                    ),

                'code' =>
                    isset($municipality['code'])
                    && $municipality['code'] !== null
                        ? sanitize_text_field(
                            (string) $municipality[
                                'code'
                            ]
                        )
                        : null,

                'postal_code' =>
                    isset($municipality['postal_code'])
                    && $municipality['postal_code'] !== null
                        ? sanitize_text_field(
                            (string) $municipality[
                                'postal_code'
                            ]
                        )
                        : null,

                'sort_order' =>
                    max(
                        0,
                        (int) (
                            $municipality['sort_order']
                            ?? 0
                        )
                    ),
            ];
        }

        usort(
            $normalized,
            static function (
                array $left,
                array $right
            ): int {
                $areaComparison =
                    ((int) (
                        $left['area_id']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['area_id']
                        ?? 0
                    ));

                if ($areaComparison !== 0) {
                    return $areaComparison;
                }

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

        return $normalized;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareRows(
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
}