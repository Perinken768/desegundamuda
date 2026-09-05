<?php

declare(strict_types=1);

namespace DSM\Favoritos\Frontend;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Catalogo\Image\ProductImageRepository;
use DSM\Catalogo\Product\Product;
use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Product\ProductStatus;
use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use DSM\Multitienda\Store\Store;
use DSM\Multitienda\Store\StoreRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerFavoritesShortcode
{
    public const SHORTCODE =
        'dsm_customer_favorites';

    private const DEFAULT_LIMIT =
        100;

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );

        /*
         * Protegemos las páginas que contienen
         * el shortcode de favoritos.
         *
         * La redirección se realiza antes de que
         * WordPress empiece a renderizar la plantilla,
         * evitando mostrar una página intermedia al
         * visitante anónimo.
         */
        add_action(
            'template_redirect',
            [
                self::class,
                'protectFavoritesPage',
            ]
        );
    }

    /**
     * Impide acceder a una página de favoritos
     * sin una sesión activa de DSM Clientes.
     *
     * Conservamos la URL solicitada para que,
     * después de iniciar sesión correctamente,
     * DSM Clientes devuelva al cliente exactamente
     * a la página de favoritos.
     */
    public static function protectFavoritesPage(): void
    {
        if (
            is_admin()
            || wp_doing_ajax()
            || !is_singular()
        ) {
            return;
        }

        $post =
            get_queried_object();

        if (
            !($post instanceof \WP_Post)
            || !has_shortcode(
                $post->post_content,
                self::SHORTCODE
            )
        ) {
            return;
        }

        if (
            self::resolveCurrentCustomer()
            !== null
        ) {
            return;
        }

        $favoritesUrl =
            get_permalink(
                $post
            );

        if (
            !is_string($favoritesUrl)
            || $favoritesUrl === ''
        ) {
            $favoritesUrl =
                home_url(
                    '/favoritos/'
                );
        }

        $loginUrl =
            add_query_arg(
                'redirect_to',
                $favoritesUrl,
                home_url(
                    '/iniciar-sesion/'
                )
            );

        wp_safe_redirect(
            $loginUrl
        );

        exit;
    }


    public static function render(): string
    {
        $customerContext =
            self::resolveCurrentCustomer();

        if ($customerContext === null) {
            return self::renderLoginRequired();
        }

        $customerId =
            (int) $customerContext['id'];

        try {
            $favoriteRepository =
                new FavoriteRepository();

            $advertisementRepository =
                new AdvertisementRepository();

            $advertisementImageRepository =
                new AdvertisementImageRepository();

            $productRepository =
                new ProductRepository();

            $productImageRepository =
                new ProductImageRepository();

            $storeRepository =
                new StoreRepository();

            $favorites =
                $favoriteRepository
                    ->findByCustomer(
                        $customerId,
                        self::DEFAULT_LIMIT,
                        0
                    );

            $items =
                self::prepareItems(
                    $favorites,
                    $advertisementRepository,
                    $advertisementImageRepository,
                    $productRepository,
                    $productImageRepository,
                    $storeRepository
                );

            return self::renderTemplate(
                [
                    'customerContext' =>
                        $customerContext,

                    'customerId' =>
                        $customerId,

                    'favorites' =>
                        $items,

                    'favoriteCount' =>
                        count($items),

                    'marketplaceUrl' =>
                        self::resolveMarketplaceUrl(),

                    'hasFavorites' =>
                        $items !== [],
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Favoritos] No se pudieron cargar los favoritos del cliente %d: %s',
                    $customerId,
                    $exception->getMessage()
                )
            );

            return sprintf(
                '<div class="dsm-favorites-notice dsm-favorites-notice--error">%s</div>',
                esc_html__(
                    'No se pudieron cargar tus favoritos.',
                    'dsm-favoritos'
                )
            );
        }
    }

    /**
     * @param array<int, Favorite> $favorites
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepareItems(
        array $favorites,
        AdvertisementRepository $advertisementRepository,
        AdvertisementImageRepository $advertisementImageRepository,
        ProductRepository $productRepository,
        ProductImageRepository $productImageRepository,
        StoreRepository $storeRepository
    ): array {
        $items = [];

        foreach ($favorites as $favorite) {
            if (!($favorite instanceof Favorite)) {
                continue;
            }

            if ($favorite->isAdvertisement()) {
                $item =
                    self::prepareAdvertisement(
                        $favorite,
                        $advertisementRepository,
                        $advertisementImageRepository
                    );

                if ($item !== null) {
                    $items[] =
                        $item;
                }

                continue;
            }

            if ($favorite->isStoreProduct()) {
                $item =
                    self::prepareStoreProduct(
                        $favorite,
                        $productRepository,
                        $productImageRepository,
                        $storeRepository
                    );

                if ($item !== null) {
                    $items[] =
                        $item;
                }
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function prepareAdvertisement(
        Favorite $favorite,
        AdvertisementRepository $advertisementRepository,
        AdvertisementImageRepository $imageRepository
    ): ?array {
        $advertisement =
            $advertisementRepository
                ->findById(
                    $favorite->getItemId()
                );

        if (
            !($advertisement instanceof Advertisement)
            || !$advertisement->isPublic()
        ) {
            return null;
        }

        $cover =
            $imageRepository
                ->findCoverByAdvertisementId(
                    $advertisement->getId()
                );

        $coverAttachmentId =
            $cover?->getAttachmentId();

        $coverUrl =
            self::resolveAttachmentUrl(
                $coverAttachmentId
            );

        return [
            'favorite_id' =>
                $favorite->getId(),

            'item_type' =>
                Favorite::TYPE_ADVERTISEMENT,

            'item_id' =>
                $advertisement->getId(),

            'advertisement_id' =>
                $advertisement->getId(),

            'title' =>
                $advertisement->getTitle(),

            'brand' =>
                $advertisement->getBrand(),

            'price' =>
                $advertisement->getPrice(),

            'original_price' =>
                $advertisement->getOriginalPrice(),

            'condition_code' =>
                $advertisement->getConditionCode(),

            'is_reserved' =>
                $advertisement->isReserved(),

            'cover_url' =>
                $coverUrl,

            'public_url' =>
                self::resolveAdvertisementUrl(
                    $advertisement
                ),

            'source_label' =>
                __(
                    'Particular',
                    'dsm-favoritos'
                ),

            'action_label' =>
                __(
                    'Ver anuncio',
                    'dsm-favoritos'
                ),

            'favorited_at' =>
                $favorite
                    ->getCreatedAt()
                    ->format(
                        'Y-m-d H:i:s'
                    ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function prepareStoreProduct(
        Favorite $favorite,
        ProductRepository $productRepository,
        ProductImageRepository $imageRepository,
        StoreRepository $storeRepository
    ): ?array {
        $product =
            $productRepository
                ->findById(
                    $favorite->getItemId()
                );

        if (!($product instanceof Product)) {
            return null;
        }

        if (
            $product->getStatus()
            !== ProductStatus::ACTIVE
        ) {
            return null;
        }

        $store =
            $storeRepository
                ->findById(
                    $product->getStoreId()
                );

        if (
            !($store instanceof Store)
            || !$store->isActive()
        ) {
            return null;
        }

        $cover =
            $imageRepository
                ->findCoverByProductId(
                    $product->getId()
                );

        $coverAttachmentId =
            $cover?->getAttachmentId();

        $coverUrl =
            self::resolveAttachmentUrl(
                $coverAttachmentId
            );

        $publicUrl =
            home_url(
                '/tienda/'
                . rawurlencode(
                    $store->getSlug()
                )
                . '/'
                . rawurlencode(
                    $product->getSlug()
                )
                . '/'
            );

        return [
            'favorite_id' =>
                $favorite->getId(),

            'item_type' =>
                Favorite::TYPE_STORE_PRODUCT,

            'item_id' =>
                $product->getId(),

            'advertisement_id' =>
                0,

            'title' =>
                $product->getName(),

            'brand' =>
                '',

            'price' =>
                $product->getDefaultPrice(),

            'original_price' =>
                $product->getOriginalPrice(),

            'condition_code' =>
                '',

            'is_reserved' =>
                false,

            'cover_url' =>
                $coverUrl,

            'public_url' =>
                $publicUrl,

            'source_label' =>
                sprintf(
                    __(
                        'Tienda · %s',
                        'dsm-favoritos'
                    ),
                    $store->getName()
                ),

            'action_label' =>
                __(
                    'Ver producto',
                    'dsm-favoritos'
                ),

            'favorited_at' =>
                $favorite
                    ->getCreatedAt()
                    ->format(
                        'Y-m-d H:i:s'
                    ),
        ];
    }

    private static function resolveAttachmentUrl(
        ?int $attachmentId
    ): string {
        if (
            $attachmentId === null
            || $attachmentId <= 0
        ) {
            return '';
        }

        $url =
            wp_get_attachment_image_url(
                $attachmentId,
                'medium'
            );

        return is_string($url)
            ? $url
            : '';
    }

    /**
     * @return array{id:int,status:string}|null
     */
    private static function resolveCurrentCustomer(): ?array
    {
        $context =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($context)) {
            return null;
        }

        $customerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? $context['customer_id']
                    ?? 0
                )
            );

        if ($customerId <= 0) {
            return null;
        }

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if ($status !== 'active') {
            return null;
        }

        return [
            'id' =>
                $customerId,

            'status' =>
                $status,
        ];
    }

    private static function resolveAdvertisementUrl(
        Advertisement $advertisement
    ): string {
        $url =
            home_url(
                '/anuncio/'
                . rawurlencode(
                    $advertisement->getSlug()
                )
                . '/'
            );

        return (string) apply_filters(
            'dsm_advertisement_public_url',
            $url,
            $advertisement->getId(),
            $advertisement
        );
    }

    private static function resolveMarketplaceUrl(): string
    {
        return (string) apply_filters(
            'dsm_advertisements_url',
            home_url(
                '/anuncios/'
            )
        );
    }

    private static function renderLoginRequired(): string
    {
        $loginUrl =
            home_url(
                '/iniciar-sesion/'
            );

        $loginUrl =
            (string) apply_filters(
                'dsm_customer_login_url',
                $loginUrl
            );

        return sprintf(
            '<div class="dsm-favorites-notice dsm-favorites-notice--login"><p>%1$s</p><a class="dsm-favorites-button dsm-favorites-button--primary" href="%2$s">%3$s</a></div>',
            esc_html__(
                'Debes iniciar sesión para consultar tus favoritos.',
                'dsm-favoritos'
            ),
            esc_url(
                $loginUrl
            ),
            esc_html__(
                'Iniciar sesión',
                'dsm-favoritos'
            )
        );
    }

    /**
     * @param array<string, mixed> $variables
     */
    private static function renderTemplate(
        array $variables
    ): string {
        $template =
            DSM_FAVORITOS_PATH
            . 'templates/account/'
            . 'customer-favorites.php';

        if (!is_file($template)) {
            return sprintf(
                '<div class="dsm-favorites-notice dsm-favorites-notice--error">%s</div>',
                esc_html__(
                    'No se encontró la plantilla de favoritos.',
                    'dsm-favoritos'
                )
            );
        }

        extract(
            $variables,
            EXTR_SKIP
        );

        ob_start();

        require $template;

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
    }

    private function __construct()
    {
    }
}
