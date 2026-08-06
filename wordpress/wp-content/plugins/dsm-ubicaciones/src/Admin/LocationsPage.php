<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Admin;

use DSM\Ubicaciones\Area\Area;
use DSM\Ubicaciones\Area\AreaRepository;
use DSM\Ubicaciones\Country\CountryRepository;
use DSM\Ubicaciones\Municipality\MunicipalityRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página de administración de ubicaciones.
 *
 * Permite gestionar desde WordPress:
 *
 * - países;
 * - áreas territoriales;
 * - municipios.
 *
 * No contiene lógica de escritura. Las acciones POST
 * se procesan en LocationAdminController.
 */
final class LocationsPage
{
    public const MENU_SLUG =
        'dsm-ubicaciones';

    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly AreaRepository $areaRepository,
        private readonly MunicipalityRepository $municipalityRepository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_menu',
            [
                $this,
                'registerMenu',
            ]
        );

        add_action(
            'admin_enqueue_scripts',
            [
                $this,
                'enqueueAssets',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __(
                'Ubicaciones',
                'dsm-ubicaciones'
            ),
            __(
                'DSM Ubicaciones',
                'dsm-ubicaciones'
            ),
            'manage_options',
            self::MENU_SLUG,
            [
                $this,
                'render',
            ],
            'dashicons-location-alt',
            58
        );
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        $expectedHook =
            'toplevel_page_'
            . self::MENU_SLUG;

        if ($hookSuffix !== $expectedHook) {
            return;
        }

        $cssRelativePath =
            'assets/admin/css/locations.css';

        $cssFilePath =
            DSM_UBICACIONES_PATH
            . $cssRelativePath;

        wp_enqueue_style(
            'dsm-ubicaciones-admin',
            DSM_UBICACIONES_URL
            . $cssRelativePath,
            [],
            is_file($cssFilePath)
                ? (string) filemtime(
                    $cssFilePath
                )
                : DSM_UBICACIONES_VERSION
        );

        $jsRelativePath =
            'assets/admin/js/locations.js';

        $jsFilePath =
            DSM_UBICACIONES_PATH
            . $jsRelativePath;

        wp_enqueue_script(
            'dsm-ubicaciones-admin',
            DSM_UBICACIONES_URL
            . $jsRelativePath,
            [],
            is_file($jsFilePath)
                ? (string) filemtime(
                    $jsFilePath
                )
                : DSM_UBICACIONES_VERSION,
            true
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para gestionar ubicaciones.',
                    'dsm-ubicaciones'
                )
            );
        }

        try {
            $countries =
                $this->countryRepository
                    ->findAll();

            $areas =
                $this->areaRepository
                    ->findAll();

            $municipalities =
                $this->municipalityRepository
                    ->findAll();

            $areasById = [];

            foreach ($areas as $area) {
                $areasById[
                    $area->getId()
                ] = $area;
            }

            $countriesById = [];

            foreach ($countries as $country) {
                $countriesById[
                    $country->getId()
                ] = $country;
            }

            $areasGroupedByCountry = [];

            foreach ($areas as $area) {
                $areasGroupedByCountry[
                    $area->getCountryId()
                ][] = $area;
            }

            $municipalitiesGroupedByArea = [];

            foreach (
                $municipalities
                as $municipality
            ) {
                $municipalitiesGroupedByArea[
                    $municipality->getAreaId()
                ][] = $municipality;
            }

            $selectedTab =
                isset($_GET['tab'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET['tab']
                        )
                    )
                    : 'countries';

            $allowedTabs = [
                'countries',
                'areas',
                'municipalities',
            ];

            if (
                !in_array(
                    $selectedTab,
                    $allowedTabs,
                    true
                )
            ) {
                $selectedTab =
                    'countries';
            }

            $notice =
                isset($_GET['dsm_location_notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'dsm_location_notice'
                            ]
                        )
                    )
                    : '';

            $error =
                isset($_GET['dsm_location_error'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'dsm_location_error'
                            ]
                        )
                    )
                    : '';

            $template =
                DSM_UBICACIONES_PATH
                . 'templates/admin/'
                . 'locations-list.php';

            if (!is_file($template)) {
                throw new \RuntimeException(
                    'No se encontró la plantilla administrativa de ubicaciones.'
                );
            }

            require $template;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Ubicaciones] No se pudo renderizar '
                . 'la administración de ubicaciones: '
                . $exception->getMessage()
            );

            ?>
            <div class="wrap">
                <h1>
                    <?php
                    esc_html_e(
                        'Ubicaciones',
                        'dsm-ubicaciones'
                    );
                    ?>
                </h1>

                <div class="notice notice-error">
                    <p>
                        <?php
                        esc_html_e(
                            'No se pudieron cargar las ubicaciones.',
                            'dsm-ubicaciones'
                        );
                        ?>
                    </p>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Devuelve las áreas que pueden ser usadas como padre
     * de otra área, excluyendo opcionalmente una concreta.
     *
     * @param array<int, Area> $areas
     *
     * @return array<int, Area>
     */
    public static function filterPossibleParents(
        array $areas,
        int $countryId,
        ?int $excludedAreaId = null
    ): array {
        return array_values(
            array_filter(
                $areas,
                static function (
                    Area $area
                ) use (
                    $countryId,
                    $excludedAreaId
                ): bool {
                    if (
                        $area->getCountryId()
                        !== $countryId
                    ) {
                        return false;
                    }

                    if (
                        $excludedAreaId !== null
                        && $area->getId()
                            === $excludedAreaId
                    ) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }
}