<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Category\CategoryRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class UpdateAdvertisement
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
        int $advertisementId,
        array $data
    ): Advertisement {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        $advertisement =
            $this->advertisementRepository->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            throw new RuntimeException(
                'No se encontró el anuncio.'
            );
        }

        if (
            !$advertisement->belongsToCustomer(
                $customerId
            )
        ) {
            throw new RuntimeException(
                'No tienes permisos para editar este anuncio.'
            );
        }

        if (!$advertisement->isEditableByCustomer()) {
            throw new RuntimeException(
                'El anuncio no se puede editar en su estado actual.'
            );
        }

        $categoryId = array_key_exists(
            'category_id',
            $data
        )
            ? (int) $data['category_id']
            : $advertisement->getCategoryId();

        $category =
            $this->categoryRepository->findById(
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

        if (!$category->canBeUsedInMarketplace()) {
            throw new RuntimeException(
                'La categoría seleccionada no admite anuncios de clientes.'
            );
        }

        $title = array_key_exists(
            'title',
            $data
        )
            ? trim((string) $data['title'])
            : $advertisement->getTitle();

        if ($title === '') {
            throw new RuntimeException(
                'El título del anuncio es obligatorio.'
            );
        }

        if (mb_strlen($title) > 180) {
            throw new RuntimeException(
                'El título no puede superar los 180 caracteres.'
            );
        }

        $description = array_key_exists(
            'description',
            $data
        )
            ? trim((string) $data['description'])
            : $advertisement->getDescription();

        if ($description === '') {
            throw new RuntimeException(
                'La descripción del anuncio es obligatoria.'
            );
        }

        $conditionCode = array_key_exists(
            'condition_code',
            $data
        )
            ? sanitize_key(
                (string) $data['condition_code']
            )
            : $advertisement->getConditionCode();

        if ($conditionCode === '') {
            throw new RuntimeException(
                'Debes indicar el estado de conservación.'
            );
        }

        $this->advertisementRepository->updateDetails(
            $advertisementId,
            [
                'category_id' =>
                    $categoryId,

                'island_id' =>
                    array_key_exists(
                        'island_id',
                        $data
                    )
                        ? $data['island_id']
                        : $advertisement->getIslandId(),

                'municipality_id' =>
                    array_key_exists(
                        'municipality_id',
                        $data
                    )
                        ? $data['municipality_id']
                        : $advertisement
                            ->getMunicipalityId(),

                'title' =>
                    $title,

                'description' =>
                    $description,

                'brand' =>
                    array_key_exists(
                        'brand',
                        $data
                    )
                        ? $data['brand']
                        : $advertisement->getBrand(),

                'price' =>
                    array_key_exists(
                        'price',
                        $data
                    )
                        ? $data['price']
                        : $advertisement->getPrice(),

                'original_price' =>
                    array_key_exists(
                        'original_price',
                        $data
                    )
                        ? $data['original_price']
                        : $advertisement
                            ->getOriginalPrice(),

                'purchase_date' =>
                    array_key_exists(
                        'purchase_date',
                        $data
                    )
                        ? $data['purchase_date']
                        : (
                            $advertisement
                                ->getPurchaseDate()
                                ?->format('Y-m-d')
                        ),

                'condition_code' =>
                    $conditionCode,
            ]
        );

        $updatedAdvertisement =
            $this->advertisementRepository->findById(
                $advertisementId
            );

        if ($updatedAdvertisement === null) {
            throw new RuntimeException(
                'El anuncio se actualizó, pero no pudo recuperarse.'
            );
        }

        return $updatedAdvertisement;
    }
}