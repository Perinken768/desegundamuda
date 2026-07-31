<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action(
    'after_setup_theme',
    static function (): void {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');

        add_theme_support(
            'html5',
            [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            ]
        );
    }
);

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        $theme = wp_get_theme();
        $version = (string) $theme->get('Version');

        wp_enqueue_style(
            'dsm-tokens',
            get_template_directory_uri()
                . '/assets/css/tokens.css',
            [],
            $version
        );

        wp_enqueue_style(
            'dsm-base',
            get_template_directory_uri()
                . '/assets/css/base.css',
            ['dsm-tokens'],
            $version
        );

        wp_enqueue_style(
            'dsm-layout',
            get_template_directory_uri()
                . '/assets/css/layout.css',
            ['dsm-base'],
            $version
        );

        wp_enqueue_style(
            'dsm-components',
            get_template_directory_uri()
                . '/assets/css/components.css',
            ['dsm-layout'],
            $version
        );
    }
);