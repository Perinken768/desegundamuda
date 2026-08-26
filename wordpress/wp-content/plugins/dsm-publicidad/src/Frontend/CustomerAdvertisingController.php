<?php

declare(strict_types=1);

namespace DSM\Publicidad\Frontend;

use DateTimeImmutable;
use DateTimeZone;
use DSM\Publicidad\Advertising\AdvertisingBannerRepository;
use DSM\Suscripciones\Application\CustomerEntitlementService;
use RuntimeException;
use Throwable;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAdvertisingController
{
    public const CREATE_ACTION =
        'dsm_customer_advertising_create';

    public const UPDATE_ACTION =
        'dsm_customer_advertising_update';

    public const NONCE_FIELD =
        'dsm_customer_advertising_nonce';

    public static function register(): void
    {
        /*
         * Un cliente DSM no es un usuario WordPress.
         *
         * Por tanto ambas rutas deben ejecutar exactamente
         * el mismo controlador. La autenticación real se
         * comprueba mediante dsm_current_customer_context.
         */
        add_action(
            'admin_post_'
            . self::CREATE_ACTION,
            [
                self::class,
                'handleCreate',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::CREATE_ACTION,
            [
                self::class,
                'handleCreate',
            ]
        );


        add_action(
            'admin_post_'
            . self::UPDATE_ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::UPDATE_ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );
    }

    public static function getNonceAction(): string
    {
        return self::CREATE_ACTION;
    }


    public static function getUpdateNonceAction(
        int $bannerId
    ): string {
        return self::UPDATE_ACTION
            . '_'
            . $bannerId;
    }

    public static function handleCreate(): never
    {
        $attachmentId = 0;

        try {
            $customerId =
                self::requireCurrentCustomerId();

            self::verifyNonce();

            /*
             * La publicidad es una prestación de suscripción.
             */
            $entitlementService =
                new CustomerEntitlementService();

            if (
                !$entitlementService->hasAdvertising(
                    $customerId
                )
            ) {
                throw new RuntimeException(
                    'Tu suscripción actual no incluye publicidad.'
                );
            }

            /*
             * Cada suscriptor puede disponer de una única
             * publicidad.
             */
            $repository =
                new AdvertisingBannerRepository();

            if (
                $repository->findByCustomer(
                    $customerId
                ) !== []
            ) {
                throw new RuntimeException(
                    'Ya tienes una publicidad creada. Puedes editar la existente, pero no crear una segunda.'
                );
            }

            $title =
                isset($_POST['title'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'title'
                            ]
                        )
                    )
                    : '';

            $targetUrl =
                isset($_POST['target_url'])
                    ? esc_url_raw(
                        wp_unslash(
                            (string) $_POST[
                                'target_url'
                            ]
                        )
                    )
                    : '';

            $areaId =
                isset($_POST['area_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'area_id'
                            ]
                        )
                    )
                    : 0;

            if ($title === '') {
                throw new RuntimeException(
                    'Debes indicar un nombre para la publicidad.'
                );
            }

            if ($targetUrl === '') {
                throw new RuntimeException(
                    'Debes indicar una URL de destino válida.'
                );
            }

            if ($areaId <= 0) {
                throw new RuntimeException(
                    'Debes seleccionar una isla.'
                );
            }

            self::validateIsland(
                $areaId
            );

            /*
             * La imagen se sube usando la biblioteca multimedia
             * estándar de WordPress, pero queda vinculada
             * funcionalmente a la campaña DSM.
             */
            $attachmentId =
                self::uploadImage();

            $banner =
                $repository->create(
                    title:
                        $title,

                    imageAttachmentId:
                        $attachmentId,

                    targetUrl:
                        $targetUrl,

                    areaId:
                        $areaId,

                    /*
                     * La prioridad comercial no la decide
                     * el cliente desde el formulario.
                     */
                    priority:
                        0,

                    /*
                     * El derecho advertising ya ha sido
                     * validado mediante la suscripción.
                     *
                     * La única publicidad del suscriptor
                     * se activa automáticamente.
                     */
                    status:
                        'active',

                    startsAt:
                        null,

                    endsAt:
                        null,

                    customerId:
                        $customerId
                );

            self::redirect(
                [
                    'advertising_notice' =>
                        'created',

                    'banner_id' =>
                        $banner->getId(),
                ]
            );
        } catch (Throwable $exception) {
            /*
             * Si la imagen llegó a WordPress pero después
             * falló la creación del banner, evitamos dejar
             * attachments huérfanos.
             */
            if ($attachmentId > 0) {
                wp_delete_attachment(
                    $attachmentId,
                    true
                );
            }

            self::redirect(
                [
                    'advertising_notice' =>
                        'error',

                    'advertising_error' =>
                        $exception->getMessage(),

                    'advertising_action' =>
                        'new',
                ]
            );
        }
    }

    public static function handleUpdate(): never
    {
        $newAttachmentId = 0;

        try {
            $customerId =
                self::requireCurrentCustomerId();

            $bannerId =
                isset($_POST['banner_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'banner_id'
                            ]
                        )
                    )
                    : 0;

            if ($bannerId <= 0) {
                throw new RuntimeException(
                    'La publicidad indicada no es válida.'
                );
            }

            self::verifyUpdateNonce(
                $bannerId
            );

            /*
             * Solo puede editar publicidad un cliente cuya
             * suscripción mantenga la prestación advertising.
             */
            $entitlementService =
                new CustomerEntitlementService();

            if (
                !$entitlementService->hasAdvertising(
                    $customerId
                )
            ) {
                throw new RuntimeException(
                    'Tu suscripción actual no incluye publicidad.'
                );
            }

            $repository =
                new AdvertisingBannerRepository();

            /*
             * Nunca confiamos únicamente en banner_id.
             * El banner debe pertenecer al cliente autenticado.
             */
            if (
                !$repository->belongsToCustomer(
                    $bannerId,
                    $customerId
                )
            ) {
                throw new RuntimeException(
                    'No puedes modificar esta publicidad.'
                );
            }

            $banner =
                $repository->findById(
                    $bannerId
                );

            if ($banner === null) {
                throw new RuntimeException(
                    'No se encontró la publicidad.'
                );
            }

            $title =
                isset($_POST['title'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                'title'
                            ]
                        )
                    )
                    : '';

            $targetUrl =
                isset($_POST['target_url'])
                    ? esc_url_raw(
                        wp_unslash(
                            (string) $_POST[
                                'target_url'
                            ]
                        )
                    )
                    : '';

            $areaId =
                isset($_POST['area_id'])
                    ? absint(
                        wp_unslash(
                            (string) $_POST[
                                'area_id'
                            ]
                        )
                    )
                    : 0;

            if ($title === '') {
                throw new RuntimeException(
                    'Debes indicar un nombre para la publicidad.'
                );
            }

            if ($targetUrl === '') {
                throw new RuntimeException(
                    'Debes indicar una URL de destino válida.'
                );
            }

            if ($areaId <= 0) {
                throw new RuntimeException(
                    'Debes seleccionar una isla.'
                );
            }

            self::validateIsland(
                $areaId
            );

            /*
             * La imagen no es obligatoria durante la edición.
             * Si no se proporciona una nueva conservamos
             * el attachment actual.
             */
            $imageAttachmentId =
                $banner->getImageAttachmentId();

            if (self::hasUploadedImage()) {
                $newAttachmentId =
                    self::uploadImage();

                $imageAttachmentId =
                    $newAttachmentId;
            }

            /*
             * El cliente solo puede modificar:
             *
             * - nombre;
             * - imagen;
             * - URL;
             * - isla.
             *
             * Estado, prioridad y vigencia siguen bajo
             * control de DSM.
             */
            $repository->update(
                bannerId:
                    $bannerId,

                title:
                    $title,

                imageAttachmentId:
                    $imageAttachmentId,

                targetUrl:
                    $targetUrl,

                areaId:
                    $areaId,

                priority:
                    $banner->getPriority(),

                status:
                    $banner->getStatus(),

                startsAt:
                    $banner->getStartsAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        ),

                endsAt:
                    $banner->getEndsAt()
                        ?->format(
                            'Y-m-d H:i:s'
                        )
            );

            /*
             * Solo eliminamos la imagen anterior cuando
             * sabemos que la actualización terminó bien.
             */
            if (
                $newAttachmentId > 0
                && $banner->getImageAttachmentId() > 0
                && $banner->getImageAttachmentId()
                    !== $newAttachmentId
            ) {
                wp_delete_attachment(
                    $banner->getImageAttachmentId(),
                    true
                );
            }

            self::redirect(
                [
                    'advertising_notice' =>
                        'updated',
                ]
            );
        } catch (Throwable $exception) {
            /*
             * Si se subió una nueva imagen pero la operación
             * falló, eliminamos únicamente esa imagen nueva.
             */
            if ($newAttachmentId > 0) {
                wp_delete_attachment(
                    $newAttachmentId,
                    true
                );
            }

            $arguments = [
                'advertising_notice' =>
                    'error',

                'advertising_error' =>
                    $exception->getMessage(),

                'advertising_action' =>
                    'edit',
            ];

            if (
                isset($bannerId)
                && $bannerId > 0
            ) {
                $arguments['banner_id'] =
                    $bannerId;
            }

            self::redirect(
                $arguments
            );
        }
    }

    private static function requireCurrentCustomerId(): int
    {
        $context =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        if (!is_array($context)) {
            self::redirectToLogin();
        }

        $customerId =
            max(
                0,
                (int) (
                    $context['id']
                    ?? 0
                )
            );

        $status =
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            );

        if ($customerId <= 0) {
            self::redirectToLogin();
        }

        if ($status !== 'active') {
            throw new RuntimeException(
                'Tu cuenta de cliente no está activa.'
            );
        }

        return $customerId;
    }

    private static function verifyNonce(): void
    {
        $nonce =
            isset($_POST[self::NONCE_FIELD])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            self::NONCE_FIELD
                        ]
                    )
                )
                : '';

        if (
            $nonce === ''
            || !wp_verify_nonce(
                $nonce,
                self::getNonceAction()
            )
        ) {
            throw new RuntimeException(
                'La solicitud de creación no es válida.'
            );
        }
    }

    private static function verifyUpdateNonce(
        int $bannerId
    ): void {
        $nonce =
            isset($_POST[self::NONCE_FIELD])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            self::NONCE_FIELD
                        ]
                    )
                )
                : '';

        if (
            $nonce === ''
            || !wp_verify_nonce(
                $nonce,
                self::getUpdateNonceAction(
                    $bannerId
                )
            )
        ) {
            throw new RuntimeException(
                'La solicitud de edición no es válida.'
            );
        }
    }

    private static function hasUploadedImage(): bool
    {
        if (
            !isset($_FILES['banner_image'])
            || !is_array(
                $_FILES['banner_image']
            )
        ) {
            return false;
        }

        return (int) (
            $_FILES['banner_image']['error']
            ?? UPLOAD_ERR_NO_FILE
        ) !== UPLOAD_ERR_NO_FILE;
    }

    private static function validateIsland(
        int $areaId
    ): void {
        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                'island'
            );

        if (!is_array($areas)) {
            throw new RuntimeException(
                'No se pudieron consultar las islas disponibles.'
            );
        }

        foreach ($areas as $area) {
            if (
                is_array($area)
                && (int) (
                    $area['id']
                    ?? 0
                ) === $areaId
            ) {
                return;
            }
        }

        throw new RuntimeException(
            'La isla seleccionada no es válida.'
        );
    }

    private static function uploadImage(): int
    {
        if (
            !isset($_FILES['banner_image'])
            || !is_array(
                $_FILES['banner_image']
            )
        ) {
            throw new RuntimeException(
                'Debes seleccionar una imagen.'
            );
        }

        $error =
            (int) (
                $_FILES['banner_image']['error']
                ?? UPLOAD_ERR_NO_FILE
            );

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'No se pudo recibir correctamente la imagen.'
            );
        }

        /*
         * Validamos las dimensiones antes de incorporar
         * el archivo a la biblioteca multimedia.
         *
         * El banner recomendado es 1180 x 360 px.
         * Admitimos imágenes mayores, pero evitamos imágenes
         * demasiado pequeñas que perderían calidad al ocupar
         * todo el ancho disponible de la portada.
         */
        $temporaryFile =
            (string) (
                $_FILES['banner_image']['tmp_name']
                ?? ''
            );

        if (
            $temporaryFile === ''
            || !is_uploaded_file(
                $temporaryFile
            )
        ) {
            throw new RuntimeException(
                'No se pudo validar la imagen subida.'
            );
        }

        $imageSize =
            getimagesize(
                $temporaryFile
            );

        if (
            $imageSize === false
            || !isset(
                $imageSize[0],
                $imageSize[1]
            )
        ) {
            throw new RuntimeException(
                'El archivo subido no es una imagen válida.'
            );
        }

        $imageWidth =
            (int) $imageSize[0];

        $imageHeight =
            (int) $imageSize[1];

        if (
            $imageWidth < 900
            || $imageHeight < 275
        ) {
            throw new RuntimeException(
                sprintf(
                    'La imagen es demasiado pequeña (%d × %d px). El tamaño mínimo permitido es 900 × 275 px y recomendamos 1180 × 360 px.',
                    $imageWidth,
                    $imageHeight
                )
            );
        }

        require_once ABSPATH
            . 'wp-admin/includes/file.php';

        require_once ABSPATH
            . 'wp-admin/includes/media.php';

        require_once ABSPATH
            . 'wp-admin/includes/image.php';

        $attachmentId =
            media_handle_upload(
                'banner_image',
                0
            );

        if ($attachmentId instanceof WP_Error) {
            throw new RuntimeException(
                $attachmentId->get_error_message()
            );
        }

        $attachmentId =
            (int) $attachmentId;

        if (
            $attachmentId <= 0
            || !wp_attachment_is_image(
                $attachmentId
            )
        ) {
            if ($attachmentId > 0) {
                wp_delete_attachment(
                    $attachmentId,
                    true
                );
            }

            throw new RuntimeException(
                'El archivo subido no es una imagen válida.'
            );
        }

        return $attachmentId;
    }

    private static function redirectToLogin(): never
    {
        $returnUrl =
            add_query_arg(
                [
                    'advertising_action' =>
                        'new',
                ],
                home_url(
                    '/mi-publicidad/'
                )
            );

        $loginUrl =
            add_query_arg(
                [
                    'redirect_to' =>
                        $returnUrl,
                ],
                home_url(
                    '/iniciar-sesion/'
                )
            );

        wp_safe_redirect(
            $loginUrl
        );

        exit;
    }

    /**
     * @param array<string, scalar> $arguments
     */
    private static function redirect(
        array $arguments = []
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $arguments,
                home_url(
                    '/mi-publicidad/'
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
