<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>

<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<header class="dsm-site-header">

    <div class="dsm-container">

        <div class="dsm-header-main">

            <div class="dsm-header-brand">

                <a
                    class="dsm-site-brand"
                    href="<?php
                    echo esc_url(
                        home_url('/')
                    );
                    ?>"
                    rel="home"
                >

                    <?php if (has_custom_logo()) : ?>

                        <?php
                        $logoId =
                            (int) get_theme_mod(
                                'custom_logo'
                            );

                        echo wp_get_attachment_image(
                            $logoId,
                            'full',
                            false,
                            [
                                'class' =>
                                    'dsm-site-logo',
                            ]
                        );
                        ?>

                    <?php endif; ?>

                    <?php if (
                        dsm_theme_show_site_title()
                    ) : ?>

                        <span class="dsm-site-title">
                            <?php
                            bloginfo(
                                'name'
                            );
                            ?>
                        </span>

                    <?php endif; ?>

                </a>

            </div>

            <button
                class="dsm-navigation-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="dsm-primary-navigation"
            >
                <span class="screen-reader-text">
                    <?php
                    esc_html_e(
                        'Abrir navegación',
                        'desegundamuda'
                    );
                    ?>
                </span>

                <span aria-hidden="true">
                    ☰
                </span>
            </button>

            <nav
                id="dsm-primary-navigation"
                class="dsm-primary-navigation"
                aria-label="<?php
                esc_attr_e(
                    'Navegación principal',
                    'desegundamuda'
                );
                ?>"
            >

                <?php
                wp_nav_menu(
                    [
                        'theme_location' =>
                            'primary',

                        'container' =>
                            false,

                        'menu_class' =>
                            'dsm-primary-menu',

                        'fallback_cb' =>
                            false,
                    ]
                );
                ?>

            </nav>

            <div class="dsm-header-actions">

                <a
                    class="dsm-header-action"
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/favoritos/'
                        )
                    );
                    ?>"
                >
                    Favoritos
                </a>

                <a
                    class="
                        dsm-button
                        dsm-button--primary
                    "
                    href="<?php
                    echo esc_url(
                        home_url(
                            '/mi-cuenta/'
                        )
                    );
                    ?>"
                >
                    <?php
                    echo esc_html(
                        dsm_theme_account_label()
                    );
                    ?>
                </a>

            </div>

        </div>

    </div>

</header>
