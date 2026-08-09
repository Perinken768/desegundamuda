<?php

declare(strict_types=1);

namespace DSM\Anuncios\Admin;

use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Application\PublishAdvertisement;
use DSM\Anuncios\Application\RejectAdvertisement;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador HTTP para la moderación administrativa.
 *
 * Sus responsabilidades se limitan a:
 *
 * - comprobar permisos;
 * - validar el nonce;
 * - leer y sanear la petición;
 * - ejecutar el caso de uso correspondiente;
 * - registrar la auditoría;
 * - guardar errores temporales;
 * - redirigir al detalle del anuncio.
 *
 * Las reglas de negocio, las transiciones, las transacciones
 * y el historial pertenecen a AdvertisementModerationService.
 */
final class AdvertisementAdminController
{
    public const ACTION_PUBLISH =
        'dsm_advertisement_admin_publish';

    public const ACTION_REJECT =
        'dsm_advertisement_admin_reject';

    public const ACTION_RESERVE =
        'dsm_advertisement_admin_reserve';

    public const ACTION_RELEASE =
        'dsm_advertisement_admin_release';

    public const ACTION_CLOSE =
        'dsm_advertisement_admin_close';

    public const NONCE_FIELD =
        'dsm_advertisement_admin_nonce';

    /*
     * Se mantienen estas constantes públicas para no romper
     * plantillas o integraciones que ya las utilicen.
     */
    public const CLOSURE_REASON_SOLD =
        AdvertisementModerationService::
            CLOSURE_REASON_SOLD;

    public const CLOSURE_REASON_WITHDRAWN =
        AdvertisementModerationService::
            CLOSURE_REASON_WITHDRAWN;

    public const CLOSURE_REASON_MODERATED =
        AdvertisementModerationService::
            CLOSURE_REASON_MODERATED;

    public const CLOSURE_REASON_EXPIRED =
        AdvertisementModerationService::
            CLOSURE_REASON_EXPIRED;

    private const CAPABILITY =
        'manage_options';

    private const ERROR_TRANSIENT_PREFIX =
        'dsm_advertisement_admin_error_';

    private AdvertisementModerationService $moderationService;

    private PublishAdvertisement $publishAdvertisement;

    private RejectAdvertisement $rejectAdvertisement;

    /**
     * Se conserva un constructor sin argumentos porque el
     * bootstrap actual del plugin instancia directamente:
     *
     * new AdvertisementAdminController()
     */
    public function __construct()
    {
        $advertisementRepository =
            new AdvertisementRepository();

        $historyRepository =
            new AdvertisementStatusHistoryRepository();

        $this->moderationService =
            new AdvertisementModerationService(
                $advertisementRepository,
                $historyRepository
            );

        $this->publishAdvertisement =
            new PublishAdvertisement(
                $this->moderationService
            );

        $this->rejectAdvertisement =
            new RejectAdvertisement(
                $this->moderationService
            );
    }

    /**
     * Registra las acciones admin-post.
     */
    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_PUBLISH,
            [
                $this,
                'handlePublish',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_REJECT,
            [
                $this,
                'handleReject',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_RESERVE,
            [
                $this,
                'handleReserve',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_RELEASE,
            [
                $this,
                'handleRelease',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION_CLOSE,
            [
                $this,
                'handleClose',
            ]
        );
    }

    /**
     * Aprueba y publica un anuncio pendiente.
     */
    public function handlePublish(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_PUBLISH
            );

        $userId =
            get_current_user_id();

        try {
            $advertisement =
                $this->publishAdvertisement
                    ->execute(
                        $advertisementId,
                        $userId,
                        'Anuncio aprobado y publicado '
                        . 'desde administración.'
                    );

            $this->clearLastError();

            $this->emitAuditEvent(
                'advertisement.published',
                $advertisementId,
                [
                    'customer_id' =>
                        $advertisement
                            ->getCustomerId(),

                    'previous_status' =>
                        'pending',

                    'new_status' =>
                        $advertisement
                            ->getStatus(),
                ]
            );

            $status =
                'published';
        } catch (Throwable $exception) {
            $this->handleException(
                'publicando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Rechaza un anuncio pendiente.
     */
    public function handleReject(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_REJECT
            );

        $userId =
            get_current_user_id();

        $reason =
            $this->getRejectionReason();

        try {
            $advertisement =
                $this->rejectAdvertisement
                    ->execute(
                        $advertisementId,
                        $userId,
                        $reason
                    );

            $this->clearLastError();

            $this->emitAuditEvent(
                'advertisement.rejected',
                $advertisementId,
                [
                    'customer_id' =>
                        $advertisement
                            ->getCustomerId(),

                    'reason' =>
                        $reason,

                    'new_status' =>
                        $advertisement
                            ->getStatus(),
                ]
            );

            $status =
                'rejected';
        } catch (Throwable $exception) {
            $this->handleException(
                'rechazando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Marca un anuncio activo como reservado desde
     * administración.
     */
    public function handleReserve(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_RESERVE
            );

        $userId =
            get_current_user_id();

        try {
            $advertisement =
                $this->moderationService
                    ->reserveByUser(
                        $advertisementId,
                        $userId,
                        'Anuncio marcado como reservado '
                        . 'desde administración.'
                    );

            $this->clearLastError();

            $this->emitAuditEvent(
                'advertisement.reserved',
                $advertisementId,
                [
                    'customer_id' =>
                        $advertisement
                            ->getCustomerId(),

                    'new_status' =>
                        $advertisement
                            ->getStatus(),
                ]
            );

            $status =
                'reserved';
        } catch (Throwable $exception) {
            $this->handleException(
                'reservando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Libera una reserva desde administración.
     */
    public function handleRelease(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_RELEASE
            );

        $userId =
            get_current_user_id();

        try {
            $advertisement =
                $this->moderationService
                    ->releaseReservationByUser(
                        $advertisementId,
                        $userId,
                        'Reserva liberada desde administración.'
                    );

            $this->clearLastError();

            $this->emitAuditEvent(
                'advertisement.reservation_released',
                $advertisementId,
                [
                    'customer_id' =>
                        $advertisement
                            ->getCustomerId(),

                    'new_status' =>
                        $advertisement
                            ->getStatus(),
                ]
            );

            $status =
                'released';
        } catch (Throwable $exception) {
            $this->handleException(
                'liberando la reserva',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Cierra un anuncio desde administración.
     *
     * El motivo moderated permite que DSM Promocionar
     * distinga este cierre de una venta real.
     */
    public function handleClose(): void
    {
        $advertisementId =
            $this->prepareAction(
                self::ACTION_CLOSE
            );

        $userId =
            get_current_user_id();

        try {
            $advertisement =
                $this->moderationService
                    ->closeByUser(
                        $advertisementId,
                        $userId,
                        self::CLOSURE_REASON_MODERATED,
                        'Anuncio cerrado desde administración.'
                    );

            $this->clearLastError();

            $this->emitAuditEvent(
                'advertisement.closed',
                $advertisementId,
                [
                    'customer_id' =>
                        $advertisement
                            ->getCustomerId(),

                    'closure_reason' =>
                        self::CLOSURE_REASON_MODERATED,

                    'closed_at' =>
                        $advertisement
                            ->getClosedAt()
                            ?->format(
                                'Y-m-d H:i:s'
                            ),

                    'new_status' =>
                        $advertisement
                            ->getStatus(),
                ]
            );

            $status =
                'closed';
        } catch (Throwable $exception) {
            $this->handleException(
                'cerrando',
                $advertisementId,
                $exception
            );

            $status =
                'error';
        }

        $this->redirectToAdvertisement(
            $advertisementId,
            $status
        );
    }

    /**
     * Comprueba permisos, identificador y nonce.
     */
    private function prepareAction(
        string $action
    ): int {
        $this->assertPermission();

        $advertisementId =
            $this->getAdvertisementId();

        check_admin_referer(
            self::getNonceAction(
                $action,
                $advertisementId
            ),
            self::NONCE_FIELD
        );

        return $advertisementId;
    }

    /**
     * Obtiene y sanea el motivo de rechazo.
     */
    private function getRejectionReason(): string
    {
        $reason =
            isset(
                $_POST[
                    'rejection_reason'
                ]
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST[
                            'rejection_reason'
                        ]
                    )
                )
                : '';

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new RuntimeException(
                'Debes indicar el motivo del rechazo.'
            );
        }

        $length =
            function_exists(
                'mb_strlen'
            )
                ? mb_strlen(
                    $reason
                )
                : strlen(
                    $reason
                );

        if ($length > 2000) {
            throw new RuntimeException(
                'El motivo del rechazo no puede superar '
                . 'los 2000 caracteres.'
            );
        }

        return $reason;
    }

    /**
     * Obtiene el identificador enviado por POST.
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
            wp_die(
                esc_html__(
                    'No se ha indicado un anuncio válido.',
                    'dsm-anuncios'
                ),
                esc_html__(
                    'Solicitud no válida',
                    'dsm-anuncios'
                ),
                [
                    'response' =>
                        400,
                ]
            );
        }

        return $advertisementId;
    }

    /**
     * Construye el nonce específico de una acción.
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
     * Guarda un error temporal para que AdvertisementsPage
     * pueda mostrarlo después de la redirección.
     */
    private function storeLastError(
        string $message
    ): void {
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
            . get_current_user_id(),
            $message,
            5 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Elimina el error administrativo anterior.
     */
    private function clearLastError(): void
    {
        delete_transient(
            self::ERROR_TRANSIENT_PREFIX
            . get_current_user_id()
        );
    }

    /**
     * Recupera el último error para AdvertisementsPage.
     */
    public static function getLastError(): string
    {
        $error =
            get_transient(
                self::ERROR_TRANSIENT_PREFIX
                . get_current_user_id()
            );

        return is_string($error)
            ? $error
            : '';
    }

    /**
     * Guarda y registra una excepción.
     */
    private function handleException(
        string $operation,
        int $advertisementId,
        Throwable $exception
    ): void {
        $this->storeLastError(
            $exception->getMessage()
        );

        error_log(
            sprintf(
                '[DSM Anuncios] Error %s el anuncio %d: %s',
                $operation,
                $advertisementId,
                $exception->getMessage()
            )
        );
    }

    /**
     * Emite un evento neutral para el futuro módulo
     * de auditoría.
     *
     * @param array<string, mixed> $extra
     */
    private function emitAuditEvent(
        string $event,
        int $advertisementId,
        array $extra = []
    ): void {
        do_action(
            'dsm_audit_event',
            $event,
            array_merge(
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'actor_type' =>
                        'wordpress_user',

                    'actor_id' =>
                        get_current_user_id(),
                ],
                $extra
            )
        );
    }

    /**
     * Redirige al detalle administrativo.
     */
    private function redirectToAdvertisement(
        int $advertisementId,
        string $status
    ): never {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' =>
                        AdvertisementsPage::
                            MENU_SLUG,

                    'view' =>
                        'detail',

                    'advertisement_id' =>
                        $advertisementId,

                    'dsm_ad_status' =>
                        sanitize_key(
                            $status
                        ),
                ],
                admin_url(
                    'admin.php'
                )
            )
        );

        exit;
    }

    /**
     * Comprueba permisos administrativos.
     */
    private function assertPermission(): void
    {
        if (
            current_user_can(
                self::CAPABILITY
            )
        ) {
            return;
        }

        wp_die(
            esc_html__(
                'No tienes permisos para moderar anuncios.',
                'dsm-anuncios'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-anuncios'
            ),
            [
                'response' =>
                    403,
            ]
        );
    }
}