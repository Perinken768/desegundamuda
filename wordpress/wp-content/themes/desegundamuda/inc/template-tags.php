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
