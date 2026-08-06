<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Category\CategoryRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Caso de uso para crear anuncios particulares.
 *
 * La ubicación utiliza area_id como campo territorial neutral.
 */
final class CreateAdvertisement
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(
        int $customerId,
        array $data
    ): Advertisement {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $categoryId =
            isset($data['category_id'])
                ? (int) $data['category_id']
                : 0;

        $areaId =
            $this->nullablePositiveInt(
                $data['area_id']
                ?? null
            );

        $municipalityId =
            $this->nullablePositiveInt(
                $data['municipality_id']
                ?? null
            );

        $title =
            isset($data['title'])
                ? trim(
                    (string) $data['title']
                )
                : '';

        $description =
            isset($data['description'])
                ? trim(
                    (string) $data['description']
                )
                : '';

        $conditionCode =
            isset($data['condition_code'])
                ? sanitize_key(
                    (string) $data[
                        'condition_code'
                    ]
                )
                : '';

        if ($categoryId <= 0) {
            throw new RuntimeException(
                'Debes seleccionar una categoría.'
            );
        }

        $category =
            $this->categoryRepository
                ->findById(
                    $categoryId
                );

        if ($category === null) {
            throw new RuntimeException(
                'La categoría seleccionada no existe.'
            );
        }

        if (!$category->isActive()) {
            throw new RuntimeException(
                'La categoría seleccionada no está activa.'
            );
        }

        if (
            !$category
                ->canBeUsedInMarketplace()
        ) {
            throw new RuntimeException(
                'La categoría seleccionada no admite anuncios de clientes.'
            );
        }

        if ($title === '') {
            throw new RuntimeException(
                'El título del anuncio es obligatorio.'
            );
        }

        if (
            mb_strlen(
                $title
            ) > 180
        ) {
            throw new RuntimeException(
                'El título no puede superar los 180 caracteres.'
            );
        }

        if ($description === '') {
            throw new RuntimeException(
                'La descripción del anuncio es obligatoria.'
            );
        }

        if ($conditionCode === '') {
            throw new RuntimeException(
                'Debes indicar el estado de conservación.'
            );
        }

        if (
            $municipalityId !== null
            && $areaId === null
        ) {
            throw new RuntimeException(
                'No se puede seleccionar un municipio sin indicar un área.'
            );
        }

        $advertisementId =
            $this->advertisementRepository
                ->create(
                    [
                        'customer_id' =>
                            $customerId,

                        'store_id' =>
                            null,

                        'category_id' =>
                            $categoryId,

                        'area_id' =>
                            $areaId,

                        'municipality_id' =>
                            $municipalityId,

                        'title' =>
                            $title,

                        'description' =>
                            $description,

                        'brand' =>
                            $data['brand']
                            ?? null,

                        'price' =>
                            $data['price']
                            ?? 0,

                        'original_price' =>
                            $data['original_price']
                            ?? null,

                        'purchase_date' =>
                            $data['purchase_date']
                            ?? null,

                        'condition_code' =>
                            $conditionCode,

                        'status' =>
                            AdvertisementStatus::DRAFT,
                    ]
                );

        $advertisement =
            $this->advertisementRepository
                ->findById(
                    $advertisementId
                );

        if ($advertisement === null) {
            throw new RuntimeException(
                'El anuncio se creó, pero no pudo recuperarse.'
            );
        }

        return $advertisement;
    }

    private function nullablePositiveInt(
        mixed $value
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $integer =
            (int) $value;

        return $integer > 0
            ? $integer
            : null;
    }
}