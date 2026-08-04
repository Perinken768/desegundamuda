<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador de la ficha pública de anuncios.
 *
 * Registra la URL:
 *
 * /anuncio/{slug}/
 *
 * La implementación es portable entre:
 *
 * - entorno local;
 * - staging;
 * - VPS de IONOS;
 *
 * No contiene dominios ni rutas físicas hardcodeadas.
 */
final class AdvertisementController
{
    public const QUERY_VAR =
        'dsm_advertisement_slug';

    public const REWRITE_BASE =
        'anuncio';

    private const REWRITE_OPTION =
        'dsm_anuncios_rewrite_version';

    private const REWRITE_VERSION =
        '1';

    public function __construct(
        private readonly AdvertisementSearchRepository $repository
    ) {
    }

    /**
     * Registra hooks públicos.
     */
    public function register(): void
    {
        add_action(
            'init',
            [$this, 'registerRewriteRules']
        );

        add_filter(
            'query_vars',
            [$this, 'registerQueryVars']
        );

        add_filter(
            'template_include',
            [$this, 'resolveTemplate'],
            99
        );

        add_action(
            'template_redirect',
            [$this, 'handleCanonicalRedirect']
        );

        add_action(
            'wp',
            [$this, 'maybeFlushRewriteRules']
        );

        add_filter(
            'document_title_parts',
            [$this, 'filterDocumentTitle']
        );

        add_action(
            'wp_head',
            [$this, 'renderMetaTags'],
            5
        );

        add_filter(
            'body_class',
            [$this, 'addBodyClasses']
        );
    }

    /**
     * Registra:
     *
     * /anuncio/{slug}/
     */
    public function registerRewriteRules(): void
    {
        add_rewrite_rule(
            '^'
            . preg_quote(
                self::REWRITE_BASE,
                '#'
            )
            . '/([^/]+)/?$',
            'index.php?'
            . self::QUERY_VAR
            . '=$matches[1]',
            'top'
        );
    }

    /**
     * Registra la variable pública del slug.
     *
     * @param array<int, string> $queryVars
     *
     * @return array<int, string>
     */
    public function registerQueryVars(
        array $queryVars
    ): array {
        if (
            !in_array(
                self::QUERY_VAR,
                $queryVars,
                true
            )
        ) {
            $queryVars[] =
                self::QUERY_VAR;
        }

        return $queryVars;
    }

    /**
     * Resuelve una plantilla virtual para la ficha.
     *
     * La plantilla virtual contiene:
     *
     * get_header()
     * shortcode de detalle
     * get_footer()
     */
    public function resolveTemplate(
        string $template
    ): string {
        if (!$this->isAdvertisementRequest()) {
            return $template;
        }

        $advertisement =
            $this->resolveCurrentAdvertisement();

        if ($advertisement === null) {
            global $wp_query;

            if ($wp_query !== null) {
                $wp_query->set_404();
            }

            status_header(404);
            nocache_headers();

            $notFoundTemplate =
                get_404_template();

            return is_string($notFoundTemplate)
            && $notFoundTemplate !== ''
                ? $notFoundTemplate
                : $template;
        }

        $virtualTemplate =
            DSM_ANUNCIOS_PATH
            . 'templates/public/'
            . 'single-advertisement.php';

        if (!is_file($virtualTemplate)) {
            return $template;
        }

        return $virtualTemplate;
    }

    /**
     * Evita URLs alternativas o slugs no normalizados.
     */
    public function handleCanonicalRedirect(): void
    {
        if (!$this->isAdvertisementRequest()) {
            return;
        }

        $advertisement =
            $this->resolveCurrentAdvertisement();

        if ($advertisement === null) {
            return;
        }

        $expectedUrl =
            (string) (
                $advertisement['public_url']
                ?? ''
            );

        if ($expectedUrl === '') {
            return;
        }

        $currentUrl =
            $this->getCurrentUrl();

        if (
            $currentUrl === ''
            || untrailingslashit($currentUrl)
                === untrailingslashit(
                    $expectedUrl
                )
        ) {
            return;
        }

        wp_safe_redirect(
            $expectedUrl,
            301
        );

        exit;
    }

    /**
     * Regenera las reglas una sola vez por versión.
     *
     * Esto evita exigir una reactivación manual del plugin.
     */
    public function maybeFlushRewriteRules(): void
    {
        $installedVersion =
            (string) get_option(
                self::REWRITE_OPTION,
                ''
            );

        if (
            $installedVersion
            === self::REWRITE_VERSION
        ) {
            return;
        }

        flush_rewrite_rules(
            false
        );

        update_option(
            self::REWRITE_OPTION,
            self::REWRITE_VERSION,
            false
        );
    }

    /**
     * Ajusta el título HTML de la ficha.
     *
     * @param array<string, string> $titleParts
     *
     * @return array<string, string>
     */
    public function filterDocumentTitle(
        array $titleParts
    ): array {
        if (!$this->isAdvertisementRequest()) {
            return $titleParts;
        }

        $advertisement =
            $this->resolveCurrentAdvertisement();

        if ($advertisement === null) {
            return $titleParts;
        }

        $title =
            trim(
                (string) (
                    $advertisement['title']
                    ?? ''
                )
            );

        if ($title !== '') {
            $titleParts['title'] =
                $title;
        }

        return $titleParts;
    }

    /**
     * Añade metadatos sociales básicos.
     */
    public function renderMetaTags(): void
    {
        if (!$this->isAdvertisementRequest()) {
            return;
        }

        $advertisement =
            $this->resolveCurrentAdvertisement();

        if ($advertisement === null) {
            return;
        }

        $title =
            trim(
                (string) (
                    $advertisement['title']
                    ?? ''
                )
            );

        $description =
            wp_trim_words(
                wp_strip_all_tags(
                    (string) (
                        $advertisement['description']
                        ?? ''
                    )
                ),
                30,
                '…'
            );

        $url =
            trim(
                (string) (
                    $advertisement['public_url']
                    ?? ''
                )
            );

        $imageUrl =
            trim(
                (string) (
                    $advertisement[
                        'cover_full_url'
                    ]
                    ?? ''
                )
            );

        if ($title !== '') {
            echo '<meta property="og:title" content="'
                . esc_attr($title)
                . '">' . "\n";
        }

        if ($description !== '') {
            echo '<meta name="description" content="'
                . esc_attr($description)
                . '">' . "\n";

            echo '<meta property="og:description" content="'
                . esc_attr($description)
                . '">' . "\n";
        }

        if ($url !== '') {
            echo '<meta property="og:url" content="'
                . esc_url($url)
                . '">' . "\n";
        }

        echo '<meta property="og:type" content="product">'
            . "\n";

        if ($imageUrl !== '') {
            echo '<meta property="og:image" content="'
                . esc_url($imageUrl)
                . '">' . "\n";
        }
    }

    /**
     * Añade clases útiles al body.
     *
     * @param array<int, string> $classes
     *
     * @return array<int, string>
     */
    public function addBodyClasses(
        array $classes
    ): array {
        if (!$this->isAdvertisementRequest()) {
            return $classes;
        }

        $classes[] =
            'dsm-single-advertisement';

        $advertisement =
            $this->resolveCurrentAdvertisement();

        if ($advertisement === null) {
            $classes[] =
                'dsm-single-advertisement-not-found';

            return array_values(
                array_unique($classes)
            );
        }

        if (
            !empty(
                $advertisement['is_reserved']
            )
        ) {
            $classes[] =
                'dsm-advertisement-is-reserved';
        }

        if (
            !empty(
                $advertisement['is_promoted']
            )
        ) {
            $classes[] =
                'dsm-advertisement-is-promoted';
        }

        return array_values(
            array_unique($classes)
        );
    }

    /**
     * Indica si la petición actual corresponde a una ficha.
     */
    public function isAdvertisementRequest(): bool
    {
        $slug =
            get_query_var(
                self::QUERY_VAR
            );

        return is_string($slug)
            && sanitize_title($slug) !== '';
    }

    /**
     * Recupera el anuncio solicitado.
     *
     * @return array<string, mixed>|null
     */
    public function resolveCurrentAdvertisement(): ?array
    {
        static $resolved = false;

        static $advertisement = null;

        if ($resolved) {
            return is_array($advertisement)
                ? $advertisement
                : null;
        }

        $resolved =
            true;

        $slug =
            get_query_var(
                self::QUERY_VAR
            );

        if (!is_string($slug)) {
            return null;
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            return null;
        }

        $advertisement =
            $this->repository
                ->findPublicBySlug(
                    $slug
                );

        return is_array($advertisement)
            ? $advertisement
            : null;
    }

    /**
     * Obtiene la URL actual sin depender del dominio.
     */
    private function getCurrentUrl(): string
    {
        $requestUri =
            $_SERVER['REQUEST_URI']
            ?? '';

        if (!is_string($requestUri)) {
            return '';
        }

        $requestUri =
            wp_unslash(
                $requestUri
            );

        if ($requestUri === '') {
            return '';
        }

        return home_url(
            $requestUri
        );
    }
}