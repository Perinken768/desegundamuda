<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function dsm_theme_show_site_title(): bool
{
    return (bool) get_theme_mod(
        'dsm_show_site_title',
        true
    );
}

function dsm_theme_account_label(): string
{
    $label =
        trim(
            (string) get_theme_mod(
                'dsm_account_label',
                'Mi cuenta'
            )
        );

    return $label !== ''
        ? $label
        : 'Mi cuenta';
}

function dsm_theme_search_placeholder(): string
{
    $placeholder =
        trim(
            (string) get_theme_mod(
                'dsm_search_placeholder',
                'Buscar ropa, marcas, productos...'
            )
        );

    return $placeholder !== ''
        ? $placeholder
        : 'Buscar ropa, marcas, productos...';
}


function dsm_theme_home_enabled(
    string $setting,
    bool $default = true
): bool {
    return (bool) get_theme_mod(
        $setting,
        $default
    );
}

function dsm_theme_home_results_title(): string
{
    $title =
        trim(
            (string) get_theme_mod(
                'dsm_home_results_title',
                'Descubre lo último'
            )
        );

    return $title !== ''
        ? $title
        : 'Descubre lo último';
}


/**
 * Devuelve la ubicación efectiva de la portada.
 *
 * Prioridad:
 *
 * 1. dsm_area recibido explícitamente por URL;
 * 2. isla/área del perfil del cliente DSM autenticado;
 * 3. sin filtro territorial.
 *
 * @return array{
 *     area_id:int,
 *     area_name:string,
 *     area_type:string,
 *     source:string
 * }
 */
/*
 * Contrato territorial compartido de portada.
 *
 * Este contexto se utiliza por:
 *
 * - DSM Anuncios;
 * - DSM Multitienda;
 * - DSM Publicidad.
 *
 * area_id = 0 significa que no existe
 * restricción territorial.
 *
 * source puede ser:
 *
 * - profile: isla obtenida del perfil;
 * - manual: isla seleccionada por URL;
 * - all: todas las islas.
 */

function dsm_theme_home_location_context(): array
{
    $areaId = 0;
    $source = 'all';

    /*
     * Si el usuario ha cambiado explícitamente
     * la isla, respetamos siempre su selección.
     *
     * dsm_area=0 significa "Todas las islas".
     */
    if (array_key_exists('dsm_area', $_GET)) {
        $areaId =
            max(
                0,
                absint(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_area'
                        ]
                    )
                )
            );

        $source =
            $areaId > 0
                ? 'manual'
                : 'all';
    } else {
        /*
         * Contexto neutral proporcionado por DSM Clientes.
         */
        $customerContext =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (
            is_array($customerContext)
            && (int) (
                $customerContext['id']
                ?? 0
            ) > 0
            && sanitize_key(
                (string) (
                    $customerContext['status']
                    ?? ''
                )
            ) === 'active'
        ) {
            $profileAreaId =
                max(
                    0,
                    (int) (
                        $customerContext[
                            'area_id'
                        ]
                        ?? 0
                    )
                );

            if ($profileAreaId > 0) {
                $areaId =
                    $profileAreaId;

                $source =
                    'profile';
            }
        }
    }

    $locationData = [];

    if ($areaId > 0) {
        $locationData =
            apply_filters(
                'dsm_location_data',
                [],
                $areaId,
                null
            );

        if (!is_array($locationData)) {
            $locationData = [];
        }
    }

    return [
        'area_id' =>
            $areaId,

        'area_name' =>
            sanitize_text_field(
                (string) (
                    $locationData[
                        'area_name'
                    ]
                    ?? ''
                )
            ),

        'area_type' =>
            sanitize_key(
                (string) (
                    $locationData[
                        'area_type'
                    ]
                    ?? ''
                )
            ),

        'source' =>
            $source,
    ];
}


/**
 * Devuelve las islas públicas disponibles.
 *
 * @return array<int, array<string, mixed>>
 */
function dsm_theme_home_islands(): array
{
    $areas =
        apply_filters(
            'dsm_location_areas',
            [],
            null,
            'island'
        );

    return is_array($areas)
        ? $areas
        : [];
}


/**
 * Devuelve el orden válido de las secciones de portada.
 *
 * Secciones permitidas:
 *
 * - search
 * - categories
 * - filters
 * - advertising
 * - listings
 *
 * @return array<int, string>
 */
function dsm_theme_home_section_order(): array
{
    $allowed = [
        'search',
        'categories',
        'filters',
        'advertising',
        'listings',
    ];

    $defaults = [
        1 => 'search',
        2 => 'categories',
        3 => 'filters',
        4 => 'advertising',
        5 => 'listings',
    ];

    $result = [];

    foreach (
        $defaults
        as $position => $defaultSection
    ) {
        $section =
            sanitize_key(
                (string) get_theme_mod(
                    'dsm_home_section_position_'
                    . $position,
                    $defaultSection
                )
            );

        if (
            !in_array(
                $section,
                $allowed,
                true
            )
            || in_array(
                $section,
                $result,
                true
            )
        ) {
            continue;
        }

        $result[] =
            $section;
    }

    /*
     * Si hubiera duplicados o un valor inválido,
     * completamos automáticamente las secciones
     * que falten.
     */
    foreach ($allowed as $section) {
        if (
            !in_array(
                $section,
                $result,
                true
            )
        ) {
            $result[] =
                $section;
        }
    }

    return $result;
}


function dsm_theme_home_categories_title(): string
{
    $title =
        trim(
            (string) get_theme_mod(
                'dsm_home_categories_title',
                'Categorías'
            )
        );

    return $title !== ''
        ? $title
        : 'Categorías';
}


function dsm_theme_home_filters_title(): string
{
    $title =
        trim(
            (string) get_theme_mod(
                'dsm_home_filters_title',
                'Filtrar resultados'
            )
        );

    return $title !== ''
        ? $title
        : 'Filtrar resultados';
}


function dsm_theme_home_listing_columns(): int
{
    $columns =
        (int) get_theme_mod(
            'dsm_home_listing_columns',
            4
        );

    return in_array(
        $columns,
        [
            3,
            4,
            5,
        ],
        true
    )
        ? $columns
        : 4;
}


/**
 * Orden configurado de los módulos de Mi cuenta.
 *
 * @param array<string, array<string, mixed>> $modules
 *
 * @return array<string, array<string, mixed>>
 */
function dsm_theme_account_order_modules(
    array $modules
): array {
    if ($modules === []) {
        return [];
    }

    /*
     * Primero ordenamos por la prioridad por defecto
     * declarada por cada plugin.
     */
    uasort(
        $modules,
        static function (
            mixed $first,
            mixed $second
        ): int {
            $firstPriority =
                is_array($first)
                    ? (int) (
                        $first[
                            'default_priority'
                        ]
                        ?? 100
                    )
                    : 100;

            $secondPriority =
                is_array($second)
                    ? (int) (
                        $second[
                            'default_priority'
                        ]
                        ?? 100
                    )
                    : 100;

            return $firstPriority
                <=> $secondPriority;
        }
    );

    $availableIds =
        array_keys(
            $modules
        );

    $configured = [];

    /*
     * Disponemos inicialmente de hasta 10 posiciones.
     *
     * Esto permite añadir nuevos módulos en el futuro
     * sin tener que rehacer este helper.
     */
    for (
        $position = 1;
        $position <= 10;
        $position++
    ) {
        $moduleId =
            sanitize_key(
                (string) get_theme_mod(
                    'dsm_account_module_position_'
                    . $position,
                    ''
                )
            );

        if (
            $moduleId === ''
            || !in_array(
                $moduleId,
                $availableIds,
                true
            )
            || in_array(
                $moduleId,
                $configured,
                true
            )
        ) {
            continue;
        }

        $configured[] =
            $moduleId;
    }

    /*
     * Los módulos no configurados manualmente se añaden
     * después respetando su prioridad por defecto.
     */
    foreach (
        $availableIds
        as $moduleId
    ) {
        if (
            !in_array(
                $moduleId,
                $configured,
                true
            )
        ) {
            $configured[] =
                $moduleId;
        }
    }

    $ordered = [];

    foreach (
        $configured
        as $moduleId
    ) {
        $ordered[
            $moduleId
        ] =
            $modules[
                $moduleId
            ];
    }

    return $ordered;
}
