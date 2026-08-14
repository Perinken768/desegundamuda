<?php

declare(strict_types=1);

namespace DSM\Favoritos\Frontend;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página "Mis favoritos".
 *
 * Shortcode:
 *
 * [dsm_customer_favorites]
 *
 * DSM Favoritos almacena únicamente la relación:
 *
 * customer_id + advertisement_id
 *
 * Los datos visuales del anuncio continúan perteneciendo
 * a DSM Anuncios.
 */
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

            $imageRepository =
                new AdvertisementImageRepository();

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
                    $imageRepository
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
        AdvertisementImageRepository $imageRepository
    ): array {
        $items = [];

        foreach ($favorites as $favorite) {
            if (!($favorite instanceof Favorite)) {
                continue;
            }

            $advertisement =
                $advertisementRepository
                    ->findById(
                        $favorite->getAdvertisementId()
                    );

            /*
             * "Mis favoritos" solo muestra anuncios que
             * continúan siendo públicos.
             *
             * Una relación antigua puede sobrevivir unos
             * instantes hasta que FavoriteCleanup la elimine.
             */
            if (
                !($advertisement instanceof Advertisement)
                || !$advertisement->isPublic()
            ) {
                continue;
            }

            $cover =
                $imageRepository
                    ->findCoverByAdvertisementId(
                        $advertisement->getId()
                    );

            $coverAttachmentId =
                $cover?->getAttachmentId();

            $coverUrl = '';

            if (
                $coverAttachmentId !== null
                && $coverAttachmentId > 0
            ) {
                $resolvedCoverUrl =
                    wp_get_attachment_image_url(
                        $coverAttachmentId,
                        'medium'
                    );

                if (is_string($resolvedCoverUrl)) {
                    $coverUrl =
                        $resolvedCoverUrl;
                }
            }

            $items[] = [
                'favorite_id' =>
                    $favorite->getId(),

                'advertisement_id' =>
                    $advertisement->getId(),

                'title' =>
                    $advertisement->getTitle(),

                'slug' =>
                    $advertisement->getSlug(),

                'brand' =>
                    $advertisement->getBrand(),

                'price' =>
                    $advertisement->getPrice(),

                'original_price' =>
                    $advertisement->getOriginalPrice(),

                'condition_code' =>
                    $advertisement->getConditionCode(),

                'status' =>
                    $advertisement->getStatus(),

                'is_reserved' =>
                    $advertisement->isReserved(),

                'cover_attachment_id' =>
                    $coverAttachmentId,

                'cover_url' =>
                    $coverUrl,

                'public_url' =>
                    self::resolvePublicUrl(
                        $advertisement
                    ),

                'favorited_at' =>
                    $favorite
                        ->getCreatedAt()
                        ->format(
                            'Y-m-d H:i:s'
                        ),
            ];
        }

        return $items;
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

        /*
         * El contrato actual utiliza "id".
         *
         * Conservamos customer_id como compatibilidad
         * defensiva con integraciones anteriores.
         */
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

    private static function resolvePublicUrl(
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