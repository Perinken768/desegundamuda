<?php

declare(strict_types=1);

namespace DSM\Facturacion\Admin;

use DSM\Facturacion\Verifactu\VerifactuCertificateConfig;
use DSM\Facturacion\Verifactu\VerifactuHealthCheck;
use DSM\Facturacion\Verifactu\VerifactuSettings;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class VerifactuSettingsPage
{
    public const MENU_SLUG =
        'dsm-facturacion-verifactu';

    private const CAPABILITY =
        'manage_options';

    private const SAVE_ACTION =
        'dsm_facturacion_save_verifactu';

    private const NONCE_ACTION =
        'dsm_facturacion_save_verifactu';

    private const NONCE_NAME =
        'dsm_facturacion_verifactu_nonce';

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
            10
        );

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $page,
                'handleSave',
            ]
        );
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            parent_slug:
                BillingSettingsPage::MENU_SLUG,

            page_title:
                __(
                    'VERI*FACTU',
                    'dsm-facturacion'
                ),

            menu_title:
                __(
                    'VERI*FACTU',
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

    public function render(): void
    {
        $this->assertPermission();

        $settings =
            VerifactuSettings::get();

        $health =
            (new VerifactuHealthCheck())
                ->inspect();

        $certificate =
            null;

        if (
            VerifactuCertificateConfig::isConfigured()
        ) {
            try {
                $certificate =
                    VerifactuCertificateConfig
                        ::inspectCertificate();
            } catch (Throwable) {
                $certificate =
                    null;
            }
        }

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
            . 'verifactu-settings.php';

        if (!is_file($template)) {
            throw new RuntimeException(
                'No se encontró la plantilla administrativa de VERI*FACTU.'
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
            $current =
                VerifactuSettings::get();

            $enabled =
                isset(
                    $_POST[
                        'verifactu_enabled'
                    ]
                )
                    ? 1
                    : 0;

            $environment =
                isset(
                    $_POST[
                        'verifactu_environment'
                    ]
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST[
                                'verifactu_environment'
                            ]
                        )
                    )
                    : VerifactuSettings::ENVIRONMENT_TEST;

            if (
                !in_array(
                    $environment,
                    [
                        VerifactuSettings::ENVIRONMENT_TEST,
                        VerifactuSettings::ENVIRONMENT_PRODUCTION,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El entorno VERI*FACTU seleccionado no es válido.'
                );
            }

            $certificateType =
                isset(
                    $_POST[
                        'verifactu_certificate_type'
                    ]
                )
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_POST[
                                'verifactu_certificate_type'
                            ]
                        )
                    )
                    : VerifactuSettings::CERTIFICATE_STANDARD;

            if (
                !in_array(
                    $certificateType,
                    [
                        VerifactuSettings::CERTIFICATE_STANDARD,
                        VerifactuSettings::CERTIFICATE_SEAL,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'El tipo de certificado VERI*FACTU no es válido.'
                );
            }

            $systemName =
                isset(
                    $_POST[
                        'verifactu_system_name'
                    ]
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'verifactu_system_name'
                            ]
                        )
                    )
                    : 'DeSegundaMuda';

            $systemName =
                trim(
                    $systemName
                );

            if ($systemName === '') {
                throw new RuntimeException(
                    'El nombre del sistema VERI*FACTU no puede estar vacío.'
                );
            }

            $systemId =
                isset(
                    $_POST[
                        'verifactu_system_id'
                    ]
                )
                    ? strtoupper(
                        sanitize_text_field(
                            wp_unslash(
                                (string) $_POST[
                                    'verifactu_system_id'
                                ]
                            )
                        )
                    )
                    : VerifactuSettings::DEFAULT_SYSTEM_ID;

            $systemId =
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $systemId
                )
                ?? '';

            if (
                $systemId === ''
                || strlen(
                    $systemId
                ) > 2
            ) {
                throw new RuntimeException(
                    'El ID del sistema VERI*FACTU debe tener uno o dos caracteres alfanuméricos.'
                );
            }

            /*
             * =================================================
             * SEGURIDAD AL CAMBIAR ENDPOINT
             * =================================================
             *
             * Una submission congela su endpoint y SOAP.
             *
             * No permitimos cambiar entorno o tipo de
             * certificado mientras haya trabajo activo en cola.
             */
            $environmentChanged =
                $environment
                !== (
                    (string) (
                        $current[
                            'environment'
                        ]
                        ?? ''
                    )
                );

            $certificateTypeChanged =
                $certificateType
                !== (
                    (string) (
                        $current[
                            'certificate_type'
                        ]
                        ?? ''
                    )
                );

            if (
                $environmentChanged
                || $certificateTypeChanged
            ) {
                $health =
                    (new VerifactuHealthCheck())
                        ->inspect();

                $activeQueue =
                    (int) (
                        $health['queue']['pending']
                        ?? 0
                    )
                    + (int) (
                        $health['queue']['sending']
                        ?? 0
                    )
                    + (int) (
                        $health['queue']['retryable']
                        ?? 0
                    );

                if ($activeQueue > 0) {
                    throw new RuntimeException(
                        'No puede cambiar el entorno o el tipo de certificado mientras existan remisiones activas en la cola VERI*FACTU.'
                    );
                }
            }

            /*
             * =================================================
             * PRODUCCION
             * =================================================
             *
             * Cambiar a producción exige confirmación
             * explícita.
             */
            if (
                $environment
                === VerifactuSettings::ENVIRONMENT_PRODUCTION
            ) {
                $productionConfirmed =
                    isset(
                        $_POST[
                            'verifactu_confirm_production'
                        ]
                    )
                    && (
                        (string) $_POST[
                            'verifactu_confirm_production'
                        ]
                    ) === '1';

                if (!$productionConfirmed) {
                    throw new RuntimeException(
                        'Debe confirmar expresamente el uso del entorno de producción VERI*FACTU.'
                    );
                }
            }

            /*
             * =================================================
             * ACTIVACION DEL TRANSPORTE
             * =================================================
             *
             * Guardar configuración está permitido sin
             * certificado.
             *
             * Activar transmisión NO.
             */
            if ($enabled === 1) {
                $health =
                    (new VerifactuHealthCheck())
                        ->inspect();

                if (
                    !(
                        $health[
                            'ready_local'
                        ]
                        ?? false
                    )
                ) {
                    throw new RuntimeException(
                        'VERI*FACTU no puede activarse porque el diagnóstico local no está preparado.'
                    );
                }

                if (
                    !VerifactuCertificateConfig
                        ::isConfigured()
                ) {
                    throw new RuntimeException(
                        'VERI*FACTU no puede activarse hasta configurar un certificado válido en el servidor.'
                    );
                }
            }

            /*
             * Conservamos installation_id e incidencia.
             */
            $data =
                array_merge(
                    $current,
                    [
                        'enabled' =>
                            $enabled,

                        'environment' =>
                            $environment,

                        'certificate_type' =>
                            $certificateType,

                        'system_name' =>
                            $systemName,

                        'system_id' =>
                            $systemId,

                        'system_version' =>
                            defined(
                                'DSM_FACTURACION_VERSION'
                            )
                                ? DSM_FACTURACION_VERSION
                                : (
                                    (string) (
                                        $current[
                                            'system_version'
                                        ]
                                        ?? '0.1.0'
                                    )
                                ),
                    ]
                );

            VerifactuSettings::save(
                $data
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
                        $exception
                            ->getMessage(),
                ]
            );
        }
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

    private function assertPermission(): void
    {
        if (
            !current_user_can(
                self::CAPABILITY
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para administrar VERI*FACTU.',
                    'dsm-facturacion'
                )
            );
        }
    }

    /**
     * @param array<string, scalar> $args
     */
    private function redirect(
        array $args = []
    ): never {
        $url =
            add_query_arg(
                array_merge(
                    [
                        'page' =>
                            self::MENU_SLUG,
                    ],
                    $args
                ),
                admin_url(
                    'admin.php'
                )
            );

        wp_safe_redirect(
            $url
        );

        exit;
    }

    private function __construct()
    {
    }
}
