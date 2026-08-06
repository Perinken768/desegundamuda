<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use DSM\Clientes\Support\TemplateRenderer;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Muestra el formulario público de edición del perfil.
 *
 * Además de los datos propios del cliente, solicita a
 * DSM Ubicaciones los países, áreas y municipios activos
 * disponibles para el formulario.
 */
final class ProfileShortcode
{
    public const SHORTCODE =
        'dsm_customer_profile';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        $authenticatedCustomer =
            new AuthenticatedCustomer(
                new CustomerSessionRepository(),
                new CustomerRepository()
            );

        $customer =
            $authenticatedCustomer->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $profileRepository =
            new CustomerProfileRepository();

        $profile =
            $profileRepository
                ->findByCustomerId(
                    $customer->getId()
                );

        if ($profile === null) {
            return sprintf(
                '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
                esc_html__(
                    'No se pudo recuperar tu perfil.',
                    'dsm-clientes'
                )
            );
        }

        $updated =
            isset(
                $_GET['profile_updated']
            )
            && sanitize_key(
                wp_unslash(
                    (string) $_GET[
                        'profile_updated'
                    ]
                )
            ) === '1';

        $hasError =
            isset(
                $_GET['profile_error']
            );

        $locationData =
            self::resolveLocationData(
                $profile->getAreaId()
            );

        return TemplateRenderer::render(
            'account/profile-form',
            [
                'customer' =>
                    $customer,

                'profile' =>
                    $profile,

                'updated' =>
                    $updated,

                'hasError' =>
                    $hasError,

                /*
                 * Indica si el cliente ya dispone de:
                 *
                 * - teléfono válido;
                 * - al menos un método autorizado.
                 */
                'hasValidContactMethod' =>
                    $profile
                        ->hasValidContactMethod(),

                'allowsPhoneCalls' =>
                    $profile
                        ->allowsPhoneCalls(),

                'allowsWhatsapp' =>
                    $profile
                        ->allowsWhatsapp(),

                'normalizedPhone' =>
                    $profile->getPhone()
                    ?? '',

                /*
                 * Ubicación territorial.
                 */
                'countries' =>
                    $locationData[
                        'countries'
                    ],

                'areas' =>
                    $locationData[
                        'areas'
                    ],

                'municipalities' =>
                    $locationData[
                        'municipalities'
                    ],

                'selectedAreaId' =>
                    $profile->getAreaId(),

                'selectedMunicipalityId' =>
                    $profile
                        ->getMunicipalityId(),

                'locationsAvailable' =>
                    $locationData[
                        'locations_available'
                    ],
            ]
        );
    }

    /**
     * Solicita las ubicaciones activas mediante los filtros
     * públicos expuestos por DSM Ubicaciones.
     *
     * Los plugins permanecen desacoplados: DSM Clientes no
     * necesita instanciar repositorios internos de ubicaciones.
     *
     * @return array{
     *     countries: array<int, array<string, mixed>>,
     *     areas: array<int, array<string, mixed>>,
     *     municipalities: array<int, array<string, mixed>>,
     *     locations_available: bool
     * }
     */
    private static function resolveLocationData(
        ?int $selectedAreaId
    ): array {
        try {
            $countries =
                apply_filters(
                    'dsm_location_countries',
                    []
                );

            if (!is_array($countries)) {
                $countries = [];
            }

            $areas =
                apply_filters(
                    'dsm_location_areas',
                    [],
                    null,
                    null
                );

            if (!is_array($areas)) {
                $areas = [];
            }

            /*
             * Cargamos todos los municipios para que el
             * formulario pueda actualizar dinámicamente el
             * selector al cambiar el área mediante JavaScript.
             */
            $municipalities =
                apply_filters(
                    'dsm_location_municipalities',
                    [],
                    null
                );

            if (!is_array($municipalities)) {
                $municipalities = [];
            }

            $countries =
                self::normalizeCountries(
                    $countries
                );

            $areas =
                self::normalizeAreas(
                    $areas
                );

            $municipalities =
                self::normalizeMunicipalities(
                    $municipalities
                );

            /*
             * Si el perfil contiene un área antigua o inactiva,
             * no la eliminamos aquí. La plantilla podrá mostrar
             * que la ubicación debe revisarse.
             */
            $locationsAvailable =
                $countries !== []
                && $areas !== [];

            return [
                'countries' =>
                    $countries,

                'areas' =>
                    $areas,

                'municipalities' =>
                    $municipalities,

                'locations_available' =>
                    $locationsAvailable,
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudieron cargar '
                . 'las ubicaciones del formulario de perfil: '
                . $exception->getMessage()
            );

            return [
                'countries' =>
                    [],

                'areas' =>
                    [],

                'municipalities' =>
                    [],

                'locations_available' =>
                    false,
            ];
        }
    }

    /**
     * @param array<int, mixed> $countries
     *
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeCountries(
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
                                $country[
                                    'iso_code'
                                ]
                                ?? ''
                            )
                        )
                    ),

                'phone_prefix' =>
                    isset(
                        $country[
                            'phone_prefix'
                        ]
                    )
                    && $country[
                        'phone_prefix'
                    ] !== null
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
                            $country[
                                'sort_order'
                            ]
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
                $orderComparison =
                    ((int) (
                        $left['sort_order']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['sort_order']
                        ?? 0
                    ));

                if ($orderComparison !== 0) {
                    return $orderComparison;
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
     * @param array<int, mixed> $areas
     *
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeAreas(
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

            $normalized[] = [
                'id' =>
                    $areaId,

                'country_id' =>
                    $countryId,

                'parent_id' =>
                    isset($area['parent_id'])
                    && $area['parent_id'] !== null
                        ? max(
                            0,
                            (int) $area[
                                'parent_id'
                            ]
                        )
                        : null,

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
                            $area[
                                'area_type_label'
                            ]
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

                $orderComparison =
                    ((int) (
                        $left['sort_order']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['sort_order']
                        ?? 0
                    ));

                if ($orderComparison !== 0) {
                    return $orderComparison;
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
    private static function normalizeMunicipalities(
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
                    isset(
                        $municipality[
                            'postal_code'
                        ]
                    )
                    && $municipality[
                        'postal_code'
                    ] !== null
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
                            $municipality[
                                'sort_order'
                            ]
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

                $orderComparison =
                    ((int) (
                        $left['sort_order']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $right['sort_order']
                        ?? 0
                    ));

                if ($orderComparison !== 0) {
                    return $orderComparison;
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

    private function __construct()
    {
    }
}