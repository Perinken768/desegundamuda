<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}


/*
 * ==========================================================
 * PERSONALIZADOR DESEGUNDAMUDA
 * ==========================================================
 */

add_action(
    'customize_register',
    static function (
        WP_Customize_Manager $customizer
    ): void {

        if (
            !class_exists(
                'DSM_Customize_Heading_Control'
            )
        ) {
            final class DSM_Customize_Heading_Control
                extends WP_Customize_Control
            {
                public $type =
                    'dsm_heading';

                public function render_content(): void
                {
                    if (
                        trim(
                            (string) $this->label
                        ) === ''
                    ) {
                        return;
                    }
                    ?>

                    <div class="dsm-customizer-heading">

                        <span class="customize-control-title">
                            <?php
                            echo esc_html(
                                $this->label
                            );
                            ?>
                        </span>

                        <?php if (
                            trim(
                                (string) $this->description
                            ) !== ''
                        ) : ?>

                            <span
                                class="
                                    description
                                    customize-control-description
                                "
                            >
                                <?php
                                echo esc_html(
                                    $this->description
                                );
                                ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <?php
                }
            }
        }


        /*
         * ==================================================
         * CABECERA
         * ==================================================
         */

        $customizer->add_section(
            'dsm_header_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda - Cabecera',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Configuración visual de la cabecera principal.',
                        'desegundamuda'
                    ),

                'priority' =>
                    30,
            ]
        );


        /*
         * Mostrar título junto al logo.
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
                    'dsm_header_options',

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
         * Texto del acceso a Mi cuenta.
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
                    'dsm_header_options',

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
         * ==================================================
         * COLORES
         * ==================================================
         */

        $customizer->add_section(
            'dsm_colors_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda - Colores',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Personaliza los colores principales del tema.',
                        'desegundamuda'
                    ),

                'priority' =>
                    31,
            ]
        );

        $colorOptions = [
            'dsm_color_primary' => [
                'Color principal',
                '#24352a',
            ],

            'dsm_color_primary_hover' => [
                'Color principal al pasar el ratón',
                '#17231b',
            ],

            'dsm_color_secondary' => [
                'Color secundario',
                '#dce8df',
            ],

            'dsm_color_accent' => [
                'Color de acento',
                '#b8793d',
            ],

            'dsm_color_background' => [
                'Fondo general',
                '#f5f7f5',
            ],

            'dsm_color_surface' => [
                'Fondo de tarjetas',
                '#ffffff',
            ],

            'dsm_color_surface_muted' => [
                'Fondo secundario',
                '#eef2ef',
            ],

            'dsm_color_text' => [
                'Texto principal',
                '#1e2520',
            ],

            'dsm_color_text_muted' => [
                'Texto secundario',
                '#667069',
            ],

            'dsm_color_border' => [
                'Bordes',
                '#d9dfda',
            ],

            'dsm_color_focus' => [
                'Color de foco',
                '#4f7d5b',
            ],
        ];

        foreach (
            $colorOptions
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
                        'sanitize_hex_color',
                ]
            );

            $customizer->add_control(
                new WP_Customize_Color_Control(
                    $customizer,
                    $settingId,
                    [
                        'section' =>
                            'dsm_colors_options',

                        'label' =>
                            __(
                                $label,
                                'desegundamuda'
                            ),
                    ]
                )
            );
        }


        /*
         * ==================================================
         * PORTADA
         * ==================================================
         */

        $customizer->add_section(
            'dsm_home_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda - Portada',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Configuración de los elementos principales de la portada.',
                        'desegundamuda'
                    ),

                'priority' =>
                    31,
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_home_heading_content',
                [
                    'section' =>
                        'dsm_home_options',

                    'label' =>
                        __(
                            'Contenido',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Textos principales mostrados en la portada.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        10,
                ]
            )
        );


        /*
         * Texto del buscador.
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
                    'dsm_home_options',

                'priority' =>
                    11,

                'label' =>
                    __(
                        'Texto del buscador',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );


        /*
         * Título de categorías.
         */

        $customizer->add_setting(
            'dsm_home_categories_title',
            [
                'default' =>
                    'Categorías',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_home_categories_title',
            [
                'section' =>
                    'dsm_home_options',

                'priority' =>
                    12,

                'label' =>
                    __(
                        'Título de categorías',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );


        /*
         * Título de filtros.
         */

        $customizer->add_setting(
            'dsm_home_filters_title',
            [
                'default' =>
                    'Filtrar resultados',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_home_filters_title',
            [
                'section' =>
                    'dsm_home_options',

                'priority' =>
                    13,

                'label' =>
                    __(
                        'Título de filtros',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_home_heading_visibility',
                [
                    'section' =>
                        'dsm_home_options',

                    'label' =>
                        __(
                            'Visibilidad',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Activa o desactiva las secciones opcionales de la portada.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        20,
                ]
            )
        );


        /*
         * Elementos visibles de la portada.
         */

        $homeToggles = [
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

        $homeTogglePriority = 21;

        foreach (
            $homeToggles
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
                        static function (
                            mixed $value
                        ): bool {
                            return (bool) $value;
                        },
                ]
            );

            $customizer->add_control(
                $settingId,
                [
                    'section' =>
                        'dsm_home_options',

                    'priority' =>
                        $homeTogglePriority,

                    'label' =>
                        __(
                            $label,
                            'desegundamuda'
                        ),

                    'type' =>
                        'checkbox',
                ]
            );

            $homeTogglePriority++;
        }


        /*
         * Título de resultados.
         */

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

                'priority' =>
                    14,

                'label' =>
                    __(
                        'Título de resultados',
                        'desegundamuda'
                    ),

                'type' =>
                    'text',
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_home_heading_design',
                [
                    'section' =>
                        'dsm_home_options',

                    'label' =>
                        __(
                            'Diseño',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Distribución visual de los resultados.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        30,
                ]
            )
        );


        /*
         * Columnas de resultados en escritorio.
         */

        $customizer->add_setting(
            'dsm_home_listing_columns',
            [
                'default' =>
                    '4',

                'sanitize_callback' =>
                    static function (
                        mixed $value
                    ): string {
                        $value =
                            (string) $value;

                        return in_array(
                            $value,
                            [
                                '3',
                                '4',
                                '5',
                            ],
                            true
                        )
                            ? $value
                            : '4';
                    },
            ]
        );

        $customizer->add_control(
            'dsm_home_listing_columns',
            [
                'section' =>
                    'dsm_home_options',

                'priority' =>
                    31,

                'label' =>
                    __(
                        'Columnas de resultados',
                        'desegundamuda'
                    ),

                'type' =>
                    'select',

                'choices' => [
                    '3' =>
                        __(
                            '3 columnas',
                            'desegundamuda'
                        ),

                    '4' =>
                        __(
                            '4 columnas',
                            'desegundamuda'
                        ),

                    '5' =>
                        __(
                            '5 columnas',
                            'desegundamuda'
                        ),
                ],
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_home_heading_order',
                [
                    'section' =>
                        'dsm_home_options',

                    'label' =>
                        __(
                            'Orden de secciones',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Define el orden en el que aparecen las secciones de la portada.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        40,
                ]
            )
        );


        /*
         * Orden visual de la portada.
         */

        $homeSections = [
            'search' =>
                __(
                    'Buscador',
                    'desegundamuda'
                ),

            'categories' =>
                __(
                    'Categorías',
                    'desegundamuda'
                ),

            'filters' =>
                __(
                    'Filtros',
                    'desegundamuda'
                ),

            'advertising' =>
                __(
                    'Publicidad',
                    'desegundamuda'
                ),

            'listings' =>
                __(
                    'Resultados',
                    'desegundamuda'
                ),
        ];

        $homeSectionDefaults = [
            1 => 'search',
            2 => 'categories',
            3 => 'filters',
            4 => 'advertising',
            5 => 'listings',
        ];

        foreach (
            $homeSectionDefaults
            as $position => $defaultSection
        ) {
            $settingId =
                'dsm_home_section_position_'
                . $position;

            $customizer->add_setting(
                $settingId,
                [
                    'default' =>
                        $defaultSection,

                    'sanitize_callback' =>
                        static function (
                            mixed $value
                        ) use (
                            $homeSections,
                            $defaultSection
                        ): string {
                            $value =
                                sanitize_key(
                                    (string) $value
                                );

                            return array_key_exists(
                                $value,
                                $homeSections
                            )
                                ? $value
                                : $defaultSection;
                        },
                ]
            );

            $customizer->add_control(
                $settingId,
                [
                    'section' =>
                        'dsm_home_options',

                    'priority' =>
                        40 + $position,

                    'label' =>
                        sprintf(
                            __(
                                'Posición %d',
                                'desegundamuda'
                            ),
                            $position
                        ),

                    'type' =>
                        'select',

                    'choices' =>
                        $homeSections,
                ]
            );
        }


        /*
         * ==================================================
         * MI CUENTA
         * ==================================================
         */

        $customizer->add_section(
            'dsm_account_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda - Mi cuenta',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Configuración visual del panel privado del cliente.',
                        'desegundamuda'
                    ),

                'priority' =>
                    32,
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_account_heading_content',
                [
                    'section' =>
                        'dsm_account_options',

                    'label' =>
                        __(
                            'Contenido',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Textos y elementos principales del panel.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        10,
                ]
            )
        );


        /*
         * Título del panel.
         */

        $customizer->add_setting(
            'dsm_account_title',
            [
                'default' =>
                    'Mi cuenta',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_account_title',
            [
                'section' =>
                    'dsm_account_options',

                'label' =>
                    __(
                        'Título del panel',
                        'desegundamuda'
                    ),

                'priority' =>
                    11,

                'type' =>
                    'text',
            ]
        );


        /*
         * Texto introductorio.
         */

        $customizer->add_setting(
            'dsm_account_description',
            [
                'default' =>
                    'Gestiona tu perfil y tu actividad en DeSegundaMuda.',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_account_description',
            [
                'section' =>
                    'dsm_account_options',

                'label' =>
                    __(
                        'Texto introductorio',
                        'desegundamuda'
                    ),

                'priority' =>
                    12,

                'type' =>
                    'text',
            ]
        );


        /*
         * Mostrar resumen de perfil.
         */

        $customizer->add_setting(
            'dsm_account_show_profile_summary',
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
            'dsm_account_show_profile_summary',
            [
                'section' =>
                    'dsm_account_options',

                'label' =>
                    __(
                        'Mostrar resumen de perfil',
                        'desegundamuda'
                    ),

                'priority' =>
                    13,

                'type' =>
                    'checkbox',
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_account_heading_design',
                [
                    'section' =>
                        'dsm_account_options',

                    'label' =>
                        __(
                            'Diseño',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Distribución visual de los módulos.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        20,
                ]
            )
        );


        /*
         * Columnas de módulos.
         */

        $customizer->add_setting(
            'dsm_account_modules_columns',
            [
                'default' =>
                    '2',

                'sanitize_callback' =>
                    static function (
                        mixed $value
                    ): string {
                        $value =
                            (string) $value;

                        return in_array(
                            $value,
                            [
                                '1',
                                '2',
                            ],
                            true
                        )
                            ? $value
                            : '2';
                    },
            ]
        );

        $customizer->add_control(
            'dsm_account_modules_columns',
            [
                'section' =>
                    'dsm_account_options',

                'label' =>
                    __(
                        'Columnas de módulos',
                        'desegundamuda'
                    ),

                'priority' =>
                    21,

                'type' =>
                    'select',

                'choices' => [
                    '1' =>
                        __(
                            'Una columna',
                            'desegundamuda'
                        ),

                    '2' =>
                        __(
                            'Dos columnas',
                            'desegundamuda'
                        ),
                ],
            ]
        );


        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_account_heading_modules',
                [
                    'section' =>
                        'dsm_account_options',

                    'label' =>
                        __(
                            'Orden de módulos',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Define el orden visual de los módulos del cliente.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        30,
                ]
            )
        );


        /*
         * Orden de módulos de Mi cuenta.
         */

        $accountModuleChoices = [
            '' =>
                __(
                    'Automático',
                    'desegundamuda'
                ),

            'promotions' =>
                __(
                    'Promociones',
                    'desegundamuda'
                ),

            'advertising' =>
                __(
                    'Mi publicidad',
                    'desegundamuda'
                ),

            'multistore' =>
                __(
                    'Mi tienda',
                    'desegundamuda'
                ),
        ];

        for (
            $position = 1;
            $position <= 3;
            $position++
        ) {
            $settingId =
                'dsm_account_module_position_'
                . $position;

            $customizer->add_setting(
                $settingId,
                [
                    'default' =>
                        '',

                    'sanitize_callback' =>
                        static function (
                            mixed $value
                        ) use (
                            $accountModuleChoices
                        ): string {
                            $value =
                                sanitize_key(
                                    (string) $value
                                );

                            return array_key_exists(
                                $value,
                                $accountModuleChoices
                            )
                                ? $value
                                : '';
                        },
                ]
            );

            $customizer->add_control(
                $settingId,
                [
                    'section' =>
                        'dsm_account_options',

                    'priority' =>
                        30 + $position,

                    'label' =>
                        sprintf(
                            __(
                                'Módulo en posición %d',
                                'desegundamuda'
                            ),
                            $position
                        ),

                    'type' =>
                        'select',

                    'choices' =>
                        $accountModuleChoices,
                ]
            );
        }


        /*
         * ==================================================
         * PIE DE PÁGINA
         * ==================================================
         *
         * Creamos ya la sección aunque todavía no tenga
         * controles. Así dejamos preparada la arquitectura
         * del tema para las siguientes opciones.
         */

        $customizer->add_section(
            'dsm_footer_options',
            [
                'title' =>
                    __(
                        'DeSegundaMuda - Pie de página',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Configuración del pie de página.',
                        'desegundamuda'
                    ),

                'priority' =>
                    33,
            ]
        );



        /*
         * ==================================================
         * CONTENIDO DEL PIE
         * ==================================================
         */

        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_footer_heading_content',
                [
                    'section' =>
                        'dsm_footer_options',

                    'label' =>
                        __(
                            'Contenido',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Información general mostrada en el pie de página.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        10,
                ]
            )
        );

        $customizer->add_setting(
            'dsm_footer_text',
            [
                'default' =>
                    '',

                'sanitize_callback' =>
                    'sanitize_text_field',
            ]
        );

        $customizer->add_control(
            'dsm_footer_text',
            [
                'section' =>
                    'dsm_footer_options',

                'label' =>
                    __(
                        'Texto adicional',
                        'desegundamuda'
                    ),

                'description' =>
                    __(
                        'Texto opcional que aparecerá junto a la información de copyright.',
                        'desegundamuda'
                    ),

                'priority' =>
                    11,

                'type' =>
                    'text',
            ]
        );


        /*
         * ==================================================
         * REDES SOCIALES
         * ==================================================
         */

        $customizer->add_control(
            new DSM_Customize_Heading_Control(
                $customizer,
                'dsm_footer_heading_social',
                [
                    'section' =>
                        'dsm_footer_options',

                    'label' =>
                        __(
                            'Redes sociales',
                            'desegundamuda'
                        ),

                    'description' =>
                        __(
                            'Solo se mostrarán las redes que tengan una URL configurada.',
                            'desegundamuda'
                        ),

                    'priority' =>
                        30,
                ]
            )
        );

        $socialNetworks = [
            'dsm_footer_instagram_url' =>
                'Instagram',

            'dsm_footer_facebook_url' =>
                'Facebook',

            'dsm_footer_tiktok_url' =>
                'TikTok',

            'dsm_footer_x_url' =>
                'X / Twitter',

            'dsm_footer_youtube_url' =>
                'YouTube',
        ];

        $socialPriority = 31;

        foreach (
            $socialNetworks
            as $settingId => $label
        ) {
            $customizer->add_setting(
                $settingId,
                [
                    'default' =>
                        '',

                    'sanitize_callback' =>
                        'esc_url_raw',
                ]
            );

            $customizer->add_control(
                $settingId,
                [
                    'section' =>
                        'dsm_footer_options',

                    'label' =>
                        __(
                            $label,
                            'desegundamuda'
                        ),

                    'priority' =>
                        $socialPriority,

                    'type' =>
                        'url',
                ]
            );

            $socialPriority++;
        }
    }
);


/*
 * ==========================================================
 * ESTILOS DEL PERSONALIZADOR
 * ==========================================================
 */

add_action(
    'customize_controls_print_styles',
    static function (): void {
        ?>
        <style>
            .dsm-customizer-heading {
                margin:
                    14px
                    -12px
                    6px;
                padding:
                    14px
                    12px
                    10px;

                border-top: 1px solid #dcdcde;
                background: #f6f7f7;
            }

            .dsm-customizer-heading
            .customize-control-title {
                margin-bottom: 3px;
            }

            .customize-control-dsm_heading {
                margin-bottom: 5px;
            }
        </style>
        <?php
    }
);
