<?php

declare(strict_types=1);

namespace DSM\Facturacion\Admin;

use DSM\Facturacion\Billing\BillingSettings;
use DSM\Facturacion\Verifactu\VerifactuSettings;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class BillingSettingsPage
{
    public const MENU_SLUG =
        'dsm-facturacion';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_facturacion_save_settings';

    private const NONCE_ACTION =
        'dsm_facturacion_save_settings';

    private const NONCE_NAME =
        'dsm_facturacion_settings_nonce';

    public static function register(): void
    {
        $page =
            new self();

        add_action(
            'admin_menu',
            [
                $page,
                'registerMenu',
            ],
            5
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $page,
                'handleSave',
            ]
        );

        add_action(
            'admin_enqueue_scripts',
            [
                $page,
                'enqueueAssets',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_menu_page(
            page_title:
                __(
                    'DSM Facturación',
                    'dsm-facturacion'
                ),

            menu_title:
                __(
                    'DSM Facturación',
                    'dsm-facturacion'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [
                    $this,
                    'render',
                ],

            icon_url:
                'dashicons-media-spreadsheet',

            position:
                31
        );

        add_submenu_page(
            parent_slug:
                self::MENU_SLUG,

            page_title:
                __(
                    'Configuración',
                    'dsm-facturacion'
                ),

            menu_title:
                __(
                    'Configuración',
                    'dsm-facturacion'
                ),

            capability:
                self::CAPABILITY,

            menu_slug:
                self::MENU_SLUG,

            callback:
                [
                    $this,
                    'render',
                ]
        );
    }

    public function enqueueAssets(
        string $hookSuffix
    ): void {
        if (
            !str_contains(
                $hookSuffix,
                self::MENU_SLUG
            )
        ) {
            return;
        }

        wp_enqueue_media();

        $relativePath =
            'assets/admin/css/billing-settings.css';

        $filePath =
            DSM_FACTURACION_PATH
            . $relativePath;

        $version =
            is_file($filePath)
                ? (string) filemtime(
                    $filePath
                )
                : DSM_FACTURACION_VERSION;

        wp_enqueue_style(
            'dsm-facturacion-admin',
            DSM_FACTURACION_URL
                . $relativePath,
            [],
            $version
        );
    }

    public function render(): void
    {
        $this->assertPermission();

        $settings =
            BillingSettings::get();

        $verifactuSettings =
            VerifactuSettings::get();

        $verifactuEndpoint =
            VerifactuSettings::getEndpoint(
                $verifactuSettings
            );

        $verifactuWsdl =
            VerifactuSettings::getWsdlUrl();

        $verifactuStatus =
            VerifactuSettings
                ::getConfigurationStatus();

        $notice =
            isset($_GET['notice'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'notice'
                        ]
                    )
                )
                : '';

        $error =
            isset($_GET['error'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_GET[
                            'error'
                        ]
                    )
                )
                : '';

        $template =
            DSM_FACTURACION_PATH
            . 'templates/admin/'
            . 'billing-settings.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla de configuración de facturación.'
            );
        }

        require $template;
    }

    public function handleSave(): never
    {
        $this->assertPermission();

        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        try {
            $taxRateRaw =
                isset($_POST['tax_rate'])
                    ? str_replace(
                        ',',
                        '.',
                        trim(
                            wp_unslash(
                                (string) $_POST[
                                    'tax_rate'
                                ]
                            )
                        )
                    )
                    : '7';

            if (
                $taxRateRaw === ''
                || !is_numeric(
                    $taxRateRaw
                )
            ) {
                throw new RuntimeException(
                    'El tipo de IGIC no es válido.'
                );
            }

            $taxRate =
                (float) $taxRateRaw;

            if (
                $taxRate < 0
                || $taxRate > 100
            ) {
                throw new RuntimeException(
                    'El tipo de IGIC debe estar entre 0 y 100.'
                );
            }

            $series =
                isset($_POST['series'])
                    ? strtoupper(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_POST[
                                    'series'
                                ]
                            )
                        )
                    )
                    : 'DSM';

            $series =
                preg_replace(
                    '/[^A-Z0-9_-]/',
                    '',
                    $series
                );

            if (
                !is_string($series)
                || $series === ''
            ) {
                throw new RuntimeException(
                    'La serie de facturación no es válida.'
                );
            }

            $countryCode =
                isset($_POST['country_code'])
                    ? strtoupper(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_POST[
                                    'country_code'
                                ]
                            )
                        )
                    )
                    : 'ES';

            if (
                strlen(
                    $countryCode
                ) !== 2
                || !ctype_alpha(
                    $countryCode
                )
            ) {
                throw new RuntimeException(
                    'El código de país no es válido.'
                );
            }

            $billingData = [
                'fiscal_name' =>
                    self::postedText(
                        'fiscal_name'
                    ),

                'tax_id' =>
                    strtoupper(
                        self::postedText(
                            'tax_id'
                        )
                    ),

                'address_line_1' =>
                    self::postedText(
                        'address_line_1'
                    ),

                'address_line_2' =>
                    self::postedText(
                        'address_line_2'
                    ),

                'postal_code' =>
                    self::postedText(
                        'postal_code'
                    ),

                'city' =>
                    self::postedText(
                        'city'
                    ),

                'province' =>
                    self::postedText(
                        'province'
                    ),

                'country_code' =>
                    $countryCode,

                'email' =>
                    isset($_POST['email'])
                        ? sanitize_email(
                            wp_unslash(
                                (string) $_POST[
                                    'email'
                                ]
                            )
                        )
                        : '',

                'series' =>
                    $series,

                'tax_type' =>
                    'IGIC',

                'tax_rate' =>
                    number_format(
                        $taxRate,
                        4,
                        '.',
                        ''
                    ),

                'prices_include_tax' =>
                    isset(
                        $_POST[
                            'prices_include_tax'
                        ]
                    )
                        ? 1
                        : 0,

                'logo_attachment_id' =>
                    isset(
                        $_POST[
                            'logo_attachment_id'
                        ]
                    )
                        ? absint(
                            wp_unslash(
                                (string) $_POST[
                                    'logo_attachment_id'
                                ]
                            )
                        )
                        : 0,

                'footer_text' =>
                    isset($_POST['footer_text'])
                        ? sanitize_textarea_field(
                            wp_unslash(
                                (string) $_POST[
                                    'footer_text'
                                ]
                            )
                        )
                        : '',
            ];

            $environment =
                self::postedKey(
                    'verifactu_environment',
                    VerifactuSettings
                        ::ENVIRONMENT_TEST
                );

            if (
                !in_array(
                    $environment,
                    [
                        VerifactuSettings
                            ::ENVIRONMENT_TEST,

                        VerifactuSettings
                            ::ENVIRONMENT_PRODUCTION,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El entorno VERI*FACTU no es válido.'
                );
            }

            $certificateType =
                self::postedKey(
                    'verifactu_certificate_type',
                    VerifactuSettings
                        ::CERTIFICATE_STANDARD
                );

            if (
                !in_array(
                    $certificateType,
                    [
                        VerifactuSettings
                            ::CERTIFICATE_STANDARD,

                        VerifactuSettings
                            ::CERTIFICATE_SEAL,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El tipo de certificado VERI*FACTU no es válido.'
                );
            }

            $currentVerifactu =
                VerifactuSettings::get();

            $installationId =
                trim(
                    (string) (
                        $currentVerifactu[
                            'installation_id'
                        ]
                        ?? ''
                    )
                );

            if ($installationId === '') {
                $installationId =
                    wp_generate_uuid4();
            }

            $verifactuData = [
                'enabled' =>
                    isset(
                        $_POST[
                            'verifactu_enabled'
                        ]
                    )
                        ? 1
                        : 0,

                'environment' =>
                    $environment,

                'certificate_type' =>
                    $certificateType,

                'installation_id' =>
                    $installationId,

                'system_name' =>
                    'DeSegundaMuda',

                'system_id' =>
                    'DSMFACT',

                'system_version' =>
                    DSM_FACTURACION_VERSION,
            ];

            BillingSettings::save(
                $billingData
            );

            VerifactuSettings::save(
                $verifactuData
            );

            $this->redirect(
                [
                    'notice' =>
                        'saved',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    private static function postedText(
        string $field
    ): string {
        if (!isset($_POST[$field])) {
            return '';
        }

        return trim(
            sanitize_text_field(
                wp_unslash(
                    (string) $_POST[
                        $field
                    ]
                )
            )
        );
    }

    private static function postedKey(
        string $field,
        string $default
    ): string {
        if (!isset($_POST[$field])) {
            return $default;
        }

        return sanitize_key(
            wp_unslash(
                (string) $_POST[
                    $field
                ]
            )
        );
    }

    private function assertPermission(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para gestionar la facturación.',
                    'dsm-facturacion'
                )
            );
        }
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private function redirect(
        array $arguments = []
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url(
                    'admin.php?page='
                    . self::MENU_SLUG
                )
            )
        );

        exit;
    }

    public static function getSaveAction(): string
    {
        return self::SAVE_ACTION;
    }

    public static function getNonceAction(): string
    {
        return self::NONCE_ACTION;
    }

    public static function getNonceName(): string
    {
        return self::NONCE_NAME;
    }

    private function __construct()
    {
    }
}
