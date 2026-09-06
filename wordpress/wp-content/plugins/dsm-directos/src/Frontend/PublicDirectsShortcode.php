<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\DirectAccessService;
use DSM\Directos\Application\DirectVideoEmbedService;
use DSM\Directos\Direct\DirectRepository;
use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicDirectsShortcode
{
    public const SHORTCODE =
        'dsm_public_directs';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                new self(),
                'render',
            ]
        );
    }

    public function render(): string
    {
        try {
            $repository =
                new DirectRepository();

            $directs =
                $repository->findPublic(
                    250
                );

            $customerContext =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            $viewerCustomerId =
                is_array(
                    $customerContext
                )
                    ? max(
                        0,
                        (int) (
                            $customerContext['id']
                            ?? 0
                        )
                    )
                    : 0;

            /*
             * =================================================
             * ACCIÓN PERSONAL DEL DIRECTORIO
             * =================================================
             *
             * - Visitante:
             *     Crear directo -> Suscripciones
             *
             * - Cliente sin acceso a Directos:
             *     Crear directo -> Suscripciones
             *
             * - Cliente con acceso a Directos:
             *     Mi directo -> Mi directo
             */

            $personalActionLabel =
                'Crear directo';

            $personalActionUrl =
                home_url(
                    '/suscripciones/'
                );

            $personalActionClass =
                'dsm-button dsm-button--primary';

            if ($viewerCustomerId > 0) {
                $hasDirectAccess =
                    (
                        new DirectAccessService()
                    )->hasAccess(
                        $viewerCustomerId
                    );

                if ($hasDirectAccess) {
                    $personalActionLabel =
                        'Mi directo';

                    $personalActionUrl =
                        home_url(
                            '/mis-directos/'
                        );

                    $personalActionClass =
                        'dsm-button dsm-button--secondary';
                }
            }


            $favoriteSellerIds = [];

            if (
                $viewerCustomerId > 0
                && class_exists(
                    FavoriteRepository::class
                )
                && class_exists(
                    Favorite::class
                )
            ) {
                $favoriteSellerIds =
                    (
                        new FavoriteRepository()
                    )->findItemIdsByCustomer(
                        $viewerCustomerId,
                        Favorite::TYPE_SELLER
                    );

                $favoriteSellerIds =
                    array_values(
                        array_unique(
                            array_filter(
                                array_map(
                                    'absint',
                                    $favoriteSellerIds
                                )
                            )
                        )
                    );
            }

            $favoriteDirects = [];
            $liveDirects = [];
            $otherDirects = [];

            foreach ($directs as $direct) {
                if (!is_array($direct)) {
                    continue;
                }

                $sellerCustomerId =
                    max(
                        0,
                        (int) (
                            $direct['customer_id']
                            ?? 0
                        )
                    );

                if ($sellerCustomerId <= 0) {
                    continue;
                }

                /*
                 * No mostramos al propio vendedor como
                 * candidato a favorito.
                 */
                $isOwnDirect =
                    $viewerCustomerId > 0
                    && $viewerCustomerId
                        === $sellerCustomerId;

                $isFavorite =
                    in_array(
                        $sellerCustomerId,
                        $favoriteSellerIds,
                        true
                    );

                $status =
                    sanitize_key(
                        (string) (
                            $direct['status']
                            ?? ''
                        )
                    );

                $direct['seller_customer_id'] =
                    $sellerCustomerId;

                $direct['seller_name'] =
                    $this->resolveSellerName(
                        $sellerCustomerId
                    );

                $direct['is_favorite'] =
                    $isFavorite;

                $direct['is_own_direct'] =
                    $isOwnDirect;

                $direct['thumbnail_url'] =
                    $status === 'live'
                        ? $this->resolveThumbnail(
                            (string) (
                                $direct['platform']
                                ?? ''
                            ),
                            (string) (
                                $direct['platform_url']
                                ?? ''
                            )
                        )
                        : '';

                if ($isFavorite) {
                    $favoriteDirects[] =
                        $direct;

                    continue;
                }

                if ($status === 'live') {
                    $liveDirects[] =
                        $direct;

                    continue;
                }

                $otherDirects[] =
                    $direct;
            }

            usort(
                $favoriteDirects,
                static function (
                    array $a,
                    array $b
                ): int {
                    $aLive =
                        (
                            $a['status']
                            ?? ''
                        ) === 'live';

                    $bLive =
                        (
                            $b['status']
                            ?? ''
                        ) === 'live';

                    if ($aLive !== $bLive) {
                        return $aLive
                            ? -1
                            : 1;
                    }

                    return strcmp(
                        (string) (
                            $b['updated_at']
                            ?? ''
                        ),
                        (string) (
                            $a['updated_at']
                            ?? ''
                        )
                    );
                }
            );

            ob_start();

            $template =
                DSM_DIRECTOS_PATH
                . 'templates/public/directs.php';

            require $template;

            return (string) ob_get_clean();

        } catch (Throwable $exception) {
            return sprintf(
                '<p>%s</p>',
                esc_html(
                    $exception->getMessage()
                )
            );
        }
    }

    private function resolveSellerName(
        int $sellerCustomerId
    ): string {
        $sellerContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $sellerCustomerId
            );

        if (!is_array($sellerContext)) {
            return sprintf(
                'Vendedor #%d',
                $sellerCustomerId
            );
        }

        $displayName =
            trim(
                (string) (
                    $sellerContext[
                        'display_name'
                    ]
                    ?? ''
                )
            );

        if ($displayName !== '') {
            return $displayName;
        }

        return sprintf(
            'Vendedor #%d',
            $sellerCustomerId
        );
    }

    private function resolveThumbnail(
        string $platform,
        string $platformUrl
    ): string {
        $video =
            (
                new DirectVideoEmbedService()
            )->resolve(
                $platform,
                $platformUrl
            );

        $resolvedPlatform =
            sanitize_key(
                (string) (
                    $video['platform']
                    ?? ''
                )
            );

        if (
            $resolvedPlatform === 'twitch'
            && !empty(
                $video['channel']
            )
        ) {
            return sprintf(
                'https://static-cdn.jtvnw.net/previews-ttv/live_user_%s-640x360.jpg',
                rawurlencode(
                    (string) $video[
                        'channel'
                    ]
                )
            );
        }

        if (
            $resolvedPlatform === 'youtube'
            && !empty(
                $video['video_id']
            )
        ) {
            return sprintf(
                'https://i.ytimg.com/vi/%s/hqdefault.jpg',
                rawurlencode(
                    (string) $video[
                        'video_id'
                    ]
                )
            );
        }

        return '';
    }

    private function __construct()
    {
    }
}
