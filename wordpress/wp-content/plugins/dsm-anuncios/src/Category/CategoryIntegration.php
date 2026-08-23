<?php

declare(strict_types=1);

namespace DSM\Anuncios\Category;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contratos públicos de integración para categorías.
 *
 * Permite que otros módulos consuman categorías sin conocer
 * directamente CategoryRepository ni las tablas internas
 * de DSM Anuncios.
 */
final class CategoryIntegration
{
    public static function register(): void
    {
        /*
         * Lista neutral de categorías públicas.
         *
         * Incluye categorías activas que puedan utilizarse
         * en marketplace, en tienda o en ambos.
         */
        add_filter(
            'dsm_public_categories',
            [
                self::class,
                'resolvePublicCategories',
            ],
            10,
            1
        );

        /*
         * Lista neutral de categorías disponibles para tiendas.
         */
        add_filter(
            'dsm_store_categories',
            [
                self::class,
                'resolveStoreCategories',
            ],
            10,
            1
        );

        /*
         * Contexto neutral de una categoría concreta.
         *
         * Permite validar existencia, estado y disponibilidad
         * para marketplace o tienda.
         */
        add_filter(
            'dsm_category_context_by_id',
            [
                self::class,
                'resolveCategoryContextById',
            ],
            10,
            2
        );
    }

    /**
     * @param mixed $currentCategories
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolvePublicCategories(
        mixed $currentCategories
    ): array {
        if (
            is_array($currentCategories)
            && $currentCategories !== []
        ) {
            return $currentCategories;
        }

        try {
            $repository =
                new CategoryRepository();

            $categories =
                $repository
                    ->findAll(
                        true
                    );

            $result = [];

            foreach ($categories as $category) {
                if (
                    !$category
                        ->canBeUsedInMarketplace()
                    && !$category
                        ->canBeUsedInStore()
                ) {
                    continue;
                }

                $result[] =
                    self::toContext(
                        $category
                    );
            }

            return $result;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudieron resolver '
                . 'las categorías públicas: '
                . $exception->getMessage()
            );

            return [];
        }
    }

    /**
     * @param mixed $currentCategories
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveStoreCategories(
        mixed $currentCategories
    ): array {
        if (
            is_array($currentCategories)
            && $currentCategories !== []
        ) {
            return $currentCategories;
        }

        try {
            $repository =
                new CategoryRepository();

            $categories =
                $repository
                    ->findStoreCategories();

            $result = [];

            foreach ($categories as $category) {
                $result[] =
                    self::toContext(
                        $category
                    );
            }

            return $result;
        } catch (Throwable $exception) {
            error_log(
                '[DSM Anuncios] No se pudieron resolver '
                . 'las categorías de tienda: '
                . $exception->getMessage()
            );

            return [];
        }
    }

    /**
     * @param mixed $currentContext
     *
     * @return array<string, mixed>|null
     */
    public static function resolveCategoryContextById(
        mixed $currentContext,
        int $categoryId
    ): ?array {
        if (is_array($currentContext)) {
            return $currentContext;
        }

        if ($categoryId <= 0) {
            return null;
        }

        try {
            $repository =
                new CategoryRepository();

            $category =
                $repository->findById(
                    $categoryId
                );

            if ($category === null) {
                return null;
            }

            return self::toContext(
                $category
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Anuncios] No se pudo resolver '
                    . 'la categoría %d: %s',
                    $categoryId,
                    $exception->getMessage()
                )
            );

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function toContext(
        Category $category
    ): array {
        return [
            'id' =>
                $category->getId(),

            'parent_id' =>
                $category->getParentId(),

            'name' =>
                $category->getName(),

            'slug' =>
                $category->getSlug(),

            'description' =>
                $category->getDescription(),

            'marketplace_allowed' =>
                $category->isMarketplaceAllowed(),

            'store_allowed' =>
                $category->isStoreAllowed(),

            'is_active' =>
                $category->isActive(),

            'sort_order' =>
                $category->getSortOrder(),

            'can_be_used_in_marketplace' =>
                $category
                    ->canBeUsedInMarketplace(),

            'can_be_used_in_store' =>
                $category
                    ->canBeUsedInStore(),
        ];
    }

    private function __construct()
    {
    }
}
