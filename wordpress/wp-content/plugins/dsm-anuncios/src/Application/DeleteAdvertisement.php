<?php

declare(strict_types=1);

namespace DSM\Anuncios\Application;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use RuntimeException;
use Throwable;
use WP_Post;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Caso de uso para eliminar definitivamente un anuncio.
 *
 * La eliminación afecta a:
 *
 * - relaciones de imágenes;
 * - historial de estados;
 * - registro principal del anuncio.
 *
 * Opcionalmente también pueden eliminarse los adjuntos
 * físicos de la biblioteca multimedia de WordPress.
 */
final class DeleteAdvertisement
{
    private wpdb $database;

    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly AdvertisementImageRepository $imageRepository,
        private readonly AdvertisementStatusHistoryRepository $historyRepository
    ) {
        global $wpdb;

        $this->database =
            $wpdb;
    }

    /**
     * Elimina definitivamente un anuncio perteneciente
     * al cliente.
     *
     * Estados permitidos:
     *
     * - draft
     * - rejected
     * - closed
     */
    public function execute(
        int $customerId,
        int $advertisementId,
        bool $deleteAttachments = true
    ): void {
        $advertisement =
            $this->resolveOwnedAdvertisement(
                $customerId,
                $advertisementId
            );

        if (
            !AdvertisementStatus::canBeDeletedByCustomer(
                $advertisement->getStatus()
            )
        ) {
            throw new RuntimeException(
                'El anuncio no se puede eliminar desde su estado actual.'
            );
        }

        /*
         * Los anuncios retirados por moderación deben
         * conservarse junto con su historial y denuncias.
         *
         * Esta comprobación es de backend y no depende
         * de que el botón Eliminar sea visible.
         */
        if (
            $advertisement->getStatus()
                === AdvertisementStatus::CLOSED
            && $advertisement->getClosureReason()
                === AdvertisementModerationService::
                    CLOSURE_REASON_MODERATED
        ) {
            throw new RuntimeException(
                'Los anuncios retirados por moderación no pueden eliminarse.'
            );
        }

        $attachmentIds =
            $this->collectAttachmentIds(
                $advertisementId
            );

        $this->database->query(
            'START TRANSACTION'
        );

        try {
            $this->imageRepository
                ->deleteByAdvertisementId(
                    $advertisementId
                );

            $this->historyRepository
                ->deleteByAdvertisementId(
                    $advertisementId
                );

            $this->advertisementRepository
                ->deleteById(
                    $advertisementId
                );

            $this->database->query(
                'COMMIT'
            );
        } catch (Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }

        if ($deleteAttachments) {
            $this->deletePhysicalAttachments(
                $attachmentIds
            );
        }

        do_action(
            'dsm_advertisement_deleted',
            $advertisementId,
            $customerId,
            $advertisement,
            $attachmentIds
        );
    }

    private function resolveOwnedAdvertisement(
        int $customerId,
        int $advertisementId
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
            $this->advertisementRepository
                ->findById(
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
                'No tienes permisos para eliminar este anuncio.'
            );
        }

        return $advertisement;
    }

    /**
     * @return array<int, int>
     */
    private function collectAttachmentIds(
        int $advertisementId
    ): array {
        $images =
            $this->imageRepository
                ->findByAdvertisementId(
                    $advertisementId
                );

        $attachmentIds = [];

        foreach ($images as $image) {
            $attachmentId =
                $image->getAttachmentId();

            if ($attachmentId <= 0) {
                continue;
            }

            $attachmentIds[$attachmentId] =
                $attachmentId;
        }

        return array_values(
            $attachmentIds
        );
    }

    /**
     * @param array<int, int> $attachmentIds
     */
    private function deletePhysicalAttachments(
        array $attachmentIds
    ): void {
        foreach ($attachmentIds as $attachmentId) {
            $attachmentId =
                (int) $attachmentId;

            if ($attachmentId <= 0) {
                continue;
            }

            try {
                $attachment =
                    get_post(
                        $attachmentId
                    );

                if (
                    !($attachment instanceof WP_Post)
                    || $attachment->post_type
                        !== 'attachment'
                ) {
                    continue;
                }

                $deleted =
                    wp_delete_attachment(
                        $attachmentId,
                        true
                    );

                if ($deleted === false) {
                    throw new RuntimeException(
                        sprintf(
                            'No se pudo eliminar el adjunto %d.',
                            $attachmentId
                        )
                    );
                }
            } catch (Throwable $exception) {
                error_log(
                    sprintf(
                        '[DSM Anuncios] Error eliminando el adjunto %d '
                        . 'del anuncio eliminado: %s',
                        $attachmentId,
                        $exception->getMessage()
                    )
                );
            }
        }
    }
}