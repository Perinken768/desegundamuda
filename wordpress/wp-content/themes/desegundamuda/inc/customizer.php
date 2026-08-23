<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action(
    'customize_register',
    static function (
        WP_Customize_Manager $customizer
    ): void {
        /*
         * =====================================================
         * SECCIÓN DESEGUNDAMUDA
         * =====================================================
         */

        $customizer->add_section(
            'dsm_theme_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Configuración visual general de DeSegundaMuda.',
                        'desegundamuda'
                    ),

                'priority' =>
                    30,
            ]
        );


        /*
         * =====================================================
         * MOSTRAR TÍTULO
         * =====================================================
         */

        $customizer->add_setting(
            'dsm_show_site_title',
            [
                'default' =>
                    true,

                'sanitize_callback' =>
                    static function (
                        mixed $value
                    ): bool {
                        return (bool) $value;
                    },
            ]
        );

        $customizer->add_control(
            'dsm_show_site_title',
            [
                'section' =>
                    'dsm_theme_options',

                'label' =>
                    __(
                        'Mostrar título junto al logo',
                        'desegundamuda'
                    ),

                'type' =>
                    'checkbox',
            ]
        );


        /*
         * =====================================================
         * TEXTO DE ACCESO
         * =====================================================
         */

        $customizer->add_setting(
            'dsm_account_label',
            [
                'default' =>
                    'Mi cuenta',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_account_label',
            [
                'section' =>
                    'dsm_theme_options',

                'label' =>
                    __(
                        'Texto del acceso a la cuenta',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );


        /*
         * =====================================================
         * PLACEHOLDER DEL BUSCADOR
         * =====================================================
         */

        $customizer->add_setting(
            'dsm_search_placeholder',
            [
                'default' =>
                    'Buscar ropa, marcas, productos...',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_search_placeholder',
            [
                'section' =>
                    'dsm_theme_options',

                'label' =>
                    __(
                        'Texto del buscador',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );
    }
);


/*
 * ==========================================================
 * PORTADA
 * ==========================================================
 */

add_action(
    'customize_register',
    static function (
        WP_Customize_Manager $customizer
    ): void {
        $customizer->add_section(
            'dsm_home_options',
            [
                'title' =>
                    __(
                        'Portada DeSegundaMuda',
                        'desegundamuda'
                    ),

                'priority' =>
                    31,
            ]
        );

        $toggles = [
            'dsm_home_show_search' => [
                'Mostrar buscador',
                true,
            ],

            'dsm_home_show_categories' => [
                'Mostrar categorías',
                true,
            ],

            'dsm_home_show_filters' => [
                'Mostrar filtros',
                true,
            ],

            'dsm_home_show_advertising' => [
                'Mostrar zona publicitaria',
                true,
            ],
        ];

        foreach (
            $toggles
            as $settingId => $configuration
        ) {
            [
                $label,
                $default,
            ] = $configuration;

            $customizer->add_setting(
                $settingId,
                [
                    'default' =>
                        $default,

                    'sanitize_callback' =>
                        static fn (
                            mixed $value
                        ): bool =>
                            (bool) $value,
                ]
            );

            $customizer->add_control(
                $settingId,
                [
                    'section' =>
                        'dsm_home_options',

                    'label' =>
                        __(
                            $label,
                            'desegundamuda'
                        ),

                    'type' =>
                        'checkbox',
                ]
            );
        }

        $customizer->add_setting(
            'dsm_home_results_title',
            [
                'default' =>
                    'Descubre lo último',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_home_results_title',
            [
                'section' =>
                    'dsm_home_options',

                'label' =>
                    __(
                        'Título de resultados',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );
    }
);
