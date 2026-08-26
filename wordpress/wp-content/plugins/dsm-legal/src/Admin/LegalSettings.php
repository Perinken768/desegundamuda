<?php

declare(strict_types=1);

namespace DSM\Legal\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalSettings
{
    public const PAGE_SLUG =
        'dsm-legal-settings';

    private const OPTION_PREFIX =
        'dsm_legal_settings_';

    public static function register(): void
    {
        add_action(
            'admin_menu',
            [self::class, 'registerMenu']
        );

        add_action(
            'admin_post_dsm_legal_settings_save',
            [self::class, 'handleSave']
        );
    }

    public static function registerMenu(): void
    {
        add_submenu_page(
            'dsm-legal',
            __(
                'Configuración legal',
                'dsm-legal'
            ),
            __(
                'Configuración',
                'dsm-legal'
            ),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (
            !current_user_can(
                'manage_options'
            )
        ) {
            return;
        }

        $saved =
            isset($_GET['updated'])
            && sanitize_key(
                wp_unslash(
                    (string) $_GET['updated']
                )
            ) === '1';

        ?>
        <div class="wrap">

            <h1>
                <?php
                echo esc_html__(
                    'Configuración legal',
                    'dsm-legal'
                );
                ?>
            </h1>

            <p>
                <?php
                echo esc_html__(
                    'Datos generales del titular de DeSegundaMuda utilizados por los documentos legales.',
                    'dsm-legal'
                );
                ?>
            </p>

            <?php if ($saved) : ?>

                <div
                    class="notice notice-success is-dismissible"
                >
                    <p>
                        <?php
                        echo esc_html__(
                            'Configuración legal guardada correctamente.',
                            'dsm-legal'
                        );
                        ?>
                    </p>
                </div>

            <?php endif; ?>

            <form
                method="post"
                action="<?php
                echo esc_url(
                    admin_url(
                        'admin-post.php'
                    )
                );
                ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="dsm_legal_settings_save"
                >

                <?php
                wp_nonce_field(
                    'dsm_legal_settings_save',
                    'dsm_legal_settings_nonce'
                );
                ?>

                <table class="form-table" role="presentation">

                    <?php
                    self::renderTextField(
                        'owner_name',
                        'Titular / Razón social'
                    );

                    self::renderTextField(
                        'tax_id',
                        'NIF / CIF'
                    );

                    self::renderTextField(
                        'trade_name',
                        'Nombre comercial'
                    );

                    self::renderTextField(
                        'registered_address',
                        'Domicilio'
                    );

                    self::renderEmailField(
                        'contact_email',
                        'Correo electrónico de contacto'
                    );

                    self::renderTextField(
                        'registry_data',
                        'Datos registrales'
                    );
                    ?>

                </table>

                <?php
                submit_button(
                    __(
                        'Guardar configuración',
                        'dsm-legal'
                    )
                );
                ?>

            </form>

        </div>
        <?php
    }

    public static function handleSave(): never
    {
        if (
            !current_user_can(
                'manage_options'
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-legal'
                )
            );
        }

        check_admin_referer(
            'dsm_legal_settings_save',
            'dsm_legal_settings_nonce'
        );

        $textFields = [
            'owner_name',
            'tax_id',
            'trade_name',
            'registered_address',
            'registry_data',
        ];

        foreach ($textFields as $field) {
            $value =
                isset($_POST[$field])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[$field]
                        )
                    )
                    : '';

            update_option(
                self::getOptionName(
                    $field
                ),
                $value,
                false
            );
        }

        $contactEmail =
            isset($_POST['contact_email'])
                ? sanitize_email(
                    wp_unslash(
                        (string) $_POST[
                            'contact_email'
                        ]
                    )
                )
                : '';

        update_option(
            self::getOptionName(
                'contact_email'
            ),
            $contactEmail,
            false
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'updated' =>
                        '1',
                ],
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }

    public static function get(
        string $key
    ): string {
        return (string) get_option(
            self::getOptionName(
                $key
            ),
            ''
        );
    }

    private static function renderTextField(
        string $key,
        string $label
    ): void {
        self::renderInput(
            $key,
            $label,
            'text'
        );
    }

    private static function renderEmailField(
        string $key,
        string $label
    ): void {
        self::renderInput(
            $key,
            $label,
            'email'
        );
    }

    private static function renderInput(
        string $key,
        string $label,
        string $type
    ): void {
        ?>
        <tr>
            <th scope="row">
                <label
                    for="<?php
                    echo esc_attr(
                        'dsm-legal-' . $key
                    );
                    ?>"
                >
                    <?php
                    echo esc_html(
                        $label
                    );
                    ?>
                </label>
            </th>

            <td>
                <input
                    id="<?php
                    echo esc_attr(
                        'dsm-legal-' . $key
                    );
                    ?>"
                    name="<?php
                    echo esc_attr(
                        $key
                    );
                    ?>"
                    type="<?php
                    echo esc_attr(
                        $type
                    );
                    ?>"
                    class="regular-text"
                    value="<?php
                    echo esc_attr(
                        self::get(
                            $key
                        )
                    );
                    ?>"
                >
            </td>
        </tr>
        <?php
    }

    private static function getOptionName(
        string $key
    ): string {
        return self::OPTION_PREFIX
            . sanitize_key(
                $key
            );
    }

    private function __construct()
    {
    }
}
