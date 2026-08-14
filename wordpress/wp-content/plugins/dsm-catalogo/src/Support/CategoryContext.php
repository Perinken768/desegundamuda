<?php

declare(strict_types=1);

namespace DSM\Catalogo\Support;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoryContext
{
    /**
     * @return array<string, mixed>
     */
    public static function requireStoreCategory(
        int $categoryId
    ): array {
        if ($categoryId <= 0) {
            throw new RuntimeException(
                'Debes seleccionar una categoría válida.'
            );
        }

        $context =
            apply_filters(
                'dsm_category_context_by_id',
                null,
                $categoryId
            );

        if (!is_array($context)) {
            throw new RuntimeException(
                'No se encontró la categoría indicada.'
            );
        }

        $resolvedCategoryId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        if (
            $resolvedCategoryId <= 0
            || $resolvedCategoryId !== $categoryId
        ) {
            throw new RuntimeException(
                'La categoría indicada no es válida.'
            );
        }

        if (
            empty(
                $context['is_active']
            )
        ) {
            throw new RuntimeException(
                'La categoría seleccionada no está activa.'
            );
        }

        if (
            empty(
                $context[
                    'can_be_used_in_store'
                ]
            )
        ) {
            throw new RuntimeException(
                'La categoría seleccionada no está disponible para productos de tienda.'
            );
        }

        return $context;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getStoreCategories(): array
    {
        $categories =
            apply_filters(
                'dsm_store_categories',
                []
            );

        return is_array($categories)
            ? $categories
            : [];
    }

    private function __construct()
    {
    }
}
