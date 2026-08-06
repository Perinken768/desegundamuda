<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Admin;

use DSM\Ubicaciones\Area\AreaRepository;
use DSM\Ubicaciones\Country\CountryRepository;
use DSM\Ubicaciones\Municipality\MunicipalityRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procesa las acciones administrativas de DSM Ubicaciones.
 *
 * Gestiona:
 *
 * - creación y edición de países;
 * - creación y edición de áreas;
 * - creación y edición de municipios;
 * - activación y desactivación de registros.
 */
final class LocationAdminController
{
    private const BASE_ACTION =
        'dsm_location_';

    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly AreaRepository $areaRepository,
        private readonly MunicipalityRepository $municipalityRepository
    ) {
    }

    public function register(): void
    {
        add_action(
            'admin_post_dsm_location_save_country',
            [
                $this,
                'handleSaveCountry',
            ]
        );

        add_action(
            'admin_post_dsm_location_toggle_country',
            [
                $this,
                'handleToggleCountry',
            ]
        );

        add_action(
            'admin_post_dsm_location_save_area',
            [
                $this,
                'handleSaveArea',
            ]
        );

        add_action(
            'admin_post_dsm_location_toggle_area',
            [
                $this,
                'handleToggleArea',
            ]
        );

        add_action(
            'admin_post_dsm_location_save_municipality',
            [
                $this,
                'handleSaveMunicipality',
            ]
        );

        add_action(
            'admin_post_dsm_location_toggle_municipality',
            [
                $this,
                'handleToggleMunicipality',
            ]
        );
    }

    public function handleSaveCountry(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_save_country',
            'dsm_location_nonce'
        );

        $countryId =
            isset($_POST['country_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'country_id'
                        ]
                    )
                )
                : 0;

        $name =
            isset($_POST['name'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['name']
                    )
                )
                : '';

        $isoCode =
            isset($_POST['iso_code'])
                ? strtoupper(
                    sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'iso_code'
                            ]
                        )
                    )
                )
                : '';

        $phonePrefix =
            isset($_POST['phone_prefix'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'phone_prefix'
                        ]
                    )
                )
                : null;

        $sortOrder =
            isset($_POST['sort_order'])
                ? max(
                    0,
                    (int) wp_unslash(
                        (string) $_POST[
                            'sort_order'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            if ($countryId > 0) {
                $this->countryRepository
                    ->update(
                        $countryId,
                        $name,
                        $isoCode,
                        $phonePrefix,
                        $sortOrder,
                        $isActive
                    );

                $this->redirectSuccess(
                    'countries',
                    'country_updated'
                );
            }

            $this->countryRepository
                ->create(
                    $name,
                    $isoCode,
                    $phonePrefix,
                    $sortOrder,
                    $isActive
                );

            $this->redirectSuccess(
                'countries',
                'country_created'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'countries',
                'country_save_failed',
                $exception
            );
        }
    }

    public function handleToggleCountry(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_toggle_country',
            'dsm_location_nonce'
        );

        $countryId =
            isset($_POST['country_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'country_id'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            $this->countryRepository
                ->setActive(
                    $countryId,
                    $isActive
                );

            $this->redirectSuccess(
                'countries',
                $isActive
                    ? 'country_activated'
                    : 'country_deactivated'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'countries',
                'country_toggle_failed',
                $exception
            );
        }
    }

    public function handleSaveArea(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_save_area',
            'dsm_location_nonce'
        );

        $areaId =
            isset($_POST['area_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'area_id'
                        ]
                    )
                )
                : 0;

        $countryId =
            isset($_POST['country_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'country_id'
                        ]
                    )
                )
                : 0;

        $parentId =
            isset($_POST['parent_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'parent_id'
                        ]
                    )
                )
                : 0;

        $parentId =
            $parentId > 0
                ? $parentId
                : null;

        $name =
            isset($_POST['name'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['name']
                    )
                )
                : '';

        $areaType =
            isset($_POST['area_type'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[
                            'area_type'
                        ]
                    )
                )
                : 'other';

        $code =
            isset($_POST['code'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['code']
                    )
                )
                : null;

        $sortOrder =
            isset($_POST['sort_order'])
                ? max(
                    0,
                    (int) wp_unslash(
                        (string) $_POST[
                            'sort_order'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            if ($areaId > 0) {
                $this->areaRepository
                    ->update(
                        $areaId,
                        $countryId,
                        $parentId,
                        $name,
                        $areaType,
                        $code,
                        $sortOrder,
                        $isActive
                    );

                $this->redirectSuccess(
                    'areas',
                    'area_updated'
                );
            }

            $this->areaRepository
                ->create(
                    $countryId,
                    $parentId,
                    $name,
                    $areaType,
                    $code,
                    $sortOrder,
                    $isActive
                );

            $this->redirectSuccess(
                'areas',
                'area_created'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'areas',
                'area_save_failed',
                $exception
            );
        }
    }

    public function handleToggleArea(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_toggle_area',
            'dsm_location_nonce'
        );

        $areaId =
            isset($_POST['area_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'area_id'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            $this->areaRepository
                ->setActive(
                    $areaId,
                    $isActive
                );

            $this->redirectSuccess(
                'areas',
                $isActive
                    ? 'area_activated'
                    : 'area_deactivated'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'areas',
                'area_toggle_failed',
                $exception
            );
        }
    }

    public function handleSaveMunicipality(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_save_municipality',
            'dsm_location_nonce'
        );

        $municipalityId =
            isset($_POST['municipality_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'municipality_id'
                        ]
                    )
                )
                : 0;

        $areaId =
            isset($_POST['area_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'area_id'
                        ]
                    )
                )
                : 0;

        $name =
            isset($_POST['name'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['name']
                    )
                )
                : '';

        $code =
            isset($_POST['code'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['code']
                    )
                )
                : null;

        $postalCode =
            isset($_POST['postal_code'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'postal_code'
                        ]
                    )
                )
                : null;

        $sortOrder =
            isset($_POST['sort_order'])
                ? max(
                    0,
                    (int) wp_unslash(
                        (string) $_POST[
                            'sort_order'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            if ($municipalityId > 0) {
                $this->municipalityRepository
                    ->update(
                        $municipalityId,
                        $areaId,
                        $name,
                        $code,
                        $postalCode,
                        $sortOrder,
                        $isActive
                    );

                $this->redirectSuccess(
                    'municipalities',
                    'municipality_updated'
                );
            }

            $this->municipalityRepository
                ->create(
                    $areaId,
                    $name,
                    $code,
                    $postalCode,
                    $sortOrder,
                    $isActive
                );

            $this->redirectSuccess(
                'municipalities',
                'municipality_created'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'municipalities',
                'municipality_save_failed',
                $exception
            );
        }
    }

    public function handleToggleMunicipality(): void
    {
        $this->assertPermission();

        check_admin_referer(
            'dsm_location_toggle_municipality',
            'dsm_location_nonce'
        );

        $municipalityId =
            isset($_POST['municipality_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'municipality_id'
                        ]
                    )
                )
                : 0;

        $isActive =
            isset($_POST['is_active'])
            && $this->normalizeCheckbox(
                $_POST['is_active']
            );

        try {
            $this->municipalityRepository
                ->setActive(
                    $municipalityId,
                    $isActive
                );

            $this->redirectSuccess(
                'municipalities',
                $isActive
                    ? 'municipality_activated'
                    : 'municipality_deactivated'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'municipalities',
                'municipality_toggle_failed',
                $exception
            );
        }
    }

    private function assertPermission(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para gestionar ubicaciones.',
                    'dsm-ubicaciones'
                )
            );
        }
    }

    private function normalizeCheckbox(
        mixed $value
    ): bool {
        $value =
            strtolower(
                trim(
                    (string) wp_unslash(
                        $value
                    )
                )
            );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }

    private function redirectSuccess(
        string $tab,
        string $notice
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        LocationsPage::MENU_SLUG,

                    'tab' =>
                        sanitize_key($tab),

                    'dsm_location_notice' =>
                        sanitize_key($notice),
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function redirectError(
        string $tab,
        string $error
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        LocationsPage::MENU_SLUG,

                    'tab' =>
                        sanitize_key($tab),

                    'dsm_location_error' =>
                        sanitize_key($error),
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function handleException(
        string $tab,
        string $error,
        Throwable $exception
    ): never {
        error_log(
            sprintf(
                '[DSM Ubicaciones] Error administrativo (%s): %s',
                $error,
                $exception->getMessage()
            )
        );

        $this->redirectError(
            $tab,
            $error
        );
    }
}