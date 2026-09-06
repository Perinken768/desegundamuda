<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Application\CloseAdvertisement;
use DSM\Anuncios\Application\DeleteAdvertisement;
use DSM\Anuncios\Application\ReleaseReservationAdvertisement;
use DSM\Anuncios\Application\ReserveAdvertisement;
use DSM\Anuncios\Application\SubmitAdvertisementForReview;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procesa las acciones que el propietario puede realizar
 * desde la sección "Mis anuncios".
 *
 * Este controlador HTTP no modifica directamente anuncios.
 *
 * Delega las operaciones en los casos de uso:
 *
 * - SubmitAdvertisementForReview
 * - ReserveAdvertisement
 * - ReleaseReservationAdvertisement
 * - CloseAdvertisement
 * - DeleteAdvertisement
 *
 * La propiedad del anuncio se valida posteriormente mediante
 * customer_id en los casos de uso y servicios correspondientes.
 */
final class CustomerAdvertisementActionController
{
    public const ACTION_SUBMIT =
        'dsm_customer_advertisement_submit';

    public const ACTION_RESERVE =
        'dsm_customer_advertisement_reserve';

    public const ACTION_RELEASE =
        'dsm_customer_advertisement_release';

    public const ACTION_CLOSE =
        'dsm_customer_advertisement_close';

    public const ACTION_DELETE =
        'dsm_customer_advertisement_delete';

    public const NONCE_FIELD =
        'dsm_customer_advertisement_action_nonce';

    private const ERROR_TRANSIENT_PREFIX =
        'dsm_customer_advertisement_action_error_';

    private const DEFAULT_LOGIN_PATH =
        '/iniciar-sesion/';

    private const DEFAULT_ADVERTISEMENTS_PATH =
        '/mis-anuncios/';

    private SubmitAdvertisementForReview
        $submitAdvertisementForReview;

    private ReserveAdvertisement
        $reserveAdvertisement;

    private ReleaseReservationAdvertisement
        $releaseReservationAdvertisement;

    private CloseAdvertisement
        $closeAdvertisement;

    private DeleteAdvertisement
        $deleteAdvertisement;

    public function __construct()
    {
        $advertisementRepository =
            new AdvertisementRepository();

        $imageRepository =
            new AdvertisementImageRepository();

        $historyRepository =
            new AdvertisementStatusHistoryRepository();

        $moderationService =
            new AdvertisementModerationService(
                $advertisementRepository,
                $historyRepository
            );

        $this->submitAdvertisementForReview =
            new SubmitAdvertisementForReview(
                $advertisementRepository,
                $moderationService
            );

        $this->reserveAdvertisement =
            new ReserveAdvertisement(
                $moderationService
            );

        $this->releaseReservationAdvertisement =
            new ReleaseReservationAdvertisement(
                $moderationService
            );

        $this->closeAdvertisement =
            new CloseAdvertisement(
                $moderationService
            );

        $this->deleteAdvertisement =
            new DeleteAdvertisement(
                $advertisementRepository,
                $imageRepository,
                $historyRepository
            );
    }

    /**
     * Registra todos los endpoints POST.
     *
     * DSM Clientes utiliza su propia sesión, independiente
     * de la sesión de WordPress.
     *
     * Por eso registramos tanto admin_post como
     * admin_post_nopriv.
     */
    public function register(): void
    {
        /*
         * ACTION_SUBMIT se conserva por compatibilidad
         * interna, pero ya no se expone al cliente.
         *
         * La publicación normal es directa.
         */

        $this->registerAction(
            self::ACTION_RESERVE,
            'handleReserve'
        );

        $this->registerAction(
            self::ACTION_RELEASE,
            'handleRelease'
        );

        $this->registerAction(
            self::ACTION_CLOSE,
            'handleClose'
        );

        $this->registerAction(
            self::ACTION_DELETE,
            'handleDelete'
        );
    }

    /**
     * Envía un anuncio a moderación.
     */
    public function handleSubmit(): never
    {
        [
            $customer,
            $advertisementId,
        ] =
            $this->prepareAction(
                self::ACTION_SUBMIT
            );

        $customerId =
            (int) $customer['id'];

        try {
            /*
             * Para volver a publicar un anuncio necesitamos
             * que el cliente mantenga un método de contacto
             * válido.
             */
            if (
                array_key_exists(
                    'has_valid_contact',
                    $customer
                )
                && empty(
                    $customer[
                        'has_valid_contact'
                    ]
                )
            ) {
                throw new RuntimeException(
                    'Debes configurar un método de contacto '
                    . 'válido antes de enviar el anuncio.'
                );
            }

            $this->submitAdvertisementForReview
                ->execute(
                    $customerId,
                    $advertisementId
                );

            $this->clearLastError(
                $customerId
            );

            $this->redirectToAdvertisements(
                'submitted'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'enviando a revisión',
                $customerId,
                $advertisementId,
                $exception
            );
        }
    }

    /**
     * Marca un anuncio activo como reservado.
     */
    public function handleReserve(): never
    {
        [
            $customer,
            $advertisementId,
        ] =
            $this->prepareAction(
                self::ACTION_RESERVE
            );

        $customerId =
            (int) $customer['id'];

        try {
            $this->reserveAdvertisement
                ->execute(
                    $customerId,
                    $advertisementId,
                    'Anuncio marcado como reservado '
                    . 'por su propietario.'
                );

            $this->clearLastError(
                $customerId
            );

            $this->redirectToAdvertisements(
                'reserved'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'reservando',
                $customerId,
                $advertisementId,
                $exception
            );
        }
    }

    /**
     * Libera una reserva y devuelve el anuncio a activo.
     */
    public function handleRelease(): never
    {
        [
            $customer,
            $advertisementId,
        ] =
            $this->prepareAction(
                self::ACTION_RELEASE
            );

        $customerId =
            (int) $customer['id'];

        try {
            $this->releaseReservationAdvertisement
                ->execute(
                    $customerId,
                    $advertisementId,
                    'Reserva liberada por '
                    . 'el propietario del anuncio.'
                );

            $this->clearLastError(
                $customerId
            );

            $this->redirectToAdvertisements(
                'released'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'liberando la reserva',
                $customerId,
                $advertisementId,
                $exception
            );
        }
    }

    /**
     * Retira voluntariamente un anuncio.
     *
     * CloseAdvertisement utiliza internamente:
     *
     * closure_reason = withdrawn
     */
    public function handleClose(): never
    {
        [
            $customer,
            $advertisementId,
        ] =
            $this->prepareAction(
                self::ACTION_CLOSE
            );

        $customerId =
            (int) $customer['id'];

        try {
            $this->closeAdvertisement
                ->execute(
                    $customerId,
                    $advertisementId,
                    'Anuncio retirado voluntariamente '
                    . 'por su propietario.'
                );

            $this->clearLastError(
                $customerId
            );

            $this->redirectToAdvertisements(
                'closed'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'cerrando',
                $customerId,
                $advertisementId,
                $exception
            );
        }
    }

    /**
     * Elimina definitivamente un anuncio.
     *
     * DeleteAdvertisement ya limita los estados permitidos
     * y comprueba que el anuncio pertenezca al cliente.
     *
     * También elimina por defecto los adjuntos físicos.
     */
    public function handleDelete(): never
    {
        [
            $customer,
            $advertisementId,
        ] =
            $this->prepareAction(
                self::ACTION_DELETE
            );

        $customerId =
            (int) $customer['id'];

        try {
            $this->deleteAdvertisement
                ->execute(
                    $customerId,
                    $advertisementId,
                    true
                );

            $this->clearLastError(
                $customerId
            );

            $this->redirectToAdvertisements(
                'deleted'
            );
        } catch (Throwable $exception) {
            $this->handleException(
                'eliminando',
                $customerId,
                $advertisementId,
                $exception
            );
        }
    }

    /**
     * Registra una acción tanto para usuarios WordPress
     * autenticados como para visitantes.
     *
     * Los clientes DSM normalmente pertenecen al segundo
     * grupo porque utilizan una sesión independiente.
     */
    private function registerAction(
        string $action,
        string $handler
    ): void {
        add_action(
            'admin_post_'
            . $action,
            [
                $this,
                $handler,
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . $action,
            [
                $this,
                $handler,
            ]
        );
    }

    /**
     * Prepara una acción:
     *
     * - resuelve al cliente;
     * - comprueba que esté activo;
     * - obtiene advertisement_id;
     * - valida el nonce.
     *
     * @return array{
     *     0:array<string, mixed>,
     *     1:int
     * }
     */
    private function prepareAction(
        string $action
    ): array {
        $customer =
            $this->resolveCurrentCustomer();

        if ($customer === null) {
            $this->redirectToLogin();
        }

        $advertisementId =
            $this->getAdvertisementId();

        check_admin_referer(
            self::getNonceAction(
                $action,
                $advertisementId
            ),
            self::NONCE_FIELD
        );

        return [
            $customer,
            $advertisementId,
        ];
    }

    /**
     * Obtiene el cliente autenticado desde DSM Clientes.
     *
     * Importante:
     *
     * El contexto devuelve:
     *
     * [
     *     'id' => 25,
     *     ...
     * ]
     *
     * Ese id es el mismo identificador que posteriormente
     * se almacena en:
     *
     * wp_dsm_ads.customer_id
     *
     * @return array<string, mixed>|null
     */
    private function resolveCurrentCustomer(): ?array
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
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }

        /*
         * Normalizamos únicamente lo necesario.
         *
         * Conservamos el resto del contexto para que acciones
         * concretas puedan consultar información adicional
         * como has_valid_contact.
         */
        $context['id'] =
            $customerId;

        $context['status'] =
            $status;

        return $context;
    }

    /**
     * Obtiene advertisement_id desde POST.
     */
    private function getAdvertisementId(): int
    {
        $advertisementId =
            isset(
                $_POST[
                    'advertisement_id'
                ]
            )
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'advertisement_id'
                        ]
                    )
                )
                : 0;

        if ($advertisementId <= 0) {
            throw new RuntimeException(
                'El identificador del anuncio no es válido.'
            );
        }

        return $advertisementId;
    }

    /**
     * Construye un nonce único por acción y anuncio.
     */
    public static function getNonceAction(
        string $action,
        int $advertisementId
    ): string {
        return $action
            . '_'
            . $advertisementId;
    }

    /**
     * Devuelve el último error de acciones del cliente.
     *
     * Se utilizará desde customer-advertisements.php.
     */
    public static function getLastError(
        int $customerId
    ): string {
        if ($customerId <= 0) {
            return '';
        }

        $error =
            get_transient(
                self::ERROR_TRANSIENT_PREFIX
                . $customerId
            );

        return is_string($error)
            ? $error
            : '';
    }

    /**
     * Guarda temporalmente un error.
     */
    private function storeLastError(
        int $customerId,
        string $message
    ): void {
        if ($customerId <= 0) {
            return;
        }

        $message =
            sanitize_text_field(
                $message
            );

        if ($message === '') {
            $message =
                __(
                    'No se pudo completar la acción.',
                    'dsm-anuncios'
                );
        }

        set_transient(
            self::ERROR_TRANSIENT_PREFIX
            . $customerId,
            $message,
            5 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Elimina un error anterior después de una acción
     * completada correctamente.
     */
    private function clearLastError(
        int $customerId
    ): void {
        if ($customerId <= 0) {
            return;
        }

        delete_transient(
            self::ERROR_TRANSIENT_PREFIX
            . $customerId
        );
    }

    /**
     * Registra una excepción y vuelve a "Mis anuncios".
     */
    private function handleException(
        string $operation,
        int $customerId,
        int $advertisementId,
        Throwable $exception
    ): never {
        $this->storeLastError(
            $customerId,
            $exception->getMessage()
        );

        error_log(
            sprintf(
                '[DSM Anuncios] Error %s el anuncio %d '
                . 'del cliente %d: %s',
                $operation,
                $advertisementId,
                $customerId,
                $exception->getMessage()
            )
        );

        $this->redirectToAdvertisements(
            'error'
        );
    }

    /**
     * Devuelve al listado de anuncios del cliente.
     */
    private function redirectToAdvertisements(
        string $status
    ): never {
        $url =
            add_query_arg(
                [
                    'dsm_ad_action_status' =>
                        sanitize_key(
                            $status
                        ),
                ],
                home_url(
                    self::DEFAULT_ADVERTISEMENTS_PATH
                )
            );

        $url =
            apply_filters(
                'dsm_customer_advertisements_url',
                $url
            );

        wp_safe_redirect(
            (string) $url
        );

        exit;
    }

    /**
     * Redirige al login cuando no existe una sesión
     * válida de DSM Clientes.
     */
    private function redirectToLogin(): never
    {
        wp_safe_redirect(
            home_url(
                self::DEFAULT_LOGIN_PATH
            )
        );

        exit;
    }
} 