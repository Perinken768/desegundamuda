<?php

declare(strict_types=1);

namespace DSM\Ubicaciones\Integration;

use DSM\Ubicaciones\Area\AreaRepository;
use DSM\Ubicaciones\Country\CountryRepository;
use DSM\Ubicaciones\Municipality\MunicipalityRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integra DSM Ubicaciones con el resto de plugins.
 *
 * Expone los filtros públicos:
 *
 * - dsm_location_countries
 * - dsm_location_areas
 * - dsm_location_municipalities
 * - dsm_location_data
 *
 * Los plugins consumidores no necesitan conocer ni
 * instanciar los repositorios internos de ubicaciones.
 */
final class LocationIntegration
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly AreaRepository $areaRepository,
        private readonly MunicipalityRepository $municipalityRepository
    ) {
    }

    /**
     * Registra la API pública de integración.
     */
    public function register(): void
    {
        add_filter(
            'dsm_location_countries',
            [
                $this,
                'provideCountries',
            ],
            10,
            1
        );

        add_filter(
            'dsm_location_areas',
            [
                $this,
                'provideAreas',
            ],
            10,
            3
        );

        add_filter(
            'dsm_location_municipalities',
            [
                $this,
                'provideMunicipalities',
            ],
            10,
            2
        );

        add_filter(
            'dsm_location_data',
            [
                $this,
                'provideLocationData',
            ],
            10,
            3
        );
    }

    /**
     * Devuelve los países activos.
     *
     * Respeta un resultado ya proporcionado por otro
     * callback con prioridad anterior.
     *
     * @param mixed $countries
     *
     * @return array<int, array<string, mixed>>
     */
    public function provideCountries(
        mixed $countries
    ): array {
        if (
            is_array($countries)
            && $countries !== []
        ) {
            return $countries;
        }

        try {
            $result = [];

            foreach (
                $this->countryRepository
                    ->findActive()
                as $country
            ) {
                $result[] =
                    $country->toArray();
            }

            return $result;
        } catch (Throwable $exception) {
            $this->logError(
                'países',
                $exception
            );

            return [];
        }
    }

    /**
     * Devuelve las áreas activas.
     *
     * Permite filtrar opcionalmente por:
     *
     * - país;
     * - tipo de área.
     *
     * Ejemplos de tipos:
     *
     * - region
     * - province
     * - island
     * - comarca
     * - other
     *
     * Respeta un resultado ya proporcionado por otro
     * callback con prioridad anterior.
     *
     * @param mixed       $areas
     * @param int|null    $countryId
     * @param string|null $areaType
     *
     * @return array<int, array<string, mixed>>
     */
    public function provideAreas(
        mixed $areas,
        ?int $countryId = null,
        ?string $areaType = null
    ): array {
        if (
            is_array($areas)
            && $areas !== []
        ) {
            return $areas;
        }

        try {
            $countryId =
                $countryId !== null
                && $countryId > 0
                    ? $countryId
                    : null;

            $areaType =
                $areaType !== null
                    ? sanitize_key(
                        $areaType
                    )
                    : null;

            $entities =
                $countryId !== null
                    ? $this->areaRepository
                        ->findByCountryId(
                            $countryId,
                            true
                        )
                    : $this->areaRepository
                        ->findActive();

            $result = [];

            foreach ($entities as $area) {
                if (
                    $areaType !== null
                    && $areaType !== ''
                    && $area->getAreaType()
                        !== $areaType
                ) {
                    continue;
                }

                $result[] =
                    $area->toArray();
            }

            return $result;
        } catch (Throwable $exception) {
            $this->logError(
                'áreas',
                $exception
            );

            return [];
        }
    }

    /**
     * Devuelve los municipios activos.
     *
     * Si se recibe areaId, únicamente devuelve los
     * municipios pertenecientes a dicha área.
     *
     * Respeta un resultado ya proporcionado por otro
     * callback con prioridad anterior.
     *
     * @param mixed    $municipalities
     * @param int|null $areaId
     *
     * @return array<int, array<string, mixed>>
     */
    public function provideMunicipalities(
        mixed $municipalities,
        ?int $areaId = null
    ): array {
        if (
            is_array($municipalities)
            && $municipalities !== []
        ) {
            return $municipalities;
        }

        try {
            $areaId =
                $areaId !== null
                && $areaId > 0
                    ? $areaId
                    : null;

            $entities =
                $areaId !== null
                    ? $this->municipalityRepository
                        ->findByAreaId(
                            $areaId,
                            true
                        )
                    : $this->municipalityRepository
                        ->findActive();

            $result = [];

            foreach ($entities as $municipality) {
                $result[] =
                    $municipality->toArray();
            }

            return $result;
        } catch (Throwable $exception) {
            $this->logError(
                'municipios',
                $exception
            );

            return [];
        }
    }

    /**
     * Resuelve la información visible de una ubicación.
     *
     * Puede recibir:
     *
     * - únicamente areaId;
     * - únicamente municipalityId;
     * - ambos identificadores.
     *
     * Si solo se recibe municipalityId, el área se recupera
     * automáticamente desde el municipio.
     *
     * Si se reciben ambos identificadores y el municipio no
     * pertenece al área indicada, se conserva el área recibida
     * y el municipio se descarta.
     *
     * @param mixed    $locationData
     * @param int|null $areaId
     * @param int|null $municipalityId
     *
     * @return array<string, mixed>
     */
    public function provideLocationData(
        mixed $locationData,
        ?int $areaId = null,
        ?int $municipalityId = null
    ): array {
        $locationData =
            is_array($locationData)
                ? $locationData
                : [];

        $areaId =
            $areaId !== null
            && $areaId > 0
                ? $areaId
                : null;

        $municipalityId =
            $municipalityId !== null
            && $municipalityId > 0
                ? $municipalityId
                : null;

        try {
            $area =
                $areaId !== null
                    ? $this->areaRepository
                        ->findById(
                            $areaId
                        )
                    : null;

            $municipality =
                $municipalityId !== null
                    ? $this->municipalityRepository
                        ->findById(
                            $municipalityId
                        )
                    : null;

            /*
             * Si se recibe únicamente el municipio,
             * recuperamos su área automáticamente.
             */
            if (
                $area === null
                && $municipality !== null
            ) {
                $area =
                    $this->areaRepository
                        ->findById(
                            $municipality
                                ->getAreaId()
                        );
            }

            /*
             * Si se reciben área y municipio, comprobamos
             * que ambos sean coherentes.
             */
            if (
                $area !== null
                && $municipality !== null
                && $municipality->getAreaId()
                    !== $area->getId()
            ) {
                $municipality =
                    null;
            }

            return array_merge(
                $locationData,
                [
                    'country_id' =>
                        $area?->getCountryId(),

                    'area_id' =>
                        $area?->getId(),

                    'area_name' =>
                        $area?->getName()
                        ?? '',

                    'area_slug' =>
                        $area?->getSlug()
                        ?? '',

                    'area_type' =>
                        $area?->getAreaType()
                        ?? '',

                    'area_type_label' =>
                        $area?->getAreaTypeLabel()
                        ?? '',

                    'municipality_id' =>
                        $municipality?->getId(),

                    'municipality_name' =>
                        $municipality?->getName()
                        ?? '',

                    'municipality_slug' =>
                        $municipality?->getSlug()
                        ?? '',
                ]
            );
        } catch (Throwable $exception) {
            $this->logError(
                'datos de ubicación',
                $exception
            );

            return array_merge(
                $locationData,
                [
                    'country_id' =>
                        null,

                    'area_id' =>
                        $areaId,

                    'area_name' =>
                        '',

                    'area_slug' =>
                        '',

                    'area_type' =>
                        '',

                    'area_type_label' =>
                        '',

                    'municipality_id' =>
                        $municipalityId,

                    'municipality_name' =>
                        '',

                    'municipality_slug' =>
                        '',
                ]
            );
        }
    }

    /**
     * Registra un error de integración sin interrumpir
     * el funcionamiento del plugin consumidor.
     */
    private function logError(
        string $context,
        Throwable $exception
    ): void {
        error_log(
            sprintf(
                '[DSM Ubicaciones] No se pudieron cargar %s: %s',
                $context,
                $exception->getMessage()
            )
        );
    }
}