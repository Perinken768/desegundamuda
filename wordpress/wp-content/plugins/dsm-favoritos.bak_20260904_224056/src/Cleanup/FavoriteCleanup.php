<?php

declare(strict_types=1);

namespace DSM\Favoritos\Cleanup;

use DSM\Favoritos\Favorite\FavoriteRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limpieza de relaciones de favoritos.
 *
 * DSM Favoritos no utiliza claves foráneas contra
 * las tablas internas de DSM Clientes o DSM Anuncios.
 *
 * La integridad entre plugins se mantiene mediante
 * los contratos públicos que ambos módulos exponen.
 */
final class FavoriteCleanup
{
    private FavoriteRepository $favoriteRepository;

    public function __construct(
        ?FavoriteRepository $favoriteRepository = null
    ) {
        $this->favoriteRepository =
            $favoriteRepository
            ?? new FavoriteRepository();
    }

    /**
     * Registra las integraciones de limpieza.
     */
    public function register(): void
    {
        /*
         * DSM Anuncios.
         *
         * Este evento se lanza después de haber eliminado
         * definitivamente el anuncio.
         */
        add_action(
            'dsm_advertisement_deleted',
            [
                $this,
                'handleAdvertisementDeleted',
            ],
            10,
            4
        );

        /*
         * DSM Clientes.
         *
         * Utilizamos el evento previo a la eliminación
         * permanente porque todavía contiene customer_id.
         *
         * El evento dsm_customer_deleted utiliza ya una
         * referencia anonimizada y no permite localizar
         * las relaciones del cliente.
         */
        add_action(
            'dsm_customer_before_permanent_deletion',
            [
                $this,
                'handleCustomerBeforePermanentDeletion',
            ],
            10,
            2
        );
    }

    /**
     * Elimina todos los favoritos asociados a un anuncio
     * que ya ha sido eliminado definitivamente.
     *
     * @param mixed $advertisementId
     * @param mixed $customerId
     * @param mixed $advertisement
     * @param mixed $attachmentIds
     */
    public function handleAdvertisementDeleted(
        mixed $advertisementId,
        mixed $customerId = null,
        mixed $advertisement = null,
        mixed $attachmentIds = null
    ): void {
        $advertisementId =
            max(
                0,
                (int) $advertisementId
            );

        if ($advertisementId <= 0) {
            return;
        }

        try {
            $deleted =
                $this->favoriteRepository
                    ->deleteByAdvertisement(
                        $advertisementId
                    );

            if ($deleted > 0) {
                do_action(
                    'dsm_favorites_cleanup_completed',
                    'advertisement',
                    $advertisementId,
                    $deleted
                );
            }
        } catch (Throwable $exception) {
            /*
             * El anuncio ya ha sido eliminado y su transacción
             * ya se confirmó antes de lanzar este hook.
             *
             * No propagamos aquí la excepción porque eso podría
             * hacer creer al consumidor que la eliminación del
             * anuncio falló cuando realmente ya se completó.
             */
            $this->logError(
                sprintf(
                    'No se pudieron limpiar los favoritos del anuncio %d.',
                    $advertisementId
                ),
                $exception
            );
        }
    }

    /**
     * Elimina todos los favoritos pertenecientes al cliente
     * antes de que DSM Clientes borre definitivamente sus datos.
     *
     * En este caso sí propagamos los errores.
     *
     * El hook forma parte del proceso previo a la eliminación,
     * por lo que si no podemos limpiar correctamente las
     * relaciones dependientes es preferible impedir que la
     * eliminación permanente continúe.
     *
     * @param mixed $customerId
     * @param mixed $anonymousReference
     */
    public function handleCustomerBeforePermanentDeletion(
        mixed $customerId,
        mixed $anonymousReference = null
    ): void {
        $customerId =
            max(
                0,
                (int) $customerId
            );

        if ($customerId <= 0) {
            throw new RuntimeException(
                'No se pudo identificar al cliente para limpiar sus favoritos.'
            );
        }

        $deleted =
            $this->favoriteRepository
                ->deleteByCustomer(
                    $customerId
                );

        if ($deleted > 0) {
            do_action(
                'dsm_favorites_cleanup_completed',
                'customer',
                $customerId,
                $deleted
            );
        }
    }

    private function logError(
        string $message,
        Throwable $exception
    ): void {
        error_log(
            '[DSM Favoritos] '
            . $message
            . ' '
            . $exception->getMessage()
        );
    }
}