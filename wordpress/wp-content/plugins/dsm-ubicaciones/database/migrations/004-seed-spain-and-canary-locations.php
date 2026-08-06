<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Carga inicial de ubicaciones.
 *
 * Estructura:
 *
 * España
 * └── Canarias
 *     ├── El Hierro
 *     ├── Fuerteventura
 *     ├── Gran Canaria
 *     ├── La Gomera
 *     ├── La Graciosa
 *     ├── La Palma
 *     ├── Lanzarote
 *     └── Tenerife
 *
 * La Graciosa no dispone de municipio propio.
 * Administrativamente pertenece al municipio de Teguise,
 * situado en el área territorial de Lanzarote.
 */
return static function (): void {
    global $wpdb;

    $countriesTable =
        $wpdb->prefix
        . 'dsm_countries';

    $areasTable =
        $wpdb->prefix
        . 'dsm_location_areas';

    $municipalitiesTable =
        $wpdb->prefix
        . 'dsm_municipalities';

    $now =
        current_time(
            'mysql',
            true
        );

    /**
     * Verifica que las tres tablas necesarias existan.
     */
    $requiredTables = [
        $countriesTable,
        $areasTable,
        $municipalitiesTable,
    ];

    foreach ($requiredTables as $requiredTable) {
        $existingTable =
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $requiredTable
                )
            );

        if ($existingTable !== $requiredTable) {
            throw new RuntimeException(
                sprintf(
                    'No existe la tabla necesaria para cargar ubicaciones: %s',
                    $requiredTable
                )
            );
        }
    }

    /**
     * Crea o actualiza un país y devuelve su ID.
     */
    $upsertCountry =
        static function (
            string $name,
            string $slug,
            string $isoCode,
            ?string $phonePrefix,
            int $sortOrder
        ) use (
            $wpdb,
            $countriesTable,
            $now
        ): int {
            $countryId =
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$countriesTable}
                        WHERE iso_code = %s
                        LIMIT 1
                        ",
                        strtoupper(
                            trim($isoCode)
                        )
                    )
                );

            $data = [
                'name' =>
                    sanitize_text_field(
                        $name
                    ),

                'slug' =>
                    sanitize_title(
                        $slug
                    ),

                'iso_code' =>
                    strtoupper(
                        sanitize_text_field(
                            $isoCode
                        )
                    ),

                'phone_prefix' =>
                    $phonePrefix !== null
                        ? sanitize_text_field(
                            $phonePrefix
                        )
                        : null,

                'is_active' =>
                    1,

                'sort_order' =>
                    max(
                        0,
                        $sortOrder
                    ),

                'updated_at' =>
                    $now,
            ];

            if ($countryId > 0) {
                $result =
                    $wpdb->update(
                        $countriesTable,
                        $data,
                        [
                            'id' =>
                                $countryId,
                        ],
                        [
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%s',
                        ],
                        [
                            '%d',
                        ]
                    );

                if ($result === false) {
                    throw new RuntimeException(
                        sprintf(
                            'No se pudo actualizar el país %s.',
                            $name
                        )
                    );
                }

                return $countryId;
            }

            $data['created_at'] =
                $now;

            $result =
                $wpdb->insert(
                    $countriesTable,
                    $data,
                    [
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                    ]
                );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear el país %s.',
                        $name
                    )
                );
            }

            $countryId =
                (int) $wpdb->insert_id;

            if ($countryId <= 0) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo recuperar el ID del país %s.',
                        $name
                    )
                );
            }

            return $countryId;
        };

    /**
     * Crea o actualiza un área y devuelve su ID.
     */
    $upsertArea =
        static function (
            int $countryId,
            ?int $parentId,
            string $name,
            string $slug,
            string $areaType,
            ?string $code,
            int $sortOrder
        ) use (
            $wpdb,
            $areasTable,
            $now
        ): int {
            $normalizedSlug =
                sanitize_title(
                    $slug
                );

            $areaId =
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$areasTable}
                        WHERE country_id = %d
                          AND slug = %s
                        LIMIT 1
                        ",
                        $countryId,
                        $normalizedSlug
                    )
                );

            $data = [
                'country_id' =>
                    $countryId,

                'parent_id' =>
                    $parentId !== null
                    && $parentId > 0
                        ? $parentId
                        : null,

                'name' =>
                    sanitize_text_field(
                        $name
                    ),

                'slug' =>
                    $normalizedSlug,

                'area_type' =>
                    sanitize_key(
                        $areaType
                    ),

                'code' =>
                    $code !== null
                        ? sanitize_text_field(
                            $code
                        )
                        : null,

                'is_active' =>
                    1,

                'sort_order' =>
                    max(
                        0,
                        $sortOrder
                    ),

                'updated_at' =>
                    $now,
            ];

            if ($areaId > 0) {
                $result =
                    $wpdb->update(
                        $areasTable,
                        $data,
                        [
                            'id' =>
                                $areaId,
                        ],
                        [
                            '%d',
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%s',
                        ],
                        [
                            '%d',
                        ]
                    );

                if ($result === false) {
                    throw new RuntimeException(
                        sprintf(
                            'No se pudo actualizar el área %s.',
                            $name
                        )
                    );
                }

                return $areaId;
            }

            $data['created_at'] =
                $now;

            $result =
                $wpdb->insert(
                    $areasTable,
                    $data,
                    [
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                    ]
                );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear el área %s.',
                        $name
                    )
                );
            }

            $areaId =
                (int) $wpdb->insert_id;

            if ($areaId <= 0) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo recuperar el ID del área %s.',
                        $name
                    )
                );
            }

            return $areaId;
        };

    /**
     * Crea o actualiza un municipio.
     */
    $upsertMunicipality =
        static function (
            int $areaId,
            string $name,
            string $slug,
            int $sortOrder,
            ?string $code = null,
            ?string $postalCode = null
        ) use (
            $wpdb,
            $municipalitiesTable,
            $now
        ): void {
            $normalizedSlug =
                sanitize_title(
                    $slug
                );

            $municipalityId =
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT id
                        FROM {$municipalitiesTable}
                        WHERE area_id = %d
                          AND slug = %s
                        LIMIT 1
                        ",
                        $areaId,
                        $normalizedSlug
                    )
                );

            $data = [
                'area_id' =>
                    $areaId,

                'name' =>
                    sanitize_text_field(
                        $name
                    ),

                'slug' =>
                    $normalizedSlug,

                'code' =>
                    $code !== null
                        ? sanitize_text_field(
                            $code
                        )
                        : null,

                'postal_code' =>
                    $postalCode !== null
                        ? sanitize_text_field(
                            $postalCode
                        )
                        : null,

                'is_active' =>
                    1,

                'sort_order' =>
                    max(
                        0,
                        $sortOrder
                    ),

                'updated_at' =>
                    $now,
            ];

            if ($municipalityId > 0) {
                $result =
                    $wpdb->update(
                        $municipalitiesTable,
                        $data,
                        [
                            'id' =>
                                $municipalityId,
                        ],
                        [
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%s',
                        ],
                        [
                            '%d',
                        ]
                    );

                if ($result === false) {
                    throw new RuntimeException(
                        sprintf(
                            'No se pudo actualizar el municipio %s.',
                            $name
                        )
                    );
                }

                return;
            }

            $data['created_at'] =
                $now;

            $result =
                $wpdb->insert(
                    $municipalitiesTable,
                    $data,
                    [
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                    ]
                );

            if ($result === false) {
                throw new RuntimeException(
                    sprintf(
                        'No se pudo crear el municipio %s.',
                        $name
                    )
                );
            }
        };

    /*
     * La migración es idempotente:
     *
     * - si un registro no existe, se crea;
     * - si ya existe, se actualiza;
     * - puede ejecutarse nuevamente sin duplicar datos.
     */
    $wpdb->query(
        'START TRANSACTION'
    );

    try {
        $spainId =
            $upsertCountry(
                'España',
                'espana',
                'ES',
                '+34',
                10
            );

        $canaryRegionId =
            $upsertArea(
                $spainId,
                null,
                'Canarias',
                'canarias',
                'region',
                'CN',
                10
            );

        $islandDefinitions = [
            'el-hierro' => [
                'name' =>
                    'El Hierro',

                'code' =>
                    'HI',

                'sort_order' =>
                    10,
            ],

            'fuerteventura' => [
                'name' =>
                    'Fuerteventura',

                'code' =>
                    'FV',

                'sort_order' =>
                    20,
            ],

            'gran-canaria' => [
                'name' =>
                    'Gran Canaria',

                'code' =>
                    'GC',

                'sort_order' =>
                    30,
            ],

            'la-gomera' => [
                'name' =>
                    'La Gomera',

                'code' =>
                    'GO',

                'sort_order' =>
                    40,
            ],

            'la-graciosa' => [
                'name' =>
                    'La Graciosa',

                'code' =>
                    'LG',

                'sort_order' =>
                    50,
            ],

            'la-palma' => [
                'name' =>
                    'La Palma',

                'code' =>
                    'LP',

                'sort_order' =>
                    60,
            ],

            'lanzarote' => [
                'name' =>
                    'Lanzarote',

                'code' =>
                    'LZ',

                'sort_order' =>
                    70,
            ],

            'tenerife' => [
                'name' =>
                    'Tenerife',

                'code' =>
                    'TF',

                'sort_order' =>
                    80,
            ],
        ];

        $islandIds = [];

        foreach (
            $islandDefinitions
            as $islandSlug => $island
        ) {
            $islandIds[$islandSlug] =
                $upsertArea(
                    $spainId,
                    $canaryRegionId,
                    (string) $island['name'],
                    $islandSlug,
                    'island',
                    (string) $island['code'],
                    (int) $island['sort_order']
                );
        }

        /**
         * Municipios agrupados por isla.
         *
         * El orden se utiliza tanto en administración como
         * en los selectores públicos.
         */
        $municipalitiesByIsland = [
            'el-hierro' => [
                'El Pinar de El Hierro',
                'Frontera',
                'Valverde',
            ],

            'fuerteventura' => [
                'Antigua',
                'Betancuria',
                'La Oliva',
                'Pájara',
                'Puerto del Rosario',
                'Tuineje',
            ],

            'gran-canaria' => [
                'Agaete',
                'Agüimes',
                'Artenara',
                'Arucas',
                'Firgas',
                'Gáldar',
                'Ingenio',
                'La Aldea de San Nicolás',
                'Las Palmas de Gran Canaria',
                'Mogán',
                'Moya',
                'San Bartolomé de Tirajana',
                'Santa Brígida',
                'Santa Lucía de Tirajana',
                'Santa María de Guía de Gran Canaria',
                'Tejeda',
                'Telde',
                'Teror',
                'Valleseco',
                'Valsequillo de Gran Canaria',
                'Vega de San Mateo',
            ],

            'la-gomera' => [
                'Agulo',
                'Alajeró',
                'Hermigua',
                'San Sebastián de La Gomera',
                'Valle Gran Rey',
                'Vallehermoso',
            ],

            /*
             * La Graciosa no contiene municipios.
             * Su administración municipal corresponde a Teguise.
             */
            'la-graciosa' => [],

            'la-palma' => [
                'Barlovento',
                'Breña Alta',
                'Breña Baja',
                'El Paso',
                'Fuencaliente de La Palma',
                'Garafía',
                'Los Llanos de Aridane',
                'Puntagorda',
                'Puntallana',
                'San Andrés y Sauces',
                'Santa Cruz de La Palma',
                'Tazacorte',
                'Tijarafe',
                'Villa de Mazo',
            ],

            'lanzarote' => [
                'Arrecife',
                'Haría',
                'San Bartolomé',
                'Teguise',
                'Tías',
                'Tinajo',
                'Yaiza',
            ],

            'tenerife' => [
                'Adeje',
                'Arafo',
                'Arico',
                'Arona',
                'Buenavista del Norte',
                'Candelaria',
                'El Rosario',
                'El Sauzal',
                'El Tanque',
                'Fasnia',
                'Garachico',
                'Granadilla de Abona',
                'Guía de Isora',
                'Güímar',
                'Icod de los Vinos',
                'La Guancha',
                'La Matanza de Acentejo',
                'La Orotava',
                'La Victoria de Acentejo',
                'Los Realejos',
                'Los Silos',
                'Puerto de la Cruz',
                'San Cristóbal de La Laguna',
                'San Juan de la Rambla',
                'San Miguel de Abona',
                'Santa Cruz de Tenerife',
                'Santa Úrsula',
                'Santiago del Teide',
                'Tacoronte',
                'Tegueste',
                'Vilaflor de Chasna',
            ],
        ];

        foreach (
            $municipalitiesByIsland
            as $islandSlug => $municipalities
        ) {
            $areaId =
                (int) (
                    $islandIds[
                        $islandSlug
                    ]
                    ?? 0
                );

            if ($areaId <= 0) {
                throw new RuntimeException(
                    sprintf(
                        'No se encontró el área de la isla %s.',
                        $islandSlug
                    )
                );
            }

            foreach (
                $municipalities
                as $index => $municipalityName
            ) {
                $upsertMunicipality(
                    $areaId,
                    $municipalityName,
                    sanitize_title(
                        $municipalityName
                    ),
                    ($index + 1) * 10
                );
            }
        }

        /*
         * Verificación final.
         */
        $countryCount =
            (int) $wpdb->get_var(
                "
                SELECT COUNT(*)
                FROM {$countriesTable}
                WHERE iso_code = 'ES'
                "
            );

        if ($countryCount !== 1) {
            throw new RuntimeException(
                'No se pudo verificar la carga de España.'
            );
        }

        $canaryIslandCount =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$areasTable}
                    WHERE parent_id = %d
                      AND area_type = 'island'
                    ",
                    $canaryRegionId
                )
            );

        if ($canaryIslandCount !== 8) {
            throw new RuntimeException(
                sprintf(
                    'Se esperaban 8 islas y se encontraron %d.',
                    $canaryIslandCount
                )
            );
        }

        $canaryMunicipalityCount =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$municipalitiesTable}
                    WHERE area_id IN (
                        SELECT id
                        FROM {$areasTable}
                        WHERE parent_id = %d
                          AND area_type = 'island'
                    )
                    ",
                    $canaryRegionId
                )
            );

        if ($canaryMunicipalityCount !== 88) {
            throw new RuntimeException(
                sprintf(
                    'Se esperaban 88 municipios y se encontraron %d.',
                    $canaryMunicipalityCount
                )
            );
        }

        $wpdb->query(
            'COMMIT'
        );
    } catch (Throwable $exception) {
        $wpdb->query(
            'ROLLBACK'
        );

        throw $exception;
    }
};