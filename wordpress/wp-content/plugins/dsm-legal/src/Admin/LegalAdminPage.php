<?php

declare(strict_types=1);

namespace DSM\Legal\Admin;

use DSM\Legal\Document\LegalDocumentRegistry;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalAdminPage
{
    public const PAGE_SLUG =
        'dsm-legal';

    public static function register(): void
    {
        add_action(
            'admin_menu',
            [
                self::class,
                'registerMenu',
            ]
        );

        add_action(
            'admin_post_dsm_legal_save',
            [
                self::class,
                'handleSave',
            ]
        );
    }

    public static function registerMenu(): void
    {
        add_menu_page(
            __(
                'DSM Legal',
                'dsm-legal'
            ),
            __(
                'DSM Legal',
                'dsm-legal'
            ),
            'manage_options',
            self::PAGE_SLUG,
            [
                self::class,
                'render',
            ],
            'dashicons-privacy',
            58
        );
    }

    public static function render(): void
    {
        if (
            !current_user_can(
                'manage_options'
            )
        ) {
            wp_die(
                esc_html__(
                    'No tienes permisos para acceder a esta página.',
                    'dsm-legal'
                )
            );
        }

        $registry =
            new LegalDocumentRegistry();

        $documents =
            $registry->all();

        $activeKey =
            isset(
                $_GET[
                    'document'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'document'
                        ]
                    )
                )
                : '';

        if (
            $activeKey === ''
            || !isset(
                $documents[
                    $activeKey
                ]
            )
        ) {
            $activeKey =
                array_key_first(
                    $documents
                )
                ?? '';
        }

        $activeDocument =
            $documents[
                $activeKey
            ]
                ?? null;

        ?>
        <div class="wrap">

            <h1>
                <?php
                esc_html_e(
                    'DSM Legal',
                    'dsm-legal'
                );
                ?>
            </h1>

            <p>
                <?php
                esc_html_e(
                    'Gestiona los documentos legales públicos de DeSegundaMuda.',
                    'dsm-legal'
                );
                ?>
            </p>

            <?php if (
                isset(
                    $_GET[
                        'updated'
                    ]
                )
                && sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'updated'
                        ]
                    )
                ) === '1'
            ) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        esc_html_e(
                            'Documento legal actualizado correctamente.',
                            'dsm-legal'
                        );
                        ?>
                    </p>
                </div>

            <?php endif; ?>

            <h2 class="nav-tab-wrapper">

                <?php foreach (
                    $documents
                    as $key => $document
                ) : ?>

                    <?php
                    $url =
                        add_query_arg(
                            [
                                'page' =>
                                    self::PAGE_SLUG,

                                'document' =>
                                    $key,
                            ],
                            admin_url(
                                'admin.php'
                            )
                        );
                    ?>

                    <a
                        class="<?php
                        echo esc_attr(
                            'nav-tab'
                            . (
                                $key
                                === $activeKey
                                    ? ' nav-tab-active'
                                    : ''
                            )
                        );
                        ?>"
                        href="<?php
                        echo esc_url(
                            $url
                        );
                        ?>"
                    >
                        <?php
                        echo esc_html(
                            $document->getTitle()
                        );
                        ?>
                    </a>

                <?php endforeach; ?>

            </h2>

            <?php if (
                $activeDocument !== null
            ) : ?>

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
                        value="dsm_legal_save"
                    >

                    <input
                        type="hidden"
                        name="document_key"
                        value="<?php
                        echo esc_attr(
                            $activeKey
                        );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_legal_save_'
                        . $activeKey,
                        'dsm_legal_nonce'
                    );
                    ?>

                    <table class="form-table">
                        <tbody>

                            <tr>
                                <th scope="row">
                                    <label
                                        for="dsm-legal-title"
                                    >
                                        <?php
                                        esc_html_e(
                                            'Título',
                                            'dsm-legal'
                                        );
                                        ?>
                                    </label>
                                </th>

                                <td>
                                    <input
                                        id="dsm-legal-title"
                                        class="regular-text"
                                        type="text"
                                        name="title"
                                        value="<?php
                                        echo esc_attr(
                                            $activeDocument->getTitle()
                                        );
                                        ?>"
                                        required
                                    >

                                    <p class="description">
                                        <?php
                                        esc_html_e(
                                            'Título público del documento.',
                                            'dsm-legal'
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php
                                    esc_html_e(
                                        'URL pública',
                                        'dsm-legal'
                                    );
                                    ?>
                                </th>

                                <td>
                                    <code>
                                        <?php
                                        echo esc_html(
                                            home_url(
                                                '/legal/'
                                                . $activeDocument->getSlug()
                                                . '/'
                                            )
                                        );
                                        ?>
                                    </code>
                                </td>
                            </tr>

                        </tbody>
                    </table>

                    <h2>
                        <?php
                        esc_html_e(
                            'Contenido',
                            'dsm-legal'
                        );
                        ?>
                    </h2>

                    <?php
                    wp_editor(
                        $activeDocument->getContent(),
                        'dsm_legal_content',
                        [
                            'textarea_name' =>
                                'content',

                            'textarea_rows' =>
                                24,

                            'media_buttons' =>
                                false,

                            'teeny' =>
                                false,

                            'quicktags' =>
                                true,
                        ]
                    );
                    ?>

                    <?php
                    submit_button(
                        __(
                            'Guardar documento',
                            'dsm-legal'
                        )
                    );
                    ?>

                </form>

            <?php endif; ?>

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

        $key =
            isset(
                $_POST[
                    'document_key'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[
                            'document_key'
                        ]
                    )
                )
                : '';

        $registry =
            new LegalDocumentRegistry();

        $document =
            $registry->findByKey(
                $key
            );

        if ($document === null) {
            wp_die(
                esc_html__(
                    'El documento legal solicitado no existe.',
                    'dsm-legal'
                )
            );
        }

        check_admin_referer(
            'dsm_legal_save_'
            . $key,
            'dsm_legal_nonce'
        );

        $title =
            isset(
                $_POST[
                    'title'
                ]
            )
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'title'
                        ]
                    )
                )
                : '';

        $content =
            isset(
                $_POST[
                    'content'
                ]
            )
                ? wp_kses_post(
                    wp_unslash(
                        (string) $_POST[
                            'content'
                        ]
                    )
                )
                : '';

        if ($title === '') {
            $title =
                $document->getTitle();
        }

        update_option(
            self::getTitleOptionName(
                $key
            ),
            $title,
            false
        );

        update_option(
            self::getContentOptionName(
                $key
            ),
            $content,
            false
        );

        /*
         * Cada modificación publicada genera una nueva
         * versión del documento legal.
         *
         * 1.0 -> 1.1 -> 1.2 -> ...
         */
        $currentVersion =
            $document->getVersion();

        if (
            preg_match(
                '/^(\\d+)\\.(\\d+)$/',
                $currentVersion,
                $matches
            )
        ) {
            $major =
                (int) $matches[1];

            $minor =
                (int) $matches[2];

            $newVersion =
                $major
                . '.'
                . ($minor + 1);
        } else {
            $newVersion =
                '1.1';
        }

        update_option(
            self::getVersionOptionName(
                $key
            ),
            $newVersion,
            false
        );

        update_option(
            self::getUpdatedAtOptionName(
                $key
            ),
            current_time(
                'mysql'
            ),
            false
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        self::PAGE_SLUG,

                    'document' =>
                        $key,

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

    public static function getTitleOptionName(
        string $key
    ): string {
        return 'dsm_legal_'
            . sanitize_key(
                $key
            )
            . '_title';
    }

    public static function getContentOptionName(
        string $key
    ): string {
        return 'dsm_legal_'
            . sanitize_key(
                $key
            )
            . '_content';
    }

    public static function getVersionOptionName(
        string $key
    ): string {
        return 'dsm_legal_'
            . sanitize_key(
                $key
            )
            . '_version';
    }

    public static function getUpdatedAtOptionName(
        string $key
    ): string {
        return 'dsm_legal_'
            . sanitize_key(
                $key
            )
            . '_updated_at';
    }

    private function __construct()
    {
    }
}
