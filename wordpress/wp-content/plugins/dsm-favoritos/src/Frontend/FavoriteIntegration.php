<?php

declare(strict_types=1);

namespace DSM\Favoritos\Frontend;

use DSM\Favoritos\Favorite\Favorite;
use DSM\Favoritos\Favorite\FavoriteRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integración pública de DSM Favoritos.
 *
 * Responsabilidades:
 *
 * - exponer consultas de favoritos mediante filtros;
 * - integrarse visualmente con DSM Anuncios;
 * - renderizar el botón de favorito;
 * - cargar los assets públicos cuando son necesarios.
 *
 * DSM Anuncios no conoce la implementación interna
 * de favoritos. Únicamente expone puntos de extensión.
 */
final class FavoriteIntegration
{
    private FavoriteRepository $favoriteRepository;

    private bool $assetsEnqueued = false;

    public function __construct(
        ?FavoriteRepository $favoriteRepository = null
    ) {
        $this->favoriteRepository =
            $favoriteRepository
            ?? new FavoriteRepository();
    }

    /**
     * Registra filtros y acciones públicas.
     */
    public function register(): void
    {
        /*
         * API pública de lectura.
         */
        add_filter(
            'dsm_favorite_is_favorite',
            [
                $this,
                'provideIsFavorite',
            ],
            10,
            3
        );

        add_filter(
            'dsm_favorite_advertisement_ids',
            [
                $this,
                'provideAdvertisementIds',
            ],
            10,
            2
        );

        add_filter(
            'dsm_favorite_customer_count',
            [
                $this,
                'provideCustomerCount',
            ],
            10,
            2
        );

        add_filter(
            'dsm_favorite_advertisement_count',
            [
                $this,
                'provideAdvertisementCount',
            ],
            10,
            2
        );

        /*
         * Integración visual con DSM Anuncios.
         */
        add_action(
            'dsm_advertisement_card_actions',
            [
                $this,
                'renderCardAction',
            ],
            10,
            1
        );

        add_action(
            'dsm_advertisement_detail_actions',
            [
                $this,
                'renderDetailAction',
            ],
            10,
            1
        );

        add_action(
            'dsm_store_product_detail_actions',
            [
                $this,
                'renderStoreProductDetailAction',
            ],
            10,
            1
        );
    }

    /**
     * Indica si el anuncio es favorito de un cliente.
     *
     * @param mixed $currentValue
     * @param mixed $customerId
     * @param mixed $advertisementId
     */
    public function provideIsFavorite(
        mixed $currentValue,
        mixed $customerId,
        mixed $advertisementId
    ): bool {
        if ($currentValue === true) {
            return true;
        }

        $customerId =
            max(
                0,
                (int) $customerId
            );

        $advertisementId =
            max(
                0,
                (int) $advertisementId
            );

        if (
            $customerId <= 0
            || $advertisementId <= 0
        ) {
            return false;
        }

        try {
            return $this->favoriteRepository
                ->exists(
                    $customerId,
                    $advertisementId
                );
        } catch (Throwable $exception) {
            $this->logError(
                'No se pudo comprobar el favorito.',
                $exception
            );

            return false;
        }
    }

    /**
     * Devuelve IDs de anuncios favoritos de un cliente.
     *
     * @param mixed $currentIds
     * @param mixed $customerId
     *
     * @return array<int, int>
     */
    public function provideAdvertisementIds(
        mixed $currentIds,
        mixed $customerId
    ): array {
        if (
            is_array($currentIds)
            && $currentIds !== []
        ) {
            return $this->normalizePositiveIds(
                $currentIds
            );
        }

        $customerId =
            max(
                0,
                (int) $customerId
            );

        if ($customerId <= 0) {
            return [];
        }

        try {
            return $this->normalizePositiveIds(
                $this->favoriteRepository
                    ->findAdvertisementIdsByCustomer(
                        $customerId
                    )
            );
        } catch (Throwable $exception) {
            $this->logError(
                'No se pudieron obtener los anuncios favoritos.',
                $exception
            );

            return [];
        }
    }

    /**
     * Cuenta favoritos guardados por un cliente.
     *
     * @param mixed $currentCount
     * @param mixed $customerId
     */
    public function provideCustomerCount(
        mixed $currentCount,
        mixed $customerId
    ): int {
        $currentCount =
            max(
                0,
                (int) $currentCount
            );

        if ($currentCount > 0) {
            return $currentCount;
        }

        $customerId =
            max(
                0,
                (int) $customerId
            );

        if ($customerId <= 0) {
            return 0;
        }

        try {
            return max(
                0,
                $this->favoriteRepository
                    ->countByCustomer(
                        $customerId
                    )
            );
        } catch (Throwable $exception) {
            $this->logError(
                'No se pudieron contar los favoritos del cliente.',
                $exception
            );

            return 0;
        }
    }

    /**
     * Cuenta cuántos clientes han guardado un anuncio.
     *
     * @param mixed $currentCount
     * @param mixed $advertisementId
     */
    public function provideAdvertisementCount(
        mixed $currentCount,
        mixed $advertisementId
    ): int {
        $currentCount =
            max(
                0,
                (int) $currentCount
            );

        if ($currentCount > 0) {
            return $currentCount;
        }

        $advertisementId =
            max(
                0,
                (int) $advertisementId
            );

        if ($advertisementId <= 0) {
            return 0;
        }

        try {
            return max(
                0,
                $this->favoriteRepository
                    ->countByAdvertisement(
                        $advertisementId
                    )
            );
        } catch (Throwable $exception) {
            $this->logError(
                'No se pudieron contar los favoritos del anuncio.',
                $exception
            );

            return 0;
        }
    }

    /**
     * Renderiza el corazón sobre una tarjeta.
     *
     * @param mixed $advertisement
     */
    /**
     * Renderiza el corazón en el detalle
     * de un producto de tienda.
     *
     * @param mixed $productContext
     */
    public function renderStoreProductDetailAction(
        mixed $productContext
    ): void {
        if (!is_array($productContext)) {
            return;
        }

        $itemId =
            max(
                0,
                (int) (
                    $productContext['id']
                    ?? 0
                )
            );

        if ($itemId <= 0) {
            return;
        }

        $currentCustomer =
            $this->resolveCurrentCustomer();

        $customerId =
            $currentCustomer !== null
                ? (int) $currentCustomer['id']
                : 0;

        $ownerCustomerId =
            max(
                0,
                (int) (
                    $productContext[
                        'owner_customer_id'
                    ]
                    ?? 0
                )
            );

        if (
            $customerId > 0
            && $ownerCustomerId > 0
            && $customerId === $ownerCustomerId
        ) {
            return;
        }

        $itemType =
            Favorite::TYPE_STORE_PRODUCT;

        $isFavorite =
            $customerId > 0
            && $this->favoriteRepository
                ->existsItem(
                    $customerId,
                    $itemType,
                    $itemId
                );

        $action =
            $isFavorite
                ? FavoriteController::ACTION_REMOVE
                : FavoriteController::ACTION_ADD;

        $context =
            'detail';

        $redirectUrl =
            trim(
                (string) (
                    $productContext['public_url']
                    ?? ''
                )
            );

        if ($redirectUrl === '') {
            $redirectUrl =
                home_url('/');
        }

        $template =
            DSM_FAVORITOS_PATH
            . 'templates/public/'
            . 'favorite-button.php';

        if (!is_file($template)) {
            return;
        }

        $this->enqueueAssets();

        require $template;
    }


    public function renderCardAction(
        mixed $advertisement
    ): void {
        if (!is_array($advertisement)) {
            return;
        }

        $this->renderFavoriteButton(
            $advertisement,
            'card'
        );
    }

    /**
     * Renderiza el botón en el detalle del anuncio.
     *
     * @param mixed $advertisement
     */
    public function renderDetailAction(
        mixed $advertisement
    ): void {
        if (!is_array($advertisement)) {
            return;
        }

        $this->renderFavoriteButton(
            $advertisement,
            'detail'
        );
    }

    /**
     * Render común.
     *
     * @param array<string, mixed> $advertisement
     */
    private function renderFavoriteButton(
        array $advertisement,
        string $context
    ): void {
        $advertisementId =
            max(
                0,
                (int) (
                    $advertisement['id']
                    ?? 0
                )
            );

        if ($advertisementId <= 0) {
            return;
        }

        $context =
            sanitize_key(
                $context
            );

        if (
            !in_array(
                $context,
                [
                    'card',
                    'detail',
                ],
                true
            )
        ) {
            return;
        }

        $currentCustomer =
            $this->resolveCurrentCustomer();

        $customerId =
            $currentCustomer !== null
                ? (int) $currentCustomer['id']
                : 0;

        /*
         * Si conocemos el propietario del anuncio,
         * no mostramos el corazón sobre sus propios
         * anuncios.
         *
         * AddFavorite también aplica esta regla,
         * por lo que esto es únicamente una mejora
         * de interfaz.
         */
        $advertisementCustomerId =
            max(
                0,
                (int) (
                    $advertisement[
                        'customer_id'
                    ]
                    ?? 0
                )
            );

        if (
            $customerId > 0
            && $advertisementCustomerId > 0
            && $customerId
                === $advertisementCustomerId
        ) {
            return;
        }

        $isFavorite =
            $customerId > 0
            && $this->favoriteRepository
                ->exists(
                    $customerId,
                    $advertisementId
                );

        $action =
            $isFavorite
                ? FavoriteController::ACTION_REMOVE
                : FavoriteController::ACTION_ADD;

        $redirectUrl =
            $this->resolveCurrentUrl(
                $advertisement
            );

        $template =
            DSM_FAVORITOS_PATH
            . 'templates/public/'
            . 'favorite-button.php';

        if (!is_file($template)) {
            return;
        }

        $this->enqueueAssets();

        require $template;
    }

    /**
     * Obtiene el cliente DSM actual.
     *
     * Para favoritos únicamente exigimos:
     *
     * - ID válido;
     * - estado active.
     *
     * No es necesario tener un método de contacto
     * configurado para guardar favoritos.
     *
     * @return array{id:int,status:string}|null
     */
    private function resolveCurrentCustomer(): ?array
    {
        $customer =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($customer)) {
            return null;
        }

        $customerId =
            max(
                0,
                (int) (
                    $customer['id']
                    ?? 0
                )
            );

        if ($customerId <= 0) {
            return null;
        }

        $status =
            sanitize_key(
                (string) (
                    $customer['status']
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

    /**
     * Obtiene la URL a la que volver después de la
     * operación.
     *
     * @param array<string, mixed> $advertisement
     */
    private function resolveCurrentUrl(
        array $advertisement
    ): string {
        $currentUrl =
            '';

        if (
            isset(
                $advertisement[
                    'public_url'
                ]
            )
        ) {
            $currentUrl =
                trim(
                    (string) $advertisement[
                        'public_url'
                    ]
                );
        }

        /*
         * En el listado queremos volver al listado actual,
         * no necesariamente al detalle.
         *
         * REQUEST_URI conserva filtros y paginación.
         */
        if (
            isset($_SERVER['REQUEST_URI'])
            && is_string(
                $_SERVER['REQUEST_URI']
            )
        ) {
            $requestUri =
                wp_unslash(
                    $_SERVER[
                        'REQUEST_URI'
                    ]
                );

            $candidate =
                home_url(
                    $requestUri
                );

            $candidate =
                wp_validate_redirect(
                    $candidate,
                    ''
                );

            if ($candidate !== '') {
                $currentUrl =
                    $candidate;
            }
        }

        if ($currentUrl === '') {
            $currentUrl =
                home_url('/');
        }

        return $currentUrl;
    }

    /**
     * Carga CSS y JS una única vez por petición.
     */
    private function enqueueAssets(): void
    {
        if ($this->assetsEnqueued) {
            return;
        }

        wp_enqueue_style(
            'dsm-favoritos-public',
            DSM_FAVORITOS_URL
                . 'assets/public/css/'
                . 'favorites.css',
            [],
            DSM_FAVORITOS_VERSION
        );

        wp_enqueue_script(
            'dsm-favoritos-public',
            DSM_FAVORITOS_URL
                . 'assets/public/js/'
                . 'favorites.js',
            [],
            DSM_FAVORITOS_VERSION,
            true
        );

        $this->assetsEnqueued =
            true;
    }

    /**
     * @param array<int|string, mixed> $ids
     *
     * @return array<int, int>
     */
    private function normalizePositiveIds(
        array $ids
    ): array {
        $normalized = [];

        foreach ($ids as $id) {
            $id =
                max(
                    0,
                    (int) $id
                );

            if ($id <= 0) {
                continue;
            }

            $normalized[$id] =
                $id;
        }

        return array_values(
            $normalized
        );
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