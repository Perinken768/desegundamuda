<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode del marketplace público.
 *
 * Uso:
 *
 * [dsm_advertisements]
 *
 * Prioridad del filtro territorial inicial:
 *
 * 1. Área indicada explícitamente en la URL.
 * 2. Área guardada en una cookie de navegación.
 * 3. Área del perfil del cliente DSM autenticado.
 * 4. Sin filtro territorial.
 *
 * DSM Anuncios no conoce las clases internas de DSM Clientes.
 * Obtiene el cliente mediante el filtro:
 *
 * dsm_current_customer_context
 */
final class AdvertisementListShortcode
{
    public const SHORTCODE =
        'dsm_advertisements';

    private const AREA_COOKIE =
        'dsm_marketplace_area';

    private const AREA_COOKIE_DURATION =
        30 * DAY_IN_SECONDS;

    public function __construct(
        private readonly AdvertisementSearchRepository $repository
    ) {
    }

    /**
     * Registra el shortcode y sus recursos.
     */
    public function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                $this,
                'render',
            ]
        );

        add_action(
            'wp_enqueue_scripts',
            [
                $this,
                'registerAssets',
            ]
        );
    }

    /**
     * Registra CSS y JavaScript públicos.
     */
    public function registerAssets(): void
    {
        $cssRelativePath =
            'assets/public/css/advertisements.css';

        $cssFilePath =
            DSM_ANUNCIOS_PATH
            . $cssRelativePath;

        wp_register_style(
            'dsm-anuncios-public',
            DSM_ANUNCIOS_URL
            . $cssRelativePath,
            [],
            is_file($cssFilePath)
                ? (string) filemtime(
                    $cssFilePath
                )
                : DSM_ANUNCIOS_VERSION
        );

        $jsRelativePath =
            'assets/public/js/advertisements.js';

        $jsFilePath =
            DSM_ANUNCIOS_PATH
            . $jsRelativePath;

        wp_register_script(
            'dsm-anuncios-public',
            DSM_ANUNCIOS_URL
            . $jsRelativePath,
            [],
            is_file($jsFilePath)
                ? (string) filemtime(
                    $jsFilePath
                )
                : DSM_ANUNCIOS_VERSION,
            true
        );
    }

    /**
     * Renderiza el marketplace.
     *
     * @param array<string, mixed> $attributes
     */
    public function render(
        array $attributes = []
    ): string {
        $attributes =
            shortcode_atts(
                [
                    'per_page' =>
                        AdvertisementSearchRepository::
                            DEFAULT_PER_PAGE,

                    'show_filters' =>
                        '1',

                    'show_search' =>
                        '1',

                    'show_categories' =>
                        '1',
                ],
                $attributes,
                self::SHORTCODE
            );

        $page =
            $this->getCurrentPage();

        $filters =
            $this->getFilters();

        $currentCustomer =
            $this->resolveCurrentCustomerContext();

        $filters =
            $this->resolveInitialArea(
                $filters,
                $currentCustomer
            );

        $perPage =
            min(
                AdvertisementSearchRepository::
                    MAX_PER_PAGE,
                max(
                    1,
                    absint(
                        (string) $attributes[
                            'per_page'
                        ]
                    )
                )
            );

        $result =
            $this->repository->search(
                $filters,
                $page,
                $perPage
            );

        $categories =
            $this->repository
                ->findCategories();

        $areaId =
            max(
                0,
                (int) (
                    $filters['area_id']
                    ?? 0
                )
            );

        $brands =
            $this->repository
                ->findBrands(
                    $areaId > 0
                        ? $areaId
                        : null
                );

        $showFilters =
            $this->toBoolean(
                $attributes[
                    'show_filters'
                ]
            );

        $showSearch =
            $this->toBoolean(
                $attributes[
                    'show_search'
                ]
            );

        $showCategories =
            $this->toBoolean(
                $attributes[
                    'show_categories'
                ]
            );

        $template =
            DSM_ANUNCIOS_PATH
            . 'templates/public/'
            . 'advertisements-list.php';

        if (!is_file($template)) {
            return sprintf(
                '<div class="dsm-advertisements-error">%s</div>',
                esc_html__(
                    'No se encontró la plantilla del marketplace.',
                    'dsm-anuncios'
                )
            );
        }

        wp_enqueue_style(
            'dsm-anuncios-public'
        );

        wp_enqueue_script(
            'dsm-anuncios-public'
        );

        ob_start();

        require $template;

        return (string) ob_get_clean();
    }

    /**
     * Recoge los filtros públicos de la URL.
     *
     * @return array<string, mixed>
     */
    private function getFilters(): array
    {
        return [
            'search' =>
                $this->getTextQueryValue(
                    'dsm_search'
                ),

            'area_id' =>
                $this->getIntegerQueryValue(
                    'dsm_area'
                ),

            'municipality_id' =>
                $this->getIntegerQueryValue(
                    'dsm_municipality'
                ),

            'category_id' =>
                $this->getIntegerQueryValue(
                    'dsm_category'
                ),

            'brand' =>
                $this->getTextQueryValue(
                    'dsm_brand'
                ),

            'condition_code' =>
                $this->getKeyQueryValue(
                    'dsm_condition'
                ),

            'min_price' =>
                $this->getDecimalQueryValue(
                    'dsm_min_price'
                ),

            'max_price' =>
                $this->getDecimalQueryValue(
                    'dsm_max_price'
                ),

            'orderby' =>
                $this->getKeyQueryValue(
                    'dsm_orderby',
                    'published_at'
                ),

            'order' =>
                strtoupper(
                    $this->getKeyQueryValue(
                        'dsm_order',
                        'DESC'
                    )
                ),
        ];
    }

    /**
     * Resuelve el área territorial inicial.
     *
     * La prioridad es:
     *
     * 1. Parámetro dsm_area de la URL.
     * 2. Cookie territorial del marketplace.
     * 3. Área guardada en el perfil del cliente.
     * 4. Ningún área seleccionada.
     *
     * @param array<string, mixed>      $filters
     * @param array<string, mixed>|null $currentCustomer
     *
     * @return array<string, mixed>
     */
    private function resolveInitialArea(
        array $filters,
        ?array $currentCustomer
    ): array {
        /*
         * Una selección explícita en la URL siempre tiene
         * prioridad, incluso cuando su valor es cero.
         *
         * area_id = 0 elimina la preferencia guardada.
         */
        if (array_key_exists('dsm_area', $_GET)) {
            $areaId =
                max(
                    0,
                    (int) (
                        $filters['area_id']
                        ?? 0
                    )
                );

            $this->persistAreaPreference(
                $areaId
            );

            /*
             * Si el área se elimina, también se descarta
             * cualquier municipio recibido sin área.
             */
            if ($areaId <= 0) {
                $filters['municipality_id'] =
                    0;
            }

            return $filters;
        }

        $rememberedAreaId =
            $this->getRememberedAreaId();

        if ($rememberedAreaId > 0) {
            $filters['area_id'] =
                $rememberedAreaId;

            return $filters;
        }

        if (
            $currentCustomer !== null
            && (int) (
                $currentCustomer['area_id']
                ?? 0
            ) > 0
        ) {
            $filters['area_id'] =
                (int) $currentCustomer[
                    'area_id'
                ];

            /*
             * Solo utilizamos automáticamente el municipio
             * del perfil cuando tampoco se recibió uno
             * explícitamente desde la URL.
             */
            if (
                !array_key_exists(
                    'dsm_municipality',
                    $_GET
                )
                && (int) (
                    $currentCustomer[
                        'municipality_id'
                    ]
                    ?? 0
                ) > 0
            ) {
                $filters['municipality_id'] =
                    (int) $currentCustomer[
                        'municipality_id'
                    ];
            }
        }

        return $filters;
    }

    /**
     * Obtiene el cliente actual mediante integración pública.
     *
     * Formato esperado:
     *
     * [
     *     'id'                   => 1,
     *     'email'                => 'cliente@correo.com',
     *     'status'               => 'active',
     *     'display_name'         => 'Cliente',
     *     'area_id'              => 4,
     *     'municipality_id'      => 26,
     *     'avatar_attachment_id' => 20,
     * ]
     *
     * @return array<string, mixed>|null
     */
    private function resolveCurrentCustomerContext(): ?array
    {
        try {
            $context =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            if (!is_array($context)) {
                return null;
            }

            $customerId =
                max(
                    0,
                    (int) (
                        $context['id']
                        ?? 0
                    )
                );

            if ($customerId <= 0) {
                return null;
            }

            return [
                'id' =>
                    $customerId,

                'email' =>
                    sanitize_email(
                        (string) (
                            $context['email']
                            ?? ''
                        )
                    ),

                'status' =>
                    sanitize_key(
                        (string) (
                            $context['status']
                            ?? ''
                        )
                    ),

                'display_name' =>
                    sanitize_text_field(
                        (string) (
                            $context['display_name']
                            ?? ''
                        )
                    ),

                'area_id' =>
                    isset($context['area_id'])
                    && $context['area_id'] !== null
                        ? max(
                            0,
                            (int) $context[
                                'area_id'
                            ]
                        )
                        : null,

                'municipality_id' =>
                    isset(
                        $context[
                            'municipality_id'
                        ]
                    )
                    && $context[
                        'municipality_id'
                    ] !== null
                        ? max(
                            0,
                            (int) $context[
                                'municipality_id'
                            ]
                        )
                        : null,

                'avatar_attachment_id' =>
                    isset(
                        $context[
                            'avatar_attachment_id'
                        ]
                    )
                    && $context[
                        'avatar_attachment_id'
                    ] !== null
                        ? max(
                            0,
                            (int) $context[
                                'avatar_attachment_id'
                            ]
                        )
                        : null,
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudo resolver '
                . 'el contexto del cliente: '
                . $exception->getMessage()
            );

            return null;
        }
    }

    private function getCurrentPage(): int
    {
        if (isset($_GET['dsm_page'])) {
            return max(
                1,
                absint(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_page'
                        ]
                    )
                )
            );
        }

        $paged =
            get_query_var(
                'paged'
            );

        return max(
            1,
            is_numeric($paged)
                ? (int) $paged
                : 1
        );
    }

    private function getTextQueryValue(
        string $name,
        string $default = ''
    ): string {
        if (!isset($_GET[$name])) {
            return $default;
        }

        return sanitize_text_field(
            wp_unslash(
                (string) $_GET[$name]
            )
        );
    }

    private function getKeyQueryValue(
        string $name,
        string $default = ''
    ): string {
        if (!isset($_GET[$name])) {
            return $default;
        }

        return sanitize_key(
            wp_unslash(
                (string) $_GET[$name]
            )
        );
    }

    private function getIntegerQueryValue(
        string $name
    ): int {
        if (!isset($_GET[$name])) {
            return 0;
        }

        return absint(
            wp_unslash(
                (string) $_GET[$name]
            )
        );
    }

    private function getDecimalQueryValue(
        string $name
    ): ?float {
        if (!isset($_GET[$name])) {
            return null;
        }

        $value =
            trim(
                wp_unslash(
                    (string) $_GET[$name]
                )
            );

        if ($value === '') {
            return null;
        }

        $value =
            str_replace(
                ',',
                '.',
                $value
            );

        if (!is_numeric($value)) {
            return null;
        }

        return max(
            0.0,
            round(
                (float) $value,
                2
            )
        );
    }

    private function toBoolean(
        mixed $value
    ): bool {
        return in_array(
            strtolower(
                trim(
                    (string) $value
                )
            ),
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }

    /**
     * Guarda o elimina la preferencia territorial.
     */
    private function persistAreaPreference(
        int $areaId
    ): void {
        if (headers_sent()) {
            return;
        }

        if ($areaId <= 0) {
            setcookie(
                self::AREA_COOKIE,
                '',
                [
                    'expires' =>
                        time()
                        - HOUR_IN_SECONDS,

                    'path' =>
                        COOKIEPATH
                        ?: '/',

                    'domain' =>
                        COOKIE_DOMAIN
                        ?: '',

                    'secure' =>
                        is_ssl(),

                    'httponly' =>
                        true,

                    'samesite' =>
                        'Lax',
                ]
            );

            unset(
                $_COOKIE[
                    self::AREA_COOKIE
                ]
            );

            return;
        }

        setcookie(
            self::AREA_COOKIE,
            (string) $areaId,
            [
                'expires' =>
                    time()
                    + self::AREA_COOKIE_DURATION,

                'path' =>
                    COOKIEPATH
                    ?: '/',

                'domain' =>
                    COOKIE_DOMAIN
                    ?: '',

                'secure' =>
                    is_ssl(),

                'httponly' =>
                    true,

                'samesite' =>
                    'Lax',
            ]
        );

        $_COOKIE[
            self::AREA_COOKIE
        ] = (string) $areaId;
    }

    /**
     * Recupera el área guardada en la cookie territorial.
     */
    private function getRememberedAreaId(): int
    {
        $value =
            $_COOKIE[
                self::AREA_COOKIE
            ]
            ?? null;

        if (
            !is_string($value)
            || $value === ''
        ) {
            return 0;
        }

        return max(
            0,
            absint(
                $value
            )
        );
    }
}