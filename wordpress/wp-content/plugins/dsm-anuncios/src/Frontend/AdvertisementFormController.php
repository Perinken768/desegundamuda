<?php

declare(strict_types=1);

namespace DSM\Anuncios\Frontend;

use DSM\Anuncios\Advertisement\Advertisement;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use DSM\Anuncios\Advertisement\AdvertisementStatus;
use DSM\Anuncios\Application\CreateAdvertisement;
use DSM\Anuncios\Application\PublishCustomerAdvertisement;
use DSM\Anuncios\Application\UpdateAdvertisement;
use DSM\Anuncios\Category\CategoryRepository;
use DSM\Anuncios\Image\AdvertisementImage;
use DSM\Anuncios\Image\AdvertisementImageRepository;
use DSM\Anuncios\Image\AdvertisementImageService;
use DSM\Anuncios\Moderation\AdvertisementModerationService;
use DSM\Anuncios\Moderation\AdvertisementStatusHistoryRepository;
use RuntimeException;
use Throwable;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador HTTP del formulario de creación y edición
 * de anuncios de clientes.
 *
 * Responsabilidades:
 *
 * - recibir el POST;
 * - comprobar cliente autenticado;
 * - validar nonce;
 * - sanear los datos recibidos;
 * - validar la ubicación;
 * - crear o actualizar el anuncio;
 * - gestionar imágenes;
 * - publicar opcionalmente el anuncio directamente;
 * - redirigir al finalizar.
 *
 * La lógica de negocio se delega en:
 *
 * - CreateAdvertisement;
 * - UpdateAdvertisement;
 * - AdvertisementImageService;
 * - PublishCustomerAdvertisement;
 * - AdvertisementModerationService.
 */
final class AdvertisementFormController
{
    public const ACTION_SAVE =
        'dsm_customer_advertisement_save';

    public const NONCE_FIELD =
        'dsm_customer_advertisement_nonce';

    private const INTENT_DRAFT =
        'draft';

    private const INTENT_PUBLISH =
        'publish';

    private const DEFAULT_LOGIN_PATH =
        '/iniciar-sesion/';

    private const DEFAULT_CREATE_PATH =
        '/publicar-anuncio/';

    private const DEFAULT_EDIT_PATH =
        '/editar-anuncio/';

    private const DEFAULT_ADVERTISEMENTS_PATH =
        '/mis-anuncios/';

    private AdvertisementRepository $advertisementRepository;

    private AdvertisementImageRepository $imageRepository;

    private CreateAdvertisement $createAdvertisement;

    private UpdateAdvertisement $updateAdvertisement;

    private PublishCustomerAdvertisement $publishCustomerAdvertisement;

    private AdvertisementImageService $imageService;

    public function __construct()
    {
        $this->advertisementRepository =
            new AdvertisementRepository();

        $this->imageRepository =
            new AdvertisementImageRepository();

        $categoryRepository =
            new CategoryRepository();

        $historyRepository =
            new AdvertisementStatusHistoryRepository();

        $moderationService =
            new AdvertisementModerationService(
                $this->advertisementRepository,
                $historyRepository
            );

        $this->createAdvertisement =
            new CreateAdvertisement(
                $this->advertisementRepository,
                $categoryRepository
            );

        $this->updateAdvertisement =
            new UpdateAdvertisement(
                $this->advertisementRepository,
                $categoryRepository
            );

        $this->publishCustomerAdvertisement =
            new PublishCustomerAdvertisement(
                $moderationService
            );

        $this->imageService =
            new AdvertisementImageService(
                $this->advertisementRepository,
                $this->imageRepository
            );
    }

    /**
     * Registra el endpoint POST.
     *
     * También registramos nopriv para poder redirigir
     * correctamente a un visitante cuya sesión haya caducado.
     */
    public function register(): void
    {
        add_action(
            'admin_post_'
            . self::ACTION_SAVE,
            [
                $this,
                'handleSave',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::ACTION_SAVE,
            [
                $this,
                'handleSave',
            ]
        );
    }

    /**
     * Procesa creación y edición.
     */
    public function handleSave(): never
    {
        $advertisementId =
            $this->getAdvertisementId();

        $customer =
            $this->resolveCurrentCustomer();

        if ($customer === null) {
            $this->redirectToLogin();
        }

        $customerId =
            (int) $customer['id'];

        $this->verifyNonce(
            $advertisementId
        );

        $intent =
            $this->getSubmitIntent();

        try {
            if (
                $advertisementId <= 0
                && !$this->canCreateAdvertisement(
                    $customer
                )
            ) {
                throw new RuntimeException(
                    'Has alcanzado el límite de anuncios disponibles.'
                );
            }

            $data =
                $this->getAdvertisementData();

            $this->validateLocation(
                (int) $data['area_id'],
                (int) $data['municipality_id']
            );

            if ($advertisementId > 0) {
                $advertisement =
                    $this->updateAdvertisement
                        ->execute(
                            $customerId,
                            $advertisementId,
                            $data
                        );
            } else {
                $advertisement =
                    $this->createAdvertisement
                        ->execute(
                            $customerId,
                            $data
                        );

                $advertisementId =
                    $advertisement->getId();
            }

            /*
             * Las imágenes se gestionan antes de enviar
             * el anuncio a revisión, porque pending ya no
             * es un estado editable por el cliente.
             */
            $this->processImageChanges(
                $customerId,
                $advertisement
            );

            /*
             * =================================================
             * PUBLICACIÓN DIRECTA
             * =================================================
             *
             * El cliente puede:
             *
             * - guardar como borrador;
             * - publicar directamente.
             *
             * Si está editando un anuncio ACTIVE, UpdateAdvertisement
             * conserva el estado ACTIVE y no es necesario realizar
             * una transición adicional.
             */

            if ($intent === self::INTENT_PUBLISH) {
                $publishedAdvertisement =
                    $this->publishCustomerAdvertisement
                        ->execute(
                            $customerId,
                            $advertisementId
                        );

                do_action(
                    'dsm_customer_advertisement_published',
                    $advertisementId,
                    $customerId,
                    $publishedAdvertisement
                );

                $this->redirectToAdvertisements(
                    'published'
                );
            }

            do_action(
                'dsm_customer_advertisement_saved',
                $advertisementId,
                $customerId,
                $advertisementId > 0
            );

            $this->redirectToEdit(
                $advertisementId,
                'saved'
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Anuncios] Error guardando el anuncio '
                    . '%d del cliente %d: %s',
                    $advertisementId,
                    $customerId,
                    $exception->getMessage()
                )
            );

            $this->redirectToFormError(
                $advertisementId,
                $this->resolveErrorCode(
                    $exception
                )
            );
        }
    }

    /**
     * Obtiene los datos del anuncio desde POST.
     *
     * @return array<string, mixed>
     */
    private function getAdvertisementData(): array
    {
        $categoryId =
            $this->getPostPositiveInt(
                'category_id'
            );

        $areaId =
            $this->getPostPositiveInt(
                'area_id'
            );

        $municipalityId =
            $this->getPostPositiveInt(
                'municipality_id'
            );

        $title =
            $this->getPostText(
                'title'
            );

        $description =
            $this->getPostTextarea(
                'description'
            );

        $brand =
            $this->getPostText(
                'brand'
            );

        $conditionCode =
            isset($_POST['condition_code'])
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[
                            'condition_code'
                        ]
                    )
                )
                : '';

        $price =
            $this->getPostPrice(
                'price',
                false
            );

        $originalPrice =
            $this->getPostPrice(
                'original_price',
                true
            );

        $purchaseDate =
            $this->getPostDate(
                'purchase_date'
            );

        return [
            'category_id' =>
                $categoryId,

            'area_id' =>
                $areaId,

            'municipality_id' =>
                $municipalityId,

            'title' =>
                $title,

            'description' =>
                $description,

            'brand' =>
                $brand !== ''
                    ? $brand
                    : null,

            'price' =>
                $price,

            'original_price' =>
                $originalPrice,

            'purchase_date' =>
                $purchaseDate,

            'condition_code' =>
                $conditionCode,
        ];
    }

    /**
     * Gestiona:
     *
     * - eliminación de imágenes;
     * - subida de nuevas imágenes;
     * - selección de portada.
     */
    private function processImageChanges(
        int $customerId,
        Advertisement $advertisement
    ): void {
        $advertisementId =
            $advertisement->getId();

        $removeImageIds =
            $this->getPostPositiveIntArray(
                'remove_image_ids'
            );

        /*
         * Primero eliminamos las imágenes marcadas.
         *
         * Así las nuevas subidas pueden ocupar las plazas
         * que hayan quedado libres.
         */
        foreach (
            $removeImageIds
            as $imageId
        ) {
            $this->imageService
                ->removeImage(
                    $customerId,
                    $advertisementId,
                    $imageId,
                    true
                );
        }

        $newAttachmentIds =
            $this->uploadImages();

        if ($newAttachmentIds !== []) {
            try {
                $this->imageService
                    ->addExistingAttachments(
                        $customerId,
                        $advertisementId,
                        $newAttachmentIds
                    );
            } catch (Throwable $exception) {
                /*
                 * Los adjuntos se han creado en WordPress,
                 * pero todavía no pertenecen al anuncio.
                 * Los eliminamos para no dejar basura.
                 */
                foreach (
                    $newAttachmentIds
                    as $attachmentId
                ) {
                    wp_delete_attachment(
                        $attachmentId,
                        true
                    );
                }

                throw $exception;
            }
        }

        $coverImageId =
            $this->getPostPositiveInt(
                'cover_image_id',
                false
            );

        if (
            $coverImageId > 0
            && !in_array(
                $coverImageId,
                $removeImageIds,
                true
            )
        ) {
            $this->imageService
                ->setCover(
                    $customerId,
                    $advertisementId,
                    $coverImageId
                );
        }

        /*
         * Protección adicional:
         * si la portada fue eliminada, el repositorio asigna
         * automáticamente otra imagen disponible.
         */
        $this->imageRepository
            ->ensureCoverExists(
                $advertisementId
            );
    }

    /**
     * Procesa el input múltiple advertisement_images[].
     *
     * @return array<int, int>
     */
    private function uploadImages(): array
    {
        if (
            !isset(
                $_FILES[
                    'advertisement_images'
                ]
            )
            || !is_array(
                $_FILES[
                    'advertisement_images'
                ]
            )
        ) {
            return [];
        }

        $files =
            $_FILES[
                'advertisement_images'
            ];

        $names =
            isset($files['name'])
            && is_array($files['name'])
                ? $files['name']
                : [];

        if ($names === []) {
            return [];
        }

        require_once ABSPATH
            . 'wp-admin/includes/file.php';

        require_once ABSPATH
            . 'wp-admin/includes/media.php';

        require_once ABSPATH
            . 'wp-admin/includes/image.php';

        $attachmentIds = [];

        foreach (
            array_keys($names)
            as $index
        ) {
            $error =
                isset($files['error'][$index])
                    ? (int) $files[
                        'error'
                    ][$index]
                    : UPLOAD_ERR_NO_FILE;

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $this->deleteAttachments(
                    $attachmentIds
                );

                throw new RuntimeException(
                    'No se pudo subir una de las imágenes.'
                );
            }

            $file = [
                'name' =>
                    sanitize_file_name(
                        (string) (
                            $files['name'][$index]
                            ?? ''
                        )
                    ),

                'type' =>
                    sanitize_mime_type(
                        (string) (
                            $files['type'][$index]
                            ?? ''
                        )
                    ),

                'tmp_name' =>
                    (string) (
                        $files['tmp_name'][$index]
                        ?? ''
                    ),

                'error' =>
                    $error,

                'size' =>
                    max(
                        0,
                        (int) (
                            $files['size'][$index]
                            ?? 0
                        )
                    ),
            ];

            if (
                $file['name'] === ''
                || $file['tmp_name'] === ''
            ) {
                $this->deleteAttachments(
                    $attachmentIds
                );

                throw new RuntimeException(
                    'Una de las imágenes recibidas no es válida.'
                );
            }

            $attachmentId =
                media_handle_sideload(
                    $file,
                    0
                );

            if (
                $attachmentId
                instanceof WP_Error
            ) {
                $this->deleteAttachments(
                    $attachmentIds
                );

                throw new RuntimeException(
                    $attachmentId
                        ->get_error_message()
                );
            }

            $attachmentId =
                (int) $attachmentId;

            if ($attachmentId <= 0) {
                $this->deleteAttachments(
                    $attachmentIds
                );

                throw new RuntimeException(
                    'WordPress no pudo crear una de las imágenes.'
                );
            }

            if (
                !wp_attachment_is_image(
                    $attachmentId
                )
            ) {
                wp_delete_attachment(
                    $attachmentId,
                    true
                );

                $this->deleteAttachments(
                    $attachmentIds
                );

                throw new RuntimeException(
                    'Solo se permiten archivos de imagen.'
                );
            }

            $attachmentIds[] =
                $attachmentId;
        }

        return $attachmentIds;
    }

    /**
     * @param array<int, int> $attachmentIds
     */
    private function deleteAttachments(
        array $attachmentIds
    ): void {
        foreach (
            $attachmentIds
            as $attachmentId
        ) {
            if ($attachmentId <= 0) {
                continue;
            }

            wp_delete_attachment(
                $attachmentId,
                true
            );
        }
    }

    /**
     * Comprueba que el municipio pertenezca realmente
     * al área recibida.
     */
    private function validateLocation(
        int $areaId,
        int $municipalityId
    ): void {
        if (
            $areaId <= 0
            || $municipalityId <= 0
        ) {
            throw new RuntimeException(
                'Debes seleccionar una ubicación válida.'
            );
        }

        $municipalities =
            apply_filters(
                'dsm_location_municipalities',
                [],
                $areaId
            );

        if (!is_array($municipalities)) {
            throw new RuntimeException(
                'No se pudo validar la ubicación seleccionada.'
            );
        }

        foreach (
            $municipalities
            as $municipality
        ) {
            if (!is_array($municipality)) {
                continue;
            }

            $currentMunicipalityId =
                (int) (
                    $municipality['id']
                    ?? 0
                );

            $currentAreaId =
                (int) (
                    $municipality['area_id']
                    ?? 0
                );

            if (
                $currentMunicipalityId
                    === $municipalityId
                && $currentAreaId
                    === $areaId
            ) {
                return;
            }
        }

        throw new RuntimeException(
            'El municipio seleccionado no pertenece al área indicada.'
        );
    }

    /**
     * Obtiene el contexto neutral del cliente.
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

        if (
            sanitize_key(
                (string) (
                    $context['status']
                    ?? ''
                )
            ) !== 'active'
        ) {
            throw new RuntimeException(
                'La cuenta del cliente no está activa.'
            );
        }

        if (
            empty(
                $context[
                    'has_valid_contact'
                ]
            )
        ) {
            throw new RuntimeException(
                'Debes configurar un método de contacto válido.'
            );
        }

        return [
            'id' =>
                $customerId,

            'status' =>
                'active',

            'email' =>
                sanitize_email(
                    (string) (
                        $context['email']
                        ?? ''
                    )
                ),

            'display_name' =>
                sanitize_text_field(
                    (string) (
                        $context[
                            'display_name'
                        ]
                        ?? ''
                    )
                ),
        ];
    }

    /**
     * Vuelve a consultar la disponibilidad antes de crear.
     *
     * El shortcode ya la consulta para mostrar el formulario,
     * pero el POST debe validar nuevamente porque nunca
     * confiamos en la interfaz.
     *
     * @param array<string, mixed> $customer
     */
    private function canCreateAdvertisement(
        array $customer
    ): bool {
        $availability =
            apply_filters(
                'dsm_customer_advertisement_publication_availability',
                [
                    'allowed' =>
                        true,

                    'open_count' =>
                        0,

                    'limit' =>
                        null,

                    'remaining' =>
                        null,

                    'plan' =>
                        '',
                ],
                (int) $customer['id'],
                $customer
            );

        if (!is_array($availability)) {
            return true;
        }

        return !array_key_exists(
            'allowed',
            $availability
        )
            || !empty(
                $availability[
                    'allowed'
                ]
            );
    }

    private function verifyNonce(
        int $advertisementId
    ): void {
        check_admin_referer(
            self::getNonceAction(
                $advertisementId
            ),
            self::NONCE_FIELD
        );
    }

    public static function getNonceAction(
        int $advertisementId
    ): string {
        return self::ACTION_SAVE
            . '_'
            . (
                $advertisementId > 0
                    ? $advertisementId
                    : 'new'
            );
    }

    private function getAdvertisementId(): int
    {
        return isset(
            $_POST[
                'advertisement_id'
            ]
        )
            ? max(
                0,
                absint(
                    wp_unslash(
                        (string) $_POST[
                            'advertisement_id'
                        ]
                    )
                )
            )
            : 0;
    }

    private function getSubmitIntent(): string
    {
        $intent =
            isset(
                $_POST[
                    'submit_intent'
                ]
            )
                ? sanitize_key(
                    wp_unslash(
                        (string) $_POST[
                            'submit_intent'
                        ]
                    )
                )
                : self::INTENT_DRAFT;

        if (
            !in_array(
                $intent,
                [
                    self::INTENT_DRAFT,
                    self::INTENT_PUBLISH,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'La acción solicitada no es válida.'
            );
        }

        return $intent;
    }

    private function getPostPositiveInt(
        string $field,
        bool $required = true
    ): int {
        $value =
            isset($_POST[$field])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            $field
                        ]
                    )
                )
                : 0;

        if (
            $required
            && $value <= 0
        ) {
            throw new RuntimeException(
                sprintf(
                    'El campo %s es obligatorio.',
                    $field
                )
            );
        }

        return $value;
    }

    /**
     * @return array<int, int>
     */
    private function getPostPositiveIntArray(
        string $field
    ): array {
        $values =
            isset($_POST[$field])
            && is_array(
                $_POST[$field]
            )
                ? wp_unslash(
                    $_POST[$field]
                )
                : [];

        $result = [];

        foreach ($values as $value) {
            $id =
                absint(
                    (string) $value
                );

            if ($id <= 0) {
                continue;
            }

            $result[$id] =
                $id;
        }

        return array_values(
            $result
        );
    }

    private function getPostText(
        string $field
    ): string {
        return isset($_POST[$field])
            ? sanitize_text_field(
                wp_unslash(
                    (string) $_POST[
                        $field
                    ]
                )
            )
            : '';
    }

    private function getPostTextarea(
        string $field
    ): string {
        return isset($_POST[$field])
            ? sanitize_textarea_field(
                wp_unslash(
                    (string) $_POST[
                        $field
                    ]
                )
            )
            : '';
    }

    private function getPostPrice(
        string $field,
        bool $nullable
    ): ?float {
        $rawValue =
            isset($_POST[$field])
                ? trim(
                    sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                $field
                            ]
                        )
                    )
                )
                : '';

        if ($rawValue === '') {
            if ($nullable) {
                return null;
            }

            throw new RuntimeException(
                'Debes indicar un precio válido.'
            );
        }

        $rawValue =
            str_replace(
                ',',
                '.',
                $rawValue
            );

        if (!is_numeric($rawValue)) {
            throw new RuntimeException(
                'El precio indicado no es válido.'
            );
        }

        $price =
            round(
                (float) $rawValue,
                2
            );

        if ($price < 0) {
            throw new RuntimeException(
                'El precio no puede ser negativo.'
            );
        }

        return $price;
    }

    private function getPostDate(
        string $field
    ): ?string {
        $value =
            isset($_POST[$field])
                ? trim(
                    sanitize_text_field(
                        wp_unslash(
                            (string) $_POST[
                                $field
                            ]
                        )
                    )
                )
                : '';

        if ($value === '') {
            return null;
        }

        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $value
            )
        ) {
            throw new RuntimeException(
                'La fecha de compra no es válida.'
            );
        }

        [
            $year,
            $month,
            $day,
        ] =
            array_map(
                'intval',
                explode(
                    '-',
                    $value
                )
            );

        if (
            !checkdate(
                $month,
                $day,
                $year
            )
        ) {
            throw new RuntimeException(
                'La fecha de compra no es válida.'
            );
        }

        return $value;
    }

    private function resolveErrorCode(
        Throwable $exception
    ): string {
        $message =
            strtolower(
                $exception->getMessage()
            );

        if (
            str_contains(
                $message,
                'categor'
            )
        ) {
            return 'invalid_category';
        }

        if (
            str_contains(
                $message,
                'ubicación'
            )
            || str_contains(
                $message,
                'municipio'
            )
            || str_contains(
                $message,
                'área'
            )
        ) {
            return 'invalid_location';
        }

        if (
            str_contains(
                $message,
                'precio'
            )
        ) {
            return 'invalid_price';
        }

        if (
            str_contains(
                $message,
                'límite'
            )
        ) {
            return 'limit_reached';
        }

        if (
            str_contains(
                $message,
                'estado actual'
            )
            || str_contains(
                $message,
                'editar'
            )
        ) {
            return 'not_editable';
        }

        return 'save_failed';
    }

    private function redirectToEdit(
        int $advertisementId,
        string $status
    ): never {
        $url =
            add_query_arg(
                [
                    'advertisement_id' =>
                        $advertisementId,

                    'dsm_form_status' =>
                        sanitize_key(
                            $status
                        ),
                ],
                home_url(
                    self::DEFAULT_EDIT_PATH
                )
            );

        $url =
            apply_filters(
                'dsm_customer_advertisement_edit_url',
                $url,
                $advertisementId,
                null
            );

        wp_safe_redirect(
            (string) $url
        );

        exit;
    }

    private function redirectToAdvertisements(
        string $status
    ): never {
        $url =
            add_query_arg(
                [
                    'dsm_form_status' =>
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

    private function redirectToFormError(
        int $advertisementId,
        string $errorCode
    ): never {
        $baseUrl =
            $advertisementId > 0
                ? home_url(
                    self::DEFAULT_EDIT_PATH
                )
                : home_url(
                    self::DEFAULT_CREATE_PATH
                );

        $arguments = [
            'dsm_form_error' =>
                sanitize_key(
                    $errorCode
                ),
        ];

        if ($advertisementId > 0) {
            $arguments[
                'advertisement_id'
            ] =
                $advertisementId;
        }

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                $baseUrl
            )
        );

        exit;
    }

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
