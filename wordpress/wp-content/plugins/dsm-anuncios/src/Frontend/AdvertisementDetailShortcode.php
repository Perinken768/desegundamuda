<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode de la ficha pública de un anuncio.
 *
 * Uso:
 *
 * [dsm_advertisement_detail]
 *
 * También admite:
 *
 * [dsm_advertisement_detail slug="mi-anuncio"]
 * [dsm_advertisement_detail id="25"]
 *
 * Parámetros adicionales:
 *
 * [dsm_advertisement_detail show_related="0"]
 * [dsm_advertisement_detail related_limit="4"]
 *
 * Normalmente el slug será proporcionado por AdvertisementController
 * mediante la variable de consulta:
 *
 * dsm_advertisement_slug
 */
final class AdvertisementDetailShortcode
{
    public const SHORTCODE =
        'dsm_advertisement_detail';

    private AdvertisementSearchRepository $repository;

    private RelatedAdvertisementRepository $relatedRepository;

    public function __construct(
        AdvertisementSearchRepository $repository,
        RelatedAdvertisementRepository $relatedRepository
    ) {
        $this->repository =
            $repository;

        $this->relatedRepository =
            $relatedRepository;
    }

    /**
     * Registra el shortcode y los recursos.
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
     * Registra los recursos de la ficha.
     *
     * Se utilizan los mismos archivos públicos que en
     * el marketplace:
     *
     * - estilos del listado y la ficha;
     * - filtros públicos;
     * - comportamiento de la galería.
     */
    public function registerAssets(): void
    {
        $cssRelativePath =
            'assets/public/css/advertisements.css';

        $cssFilePath =
            DSM_ANUNCIOS_PATH
            . $cssRelativePath;

        if (
            !wp_style_is(
                'dsm-anuncios-public',
                'registered'
            )
        ) {
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
        }

        $jsRelativePath =
            'assets/public/js/advertisements.js';

        $jsFilePath =
            DSM_ANUNCIOS_PATH
            . $jsRelativePath;

        if (
            !wp_script_is(
                'dsm-anuncios-public',
                'registered'
            )
        ) {
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
    }

    /**
     * Renderiza la ficha pública.
     *
     * @param array<string, mixed> $attributes
     */
    public function render(
        array $attributes = []
    ): string {
        $attributes =
            shortcode_atts(
                [
                    'id' =>
                        0,

                    'slug' =>
                        '',

                    'show_breadcrumbs' =>
                        '1',

                    'show_seller' =>
                        '1',

                    'show_actions' =>
                        '1',

                    'show_related' =>
                        '1',

                    'related_limit' =>
                        RelatedAdvertisementRepository::
                            DEFAULT_LIMIT,
                ],
                $attributes,
                self::SHORTCODE
            );

        $advertisement =
            $this->resolveAdvertisement(
                $attributes
            );

        if ($advertisement === null) {
            return $this->renderNotFound();
        }

        $showBreadcrumbs =
            $this->toBoolean(
                $attributes[
                    'show_breadcrumbs'
                ]
            );

        $showSeller =
            $this->toBoolean(
                $attributes[
                    'show_seller'
                ]
            );

        $showActions =
            $this->toBoolean(
                $attributes[
                    'show_actions'
                ]
            );

        $showRelated =
            $this->toBoolean(
                $attributes[
                    'show_related'
                ]
            );

        $relatedLimit =
            min(
                RelatedAdvertisementRepository::
                    MAX_LIMIT,
                max(
                    1,
                    absint(
                        (string) (
                            $attributes[
                                'related_limit'
                            ]
                            ?? RelatedAdvertisementRepository::
                                DEFAULT_LIMIT
                        )
                    )
                )
            );

        $currentCustomer =
            $this->resolveCurrentCustomerContext();

        $isOwner =
            $currentCustomer !== null
            && (int) (
                $currentCustomer['id']
                ?? 0
            ) === (int) (
                $advertisement[
                    'customer_id'
                ]
                ?? 0
            );

        /*
         * Los anuncios relacionados se obtienen únicamente
         * cuando el shortcode tiene habilitada esta sección.
         *
         * La plantilla siempre recibe un array, lo que evita
         * comprobaciones ambiguas o variables no definidas.
         *
         * @var array<int, array<string, mixed>>
         */
        $relatedAdvertisements =
            $showRelated
                ? $this->relatedRepository
                    ->findRelated(
                        $advertisement,
                        $relatedLimit
                    )
                : [];

        $template =
            DSM_ANUNCIOS_PATH
            . 'templates/public/'
            . 'advertisement-detail.php';

        if (!is_file($template)) {
            return sprintf(
                '<div class="dsm-advertisements-error">%s</div>',
                esc_html__(
                    'No se encontró la plantilla de la ficha del anuncio.',
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
     * Resuelve el anuncio mediante:
     *
     * 1. ID del shortcode.
     * 2. Slug del shortcode.
     * 3. Query var de la URL amigable.
     * 4. Parámetro GET dsm_advertisement_slug.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    private function resolveAdvertisement(
        array $attributes
    ): ?array {
        $advertisementId =
            absint(
                (string) (
                    $attributes['id']
                    ?? 0
                )
            );

        if ($advertisementId > 0) {
            return $this->repository
                ->findPublicById(
                    $advertisementId
                );
        }

        $slug =
            sanitize_title(
                (string) (
                    $attributes['slug']
                    ?? ''
                )
            );

        if ($slug === '') {
            $querySlug =
                get_query_var(
                    'dsm_advertisement_slug'
                );

            if (is_string($querySlug)) {
                $slug =
                    sanitize_title(
                        $querySlug
                    );
            }
        }

        if (
            $slug === ''
            && isset(
                $_GET[
                    'dsm_advertisement_slug'
                ]
            )
        ) {
            $slug =
                sanitize_title(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_advertisement_slug'
                        ]
                    )
                );
        }

        if ($slug === '') {
            return null;
        }

        return $this->repository
            ->findPublicBySlug(
                $slug
            );
    }

    /**
     * Obtiene un contexto neutral del cliente autenticado.
     *
     * DSM Anuncios no conoce el sistema interno de sesiones
     * ni las clases propias de DSM Clientes.
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
                        $context[
                            'display_name'
                        ]
                        ?? ''
                    )
                ),

            'area_id' =>
                isset(
                    $context['area_id']
                )
                && $context[
                    'area_id'
                ] !== null
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
    }

    /**
     * Renderiza la respuesta cuando el anuncio no existe
     * o no está públicamente visible.
     */
    private function renderNotFound(): string
    {
        $marketplaceUrl =
            home_url(
                '/anuncios/'
            );

        return sprintf(
            '
            <section class="dsm-advertisement-not-found">
                <span
                    class="dashicons dashicons-search"
                    aria-hidden="true"
                ></span>

                <h1>%1$s</h1>

                <p>%2$s</p>

                <a
                    class="dsm-button dsm-button--primary"
                    href="%3$s"
                >
                    %4$s
                </a>
            </section>
            ',
            esc_html__(
                'Anuncio no disponible',
                'dsm-anuncios'
            ),
            esc_html__(
                'El anuncio no existe, ha sido retirado o ya no está publicado.',
                'dsm-anuncios'
            ),
            esc_url(
                $marketplaceUrl
            ),
            esc_html__(
                'Volver a los anuncios',
                'dsm-anuncios'
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
}