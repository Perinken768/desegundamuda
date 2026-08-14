<?php

declare(strict_types=1);

namespace DSM\Anuncios\Advertisement;

use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contratos públicos neutrales relacionados con anuncios.
 *
 * Permite que otros módulos consulten información básica
 * de un anuncio sin depender directamente del repositorio
 * interno de DSM Anuncios.
 */
final class AdvertisementIntegration
{
    public static function register(): void
    {
        add_filter(
            'dsm_advertisement_context_by_id',
            [
                self::class,
                'resolveAdvertisementContextById',
            ],
            10,
            2
        );
    }

    /**
     * @param mixed $currentContext
     *
     * @return array<string, mixed>|null
     */
    public static function resolveAdvertisementContextById(
        mixed $currentContext,
        int $advertisementId
    ): ?array {
        if (is_array($currentContext)) {
            return $currentContext;
        }

        if ($advertisementId <= 0) {
            return null;
        }

        try {
            $repository =
                new AdvertisementRepository();

            $advertisement =
                $repository->findById(
                    $advertisementId
                );

            if ($advertisement === null) {
                return null;
            }

            return [
                'id' =>
                    $advertisement->getId(),

                'customer_id' =>
                    $advertisement->getCustomerId(),

                'status' =>
                    $advertisement->getStatus(),

                'title' =>
                    $advertisement->getTitle(),

                'slug' =>
                    $advertisement->getSlug(),

                'is_public' =>
                    $advertisement->isPublic(),

                'is_reserved' =>
                    $advertisement->isReserved(),

                'is_closed' =>
                    $advertisement->isClosed(),

                'published_at' =>
                    $advertisement
                        ->getPublishedAt()
                        ?->format('Y-m-d H:i:s'),

                'closed_at' =>
                    $advertisement
                        ->getClosedAt()
                        ?->format('Y-m-d H:i:s'),
            ];
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Anuncios] No se pudo resolver '
                    . 'el contexto del anuncio %d: %s',
                    $advertisementId,
                    $exception->getMessage()
                )
            );

            return null;
        }
    }

    private function __construct()
    {
    }
}
