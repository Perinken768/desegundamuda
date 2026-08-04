<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode del formulario público de anuncios.
 *
 * Uso:
 *
 * [dsm_advertisement_form]
 *
 * También admite:
 *
 * [dsm_advertisement_form advertisement_id="25"]
 *
 * Responsabilidades:
 *
 * - comprobar que existe un cliente DSM autenticado;
 * - comprobar que la cuenta está activa;
 * - exigir al menos un método de contacto válido;
 * - consultar si el cliente puede abrir otro anuncio;
 * - preparar el contexto para la plantilla;
 * - cargar los recursos públicos del plugin.
 *
 * DSM Anuncios no conoce las clases internas de DSM Clientes
 * ni de DSM Suscripciones. Toda la comunicación se realiza
 * mediante filtros públicos.
 */
final class AdvertisementFormShortcode
{
    public const SHORTCODE =
        'dsm_advertisement_form';

    private const DEFAULT_LOGIN_PATH =
        '/iniciar-sesion/';

    private const DEFAULT_PROFILE_PATH =
        '/editar-perfil/';

    private const DEFAULT_ACCOUNT_PATH =
        '/mi-cuenta/';

    /**
     * Registra el shortcode y los recursos públicos.
     */
    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );

        add_action(
            'wp_enqueue_scripts',
            [
                self::class,
                'registerAssets',
            ]
        );
    }

    /**
     * Registra los recursos reutilizados por el formulario.
     */
    public static function registerAssets(): void
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
     * Renderiza el formulario o el bloqueo correspondiente.
     *
     * @param array<string, mixed> $attributes
     */
    public static function render(
        array $attributes = []
    ): string {
        try {
            $attributes =
                shortcode_atts(
                    [
                        'advertisement_id' =>
                            0,
                    ],
                    $attributes,
                    self::SHORTCODE
                );

            $currentCustomer =
                self::resolveCurrentCustomerContext();

            if ($currentCustomer === null) {
                return self::renderAuthenticationRequired();
            }

            if (
                !self::isCustomerActive(
                    $currentCustomer
                )
            ) {
                return self::renderInactiveCustomer();
            }

            if (
                !self::hasValidContactMethod(
                    $currentCustomer
                )
            ) {
                return self::renderContactRequired();
            }

            $advertisementId =
                max(
                    0,
                    absint(
                        (string) (
                            $attributes[
                                'advertisement_id'
                            ]
                            ?? 0
                        )
                    )
                );

            /*
             * En creación se consulta si el cliente puede
             * abrir otro anuncio.
             *
             * En edición no se consume una plaza nueva.
             */
            $publicationAvailability =
                $advertisementId <= 0
                    ? self::resolvePublicationAvailability(
                        $currentCustomer
                    )
                    : self::defaultPublicationAvailability();

            if (
                empty(
                    $publicationAvailability[
                        'allowed'
                    ]
                )
            ) {
                return self::renderPublicationLimitReached(
                    $publicationAvailability
                );
            }

            $advertisement =
                null;

            if ($advertisementId > 0) {
                $advertisement =
                    apply_filters(
                        'dsm_customer_editable_advertisement',
                        null,
                        $advertisementId,
                        (int) $currentCustomer['id']
                    );

                /*
                 * Si se solicita una edición, el anuncio debe
                 * existir y pertenecer al cliente autenticado.
                 */
                if (!is_array($advertisement)) {
                    return self::renderAdvertisementNotEditable();
                }
            }

            $categories =
                apply_filters(
                    'dsm_advertisement_form_categories',
                    []
                );

            if (!is_array($categories)) {
                $categories = [];
            }

            $locations =
                apply_filters(
                    'dsm_advertisement_form_locations',
                    []
                );

            if (!is_array($locations)) {
                $locations = [];
            }

            $formConfiguration =
                apply_filters(
                    'dsm_advertisement_form_configuration',
                    [
                        'minimum_images' =>
                            0,

                        'maximum_images' =>
                            10,

                        'title_max_length' =>
                            180,

                        'description_max_length' =>
                            10000,

                        'minimum_price' =>
                            0.01,

                        'maximum_price' =>
                            null,

                        'auto_publish_enabled' =>
                            false,
                    ],
                    $currentCustomer,
                    $advertisement
                );

            if (!is_array($formConfiguration)) {
                $formConfiguration = [];
            }

            $template =
                DSM_ANUNCIOS_PATH
                . 'templates/account/'
                . 'advertisement-form.php';

            if (!is_file($template)) {
                return self::renderTemplateMissing();
            }

            wp_enqueue_style(
                'dsm-anuncios-public'
            );

            wp_enqueue_script(
                'dsm-anuncios-public'
            );

            /*
             * Variables disponibles dentro de la plantilla:
             *
             * @var array<string, mixed>      $currentCustomer
             * @var array<string, mixed>|null $advertisement
             * @var int                       $advertisementId
             * @var array<int, mixed>         $categories
             * @var array<int, mixed>         $locations
             * @var array<string, mixed>      $formConfiguration
             * @var array<string, mixed>      $publicationAvailability
             * @var bool                      $isEditing
             */
            $isEditing =
                $advertisementId > 0;

            ob_start();

            require $template;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudo renderizar '
                . 'el formulario de anuncios: '
                . $exception->getMessage()
            );

            return self::renderUnexpectedError();
        }
    }

    /**
     * Obtiene un contexto neutral del cliente autenticado.
     *
     * Formato esperado:
     *
     * [
     *     'id'                  => 2,
     *     'email'               => 'cliente@correo.com',
     *     'status'              => 'active',
     *     'display_name'        => 'Cliente',
     *     'phone'               => '+34600123456',
     *     'allow_phone_calls'   => true,
     *     'allow_whatsapp'      => true,
     *     'has_valid_contact'   => true,
     * ]
     *
     * @return array<string, mixed>|null
     */
    private static function resolveCurrentCustomerContext(): ?array
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
                        $context['display_name']
                        ?? ''
                    )
                ),

            'phone' =>
                sanitize_text_field(
                    (string) (
                        $context['phone']
                        ?? ''
                    )
                ),

            'allow_phone_calls' =>
                !empty(
                    $context[
                        'allow_phone_calls'
                    ]
                ),

            'allow_whatsapp' =>
                !empty(
                    $context[
                        'allow_whatsapp'
                    ]
                ),

            'has_valid_contact' =>
                !empty(
                    $context[
                        'has_valid_contact'
                    ]
                ),

            'island_id' =>
                isset($context['island_id'])
                && $context['island_id'] !== null
                    ? max(
                        0,
                        (int) $context[
                            'island_id'
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
     * Comprueba que la cuenta del cliente puede operar.
     *
     * Un estado vacío no se considera válido.
     */
    private static function isCustomerActive(
        array $currentCustomer
    ): bool {
        return sanitize_key(
            (string) (
                $currentCustomer['status']
                ?? ''
            )
        ) === 'active';
    }

    /**
     * Comprueba que existe:
     *
     * - un teléfono;
     * - llamadas o WhatsApp autorizados;
     * - confirmación de DSM Clientes.
     *
     * @param array<string, mixed> $currentCustomer
     */
    private static function hasValidContactMethod(
        array $currentCustomer
    ): bool {
        $phone =
            trim(
                (string) (
                    $currentCustomer['phone']
                    ?? ''
                )
            );

        $allowsPhoneCalls =
            !empty(
                $currentCustomer[
                    'allow_phone_calls'
                ]
            );

        $allowsWhatsapp =
            !empty(
                $currentCustomer[
                    'allow_whatsapp'
                ]
            );

        $integrationConfirmsContact =
            !empty(
                $currentCustomer[
                    'has_valid_contact'
                ]
            );

        return $phone !== ''
            && (
                $allowsPhoneCalls
                || $allowsWhatsapp
            )
            && $integrationConfirmsContact;
    }

    /**
     * Consulta el límite de anuncios abiertos.
     *
     * dsm-anuncios no conoce los planes ni las suscripciones.
     * El plugin correspondiente podrá modificar este resultado.
     *
     * Resultado esperado:
     *
     * [
     *     'allowed'    => true,
     *     'open_count' => 4,
     *     'limit'      => 10,
     *     'remaining'  => 6,
     *     'plan'       => 'free',
     *     'message'    => '',
     * ]
     *
     * @param array<string, mixed> $currentCustomer
     *
     * @return array<string, mixed>
     */
    private static function resolvePublicationAvailability(
        array $currentCustomer
    ): array {
        $default =
            self::defaultPublicationAvailability();

        $availability =
            apply_filters(
                'dsm_customer_advertisement_publication_availability',
                $default,
                (int) (
                    $currentCustomer['id']
                    ?? 0
                ),
                $currentCustomer
            );

        if (!is_array($availability)) {
            return $default;
        }

        $limit =
            isset($availability['limit'])
            && is_numeric(
                $availability['limit']
            )
                ? max(
                    0,
                    (int) $availability[
                        'limit'
                    ]
                )
                : null;

        $openCount =
            max(
                0,
                (int) (
                    $availability[
                        'open_count'
                    ]
                    ?? 0
                )
            );

        $remaining =
            $limit !== null
                ? max(
                    0,
                    $limit - $openCount
                )
                : null;

        $allowed =
            array_key_exists(
                'allowed',
                $availability
            )
                ? !empty(
                    $availability['allowed']
                )
                : (
                    $limit === null
                    || $openCount < $limit
                );

        return [
            'allowed' =>
                $allowed,

            'open_count' =>
                $openCount,

            'limit' =>
                $limit,

            'remaining' =>
                $remaining,

            'plan' =>
                sanitize_key(
                    (string) (
                        $availability['plan']
                        ?? ''
                    )
                ),

            'message' =>
                sanitize_text_field(
                    (string) (
                        $availability['message']
                        ?? ''
                    )
                ),
        ];
    }

    /**
     * Mientras dsm-suscripciones no esté instalado,
     * el shortcode no impone todavía un límite propio.
     *
     * El límite Free de 10 anuncios abiertos se conectará
     * mediante el filtro de disponibilidad.
     *
     * @return array<string, mixed>
     */
    private static function defaultPublicationAvailability(): array
    {
        return [
            'allowed' =>
                true,

            'open_count' =>
                0,

            'limit' =>
                null,

            'remaining' =>
                null,

            'plan' =>
                '',

            'message' =>
                '',
        ];
    }

    private static function renderAuthenticationRequired(): string
    {
        $loginUrl =
            home_url(
                self::DEFAULT_LOGIN_PATH
            );

        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--login">
                <span
                    class="dashicons dashicons-lock"
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
                'Inicia sesión para publicar',
                'dsm-anuncios'
            ),
            esc_html__(
                'Necesitas una cuenta de DeSegundaMuda para crear y gestionar anuncios.',
                'dsm-anuncios'
            ),
            esc_url(
                $loginUrl
            ),
            esc_html__(
                'Iniciar sesión',
                'dsm-anuncios'
            )
        );
    }

    private static function renderInactiveCustomer(): string
    {
        $accountUrl =
            home_url(
                self::DEFAULT_ACCOUNT_PATH
            );

        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--inactive">
                <span
                    class="dashicons dashicons-warning"
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
                'Tu cuenta no puede publicar anuncios',
                'dsm-anuncios'
            ),
            esc_html__(
                'Revisa el estado de tu cuenta antes de intentar crear un anuncio.',
                'dsm-anuncios'
            ),
            esc_url(
                $accountUrl
            ),
            esc_html__(
                'Ir a mi cuenta',
                'dsm-anuncios'
            )
        );
    }

    private static function renderContactRequired(): string
    {
        $profileUrl =
            home_url(
                self::DEFAULT_PROFILE_PATH
            );

        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--contact">
                <span
                    class="dashicons dashicons-phone"
                    aria-hidden="true"
                ></span>

                <h1>%1$s</h1>

                <p>%2$s</p>

                <ul>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ul>

                <a
                    class="dsm-button dsm-button--primary"
                    href="%5$s"
                >
                    %6$s
                </a>
            </section>
            ',
            esc_html__(
                'Configura una forma de contacto',
                'dsm-anuncios'
            ),
            esc_html__(
                'Antes de publicar un anuncio debes configurar cómo podrán contactar contigo.',
                'dsm-anuncios'
            ),
            esc_html__(
                'Añade un número de teléfono válido.',
                'dsm-anuncios'
            ),
            esc_html__(
                'Activa llamadas, WhatsApp o ambos métodos.',
                'dsm-anuncios'
            ),
            esc_url(
                $profileUrl
            ),
            esc_html__(
                'Configurar contacto',
                'dsm-anuncios'
            )
        );
    }

    /**
     * @param array<string, mixed> $availability
     */
    private static function renderPublicationLimitReached(
        array $availability
    ): string {
        $limit =
            isset($availability['limit'])
            && $availability['limit'] !== null
                ? max(
                    0,
                    (int) $availability['limit']
                )
                : 0;

        $openCount =
            max(
                0,
                (int) (
                    $availability['open_count']
                    ?? 0
                )
            );

        $customMessage =
            trim(
                (string) (
                    $availability['message']
                    ?? ''
                )
            );

        $message =
            $customMessage !== ''
                ? $customMessage
                : sprintf(
                    __(
                        'Tienes %1$d de %2$d anuncios abiertos. Cierra, vende o elimina uno para poder publicar otro.',
                        'dsm-anuncios'
                    ),
                    $openCount,
                    $limit
                );

        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--limit">
                <span
                    class="dashicons dashicons-chart-bar"
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
                'Has alcanzado el límite de anuncios abiertos',
                'dsm-anuncios'
            ),
            esc_html(
                $message
            ),
            esc_url(
                home_url(
                    self::DEFAULT_ACCOUNT_PATH
                )
            ),
            esc_html__(
                'Gestionar mis anuncios',
                'dsm-anuncios'
            )
        );
    }

    private static function renderAdvertisementNotEditable(): string
    {
        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--error">
                <span
                    class="dashicons dashicons-warning"
                    aria-hidden="true"
                ></span>

                <h1>%1$s</h1>

                <p>%2$s</p>
            </section>
            ',
            esc_html__(
                'No puedes editar este anuncio',
                'dsm-anuncios'
            ),
            esc_html__(
                'El anuncio no existe o no pertenece a tu cuenta.',
                'dsm-anuncios'
            )
        );
    }

    private static function renderTemplateMissing(): string
    {
        return sprintf(
            '<div class="dsm-advertisements-error">%s</div>',
            esc_html__(
                'No se encontró la plantilla del formulario de anuncios.',
                'dsm-anuncios'
            )
        );
    }

    private static function renderUnexpectedError(): string
    {
        return sprintf(
            '
            <section class="dsm-advertisement-form-gate dsm-advertisement-form-gate--error">
                <span
                    class="dashicons dashicons-warning"
                    aria-hidden="true"
                ></span>

                <h1>%1$s</h1>

                <p>%2$s</p>
            </section>
            ',
            esc_html__(
                'No se pudo cargar el formulario',
                'dsm-anuncios'
            ),
            esc_html__(
                'Se ha producido un error inesperado. Inténtalo de nuevo más tarde.',
                'dsm-anuncios'
            )
        );
    }

    private function __construct()
    {
    }
}