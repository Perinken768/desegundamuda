<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Catalogo\Image\ProductImageRepository;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Variant\ProductVariantRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectItemViewService
{
    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null
     */
    public function resolve(
        array $item
    ): ?array {
        $sourceType =
            sanitize_key(
                (string) (
                    $item['source_type']
                    ?? ''
                )
            );

        return match ($sourceType) {
            'advertisement' =>
                $this->resolveAdvertisement(
                    $item
                ),

            'inventory' =>
                $this->resolveInventory(
                    $item
                ),

            default =>
                null,
        };
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null
     */
    private function resolveAdvertisement(
        array $item
    ): ?array {
        $advertisementId =
            max(
                0,
                (int) (
                    $item['advertisement_id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            return null;
        }

        $advertisement =
            (
                new AdvertisementRepository()
            )->findById(
                $advertisementId
            );

        if ($advertisement === null) {
            return null;
        }

        $cover =
            (
                new AdvertisementImageRepository()
            )->findCoverByAdvertisementId(
                $advertisementId
            );

        $attachmentId =
            $cover !== null
                ? $cover->getAttachmentId()
                : 0;

        $imageUrl =
            $attachmentId > 0
                ? wp_get_attachment_image_url(
                    $attachmentId,
                    'medium'
                )
                : false;

        return [
            'live_item_id' =>
                (int) (
                    $item['id']
                    ?? 0
                ),

            'live_number' =>
                (int) (
                    $item['live_number']
                    ?? 0
                ),

            'source_type' =>
                'advertisement',

            'advertisement_id' =>
                $advertisementId,

            'product_id' =>
                null,

            'variant_id' =>
                null,

            'title' =>
                $advertisement->getTitle(),

            'subtitle' =>
                trim(
                    (string) (
                        $advertisement->getBrand()
                        ?? ''
                    )
                ),

            'size' =>
                '',

            'color' =>
                '',

            'price' =>
                $advertisement->getPrice(),

            'available_quantity' =>
                $advertisement->isReserved()
                    ? 0
                    : 1,

            'tracks_stock' =>
                false,

            'image_url' =>
                is_string($imageUrl)
                    ? $imageUrl
                    : '',

            'status' =>
                $advertisement->getStatus(),
        ];
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null
     */
    private function resolveInventory(
        array $item
    ): ?array {
        $productId =
            max(
                0,
                (int) (
                    $item['product_id']
                    ?? 0
                )
            );

        $variantId =
            max(
                0,
                (int) (
                    $item['variant_id']
                    ?? 0
                )
            );

        if (
            $productId <= 0
            || $variantId <= 0
        ) {
            return null;
        }

        $product =
            (
                new ProductRepository()
            )->findById(
                $productId
            );

        $variant =
            (
                new ProductVariantRepository()
            )->findById(
                $variantId
            );

        if (
            $product === null
            || $variant === null
        ) {
            return null;
        }

        if (
            $variant->getProductId()
            !== $product->getId()
        ) {
            return null;
        }

        $cover =
            (
                new ProductImageRepository()
            )->findCoverByProductId(
                $productId
            );

        $attachmentId =
            $cover !== null
                ? $cover->getAttachmentId()
                : 0;

        $imageUrl =
            $attachmentId > 0
                ? wp_get_attachment_image_url(
                    $attachmentId,
                    'medium'
                )
                : false;

        $price =
            $variant->getPrice();

        if ($price === null) {
            $price =
                $product->getDefaultPrice();
        }

        $availableQuantity =
            $variant->tracksStock()
                ? $variant->getAvailableStock()
                : null;

        return [
            'live_item_id' =>
                (int) (
                    $item['id']
                    ?? 0
                ),

            'live_number' =>
                (int) (
                    $item['live_number']
                    ?? 0
                ),

            'source_type' =>
                'inventory',

            'advertisement_id' =>
                null,

            'product_id' =>
                $productId,

            'variant_id' =>
                $variantId,

            'title' =>
                $product->getName(),

            'subtitle' =>
                '',

            'size' =>
                trim(
                    (string) (
                        $variant->getSizeValue()
                        ?? ''
                    )
                ),

            'color' =>
                trim(
                    (string) (
                        $variant->getColorValue()
                        ?? ''
                    )
                ),

            'price' =>
                (float) $price,

            'available_quantity' =>
                $availableQuantity,

            'tracks_stock' =>
                $variant->tracksStock(),

            'image_url' =>
                is_string($imageUrl)
                    ? $imageUrl
                    : '',

            'status' =>
                $variant->isActive()
                    ? 'active'
                    : 'inactive',
        ];
    }
}
