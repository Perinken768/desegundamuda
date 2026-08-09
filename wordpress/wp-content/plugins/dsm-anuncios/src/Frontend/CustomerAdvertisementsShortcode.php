<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Muestra los anuncios pertenecientes al cliente autenticado.
 *
 * El shortcode no conoce las clases internas de DSM Clientes.
 * Obtiene el contexto mediante:
 *
 * dsm_current_customer_context
 */
final class CustomerAdvertisementsShortcode
{
    public const SHORTCODE =
        'dsm_customer_advertisements';

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
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (
            !is_array($customerContext)
            || (
                (int) (
                    $customerContext[
                        'id'
                    ]
                    ?? 0
                )
            ) <= 0
        ) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $customerId =
            (int) $customerContext[
                'id'
            ];

        $selectedStatus =
            self::resolveSelectedStatus();

        try {
            $advertisementRepository =
                new AdvertisementRepository();

            $imageRepository =
                new AdvertisementImageRepository();

            $advertisements =
                $advertisementRepository
                    ->findByCustomer(
                        $customerId,
                        self::DEFAULT_LIMIT,
                        0
                    );

            $items =
                self::prepareItems(
                    $advertisements,
                    $imageRepository,
                    $selectedStatus
                );

            return self::renderTemplate(
                [
                    'customerContext' =>
                        $customerContext,

                    'customerId' =>
                        $customerId,

                    'advertisements' =>
                        $items,

                    'selectedStatus' =>
                        $selectedStatus,

                    'statuses' =>
                        AdvertisementStatus::all(),

                    'statusCounts' =>
                        self::countByStatus(
                            $advertisements
                        ),

                    'createAdvertisementUrl' =>
                        self::resolveCreateAdvertisementUrl(),

                    'customerAdvertisementsUrl' =>
                        self::resolveCustomerAdvertisementsUrl(),

                    'hasAdvertisements' =>
                        $advertisements !== [],

                    'hasFilteredAdvertisements' =>
                        $items !== [],
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Anuncios] No se pudieron cargar los anuncios del cliente %d: %s',
                    $customerId,
                    $exception->getMessage()
                )
            );

            return sprintf(
                '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
                esc_html__(
                    'No se pudieron cargar tus anuncios.',
                    'dsm-anuncios'
                )
            );
        }
    }

    /**
     * @param array<int, Advertisement> $advertisements
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepareItems(
        array $advertisements,
        AdvertisementImageRepository $imageRepository,
        string $selectedStatus
    ): array {
        $items = [];

        foreach ($advertisements as $advertisement) {
            if (
                !($advertisement instanceof Advertisement)
            ) {
                continue;
            }

            if (
                $selectedStatus !== ''
                && $advertisement->getStatus()
                    !== $selectedStatus
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
                'id' =>
                    $advertisement->getId(),

                'title' =>
                    $advertisement->getTitle(),

                'slug' =>
                    $advertisement->getSlug(),

                'status' =>
                    $advertisement->getStatus(),

                'price' =>
                    $advertisement->getPrice(),

                'brand' =>
                    $advertisement->getBrand(),

                'rejection_reason' =>
                    $advertisement
                        ->getRejectionReason(),

                'created_at' =>
                    $advertisement
                        ->getCreatedAt()
                        ->format(
                            'Y-m-d H:i:s'
                        ),

                'updated_at' =>
                    $advertisement
                        ->getUpdatedAt()
                        ->format(
                            'Y-m-d H:i:s'
                        ),

                'published_at' =>
                    $advertisement
                        ->getPublishedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'reserved_at' =>
                    $advertisement
                        ->getReservedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'closed_at' =>
                    $advertisement
                        ->getClosedAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                'cover_attachment_id' =>
                    $coverAttachmentId,

                'cover_url' =>
                    $coverUrl,

                'is_public' =>
                    AdvertisementStatus::isPublic(
                        $advertisement->getStatus()
                    ),

                'can_edit' =>
                    AdvertisementStatus::
                        canBeEditedByCustomer(
                            $advertisement->getStatus()
                        ),

                'can_submit' =>
                    AdvertisementStatus::
                        canBeSubmitted(
                            $advertisement->getStatus()
                        ),

                'can_reserve' =>
                    AdvertisementStatus::
                        canBeReserved(
                            $advertisement->getStatus()
                        ),

                'can_release' =>
                    AdvertisementStatus::
                        canBeReleased(
                            $advertisement->getStatus()
                        ),

                'can_close' =>
                    AdvertisementStatus::
                        canBeClosed(
                            $advertisement->getStatus()
                        ),

                'can_delete' =>
                    AdvertisementStatus::
                        canBeDeletedByCustomer(
                            $advertisement->getStatus()
                        ),

                'public_url' =>
                    self::resolvePublicUrl(
                        $advertisement
                    ),

                'edit_url' =>
                    self::resolveEditUrl(
                        $advertisement
                    ),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, Advertisement> $advertisements
     *
     * @return array<string, int>
     */
    private static function countByStatus(
        array $advertisements
    ): array {
        $counts = [];

        foreach (
            AdvertisementStatus::all()
            as $status
        ) {
            $counts[$status] =
                0;
        }

        foreach ($advertisements as $advertisement) {
            if (
                !($advertisement instanceof Advertisement)
            ) {
                continue;
            }

            $status =
                $advertisement->getStatus();

            if (!isset($counts[$status])) {
                $counts[$status] =
                    0;
            }

            $counts[$status]++;
        }

        return $counts;
    }

    private static function resolveSelectedStatus(): string
    {
        $status =
            isset($_GET['dsm_ad_status'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_GET[
                            'dsm_ad_status'
                        ]
                    )
                )
                : '';

        return AdvertisementStatus::isValid(
            $status
        )
            ? $status
            : '';
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

    private static function resolveEditUrl(
        Advertisement $advertisement
    ): string {
        $url =
            add_query_arg(
                [
                    'advertisement_id' =>
                        $advertisement->getId(),
                ],
                home_url(
                    '/editar-anuncio/'
                )
            );

        return (string) apply_filters(
            'dsm_customer_advertisement_edit_url',
            $url,
            $advertisement->getId(),
            $advertisement
        );
    }

    private static function resolveCreateAdvertisementUrl(): string
    {
        return (string) apply_filters(
            'dsm_customer_advertisement_create_url',
            home_url(
                '/publicar-anuncio/'
            )
        );
    }

    private static function resolveCustomerAdvertisementsUrl(): string
    {
        return (string) apply_filters(
            'dsm_customer_advertisements_url',
            home_url(
                '/mis-anuncios/'
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
            DSM_ANUNCIOS_PATH
            . 'templates/account/'
            . 'customer-advertisements.php';

        if (!is_file($template)) {
            return sprintf(
                '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
                esc_html__(
                    'No se encontró la plantilla de tus anuncios.',
                    'dsm-anuncios'
                )
            );
        }

        extract(
            $variables,
            EXTR_SKIP
        );

        ob_start();

        include $template;

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