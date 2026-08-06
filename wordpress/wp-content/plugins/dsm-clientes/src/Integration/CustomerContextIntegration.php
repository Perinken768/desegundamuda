<?php

declare(strict_types=1);

namespace DSM\Clientes\Integration;

use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integra DSM Clientes con el resto de módulos.
 *
 * Expone:
 *
 * - contexto del cliente autenticado;
 * - información pública del vendedor de un anuncio;
 * - preferencias de llamada y WhatsApp;
 * - URLs de contacto autorizadas;
 * - ubicación territorial neutral mediante area_id.
 *
 * Los plugins consumidores no necesitan conocer las clases
 * internas de autenticación, clientes o perfiles.
 */
final class CustomerContextIntegration
{
    /**
     * Registra los filtros públicos de integración.
     */
    public static function register(): void
    {
        /*
         * Contexto del cliente autenticado.
         */
        add_filter(
            'dsm_current_customer_context',
            [
                self::class,
                'resolve',
            ],
            10,
            1
        );

        /*
         * Información pública del vendedor de un anuncio.
         *
         * DSM Anuncios envía:
         *
         * - datos públicos iniciales;
         * - ID del anuncio.
         */
        add_filter(
            'dsm_advertisement_seller_public_data',
            [
                self::class,
                'resolveAdvertisementSeller',
            ],
            10,
            2
        );
    }

    /**
     * Construye el contexto del cliente autenticado.
     *
     * @param mixed $currentContext
     *
     * @return array<string, mixed>|null
     */
    public static function resolve(
        mixed $currentContext
    ): ?array {
        /*
         * Respeta un contexto ya aportado por una integración
         * ejecutada con una prioridad anterior.
         */
        if (is_array($currentContext)) {
            return $currentContext;
        }

        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            if ($customer === null) {
                return null;
            }

            $profileRepository =
                new CustomerProfileRepository();

            $profile =
                $profileRepository
                    ->findByCustomerId(
                        $customer->getId()
                    );

            $areaId =
                $profile?->getAreaId();

            return [
                'id' =>
                    $customer->getId(),

                'email' =>
                    $customer->getEmail(),

                'status' =>
                    $customer->getStatus(),

                'display_name' =>
                    $profile?->getDisplayName()
                    ?? '',

                'area_id' =>
                    $areaId,

                'municipality_id' =>
                    $profile?->getMunicipalityId(),

                'avatar_attachment_id' =>
                    $profile?->getAvatarAttachmentId(),

                'phone' =>
                    $profile?->getPhone(),

                'allow_phone_calls' =>
                    $profile?->allowsPhoneCalls()
                    ?? false,

                'allow_whatsapp' =>
                    $profile?->allowsWhatsapp()
                    ?? false,

                'has_valid_contact' =>
                    $profile?->hasValidContactMethod()
                    ?? false,
            ];
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudo construir '
                . 'el contexto del cliente: '
                . $exception->getMessage()
            );

            return null;
        }
    }

    /**
     * Completa la información pública del vendedor de un anuncio.
     *
     * El número no se devuelve como texto público.
     *
     * Solo se exponen:
     *
     * - URL tel:, cuando acepta llamadas;
     * - URL wa.me, cuando acepta WhatsApp;
     * - indicadores de métodos autorizados.
     *
     * @param mixed $sellerData
     *
     * @return array<string, mixed>
     */
    public static function resolveAdvertisementSeller(
        mixed $sellerData,
        int $advertisementId
    ): array {
        $sellerData =
            is_array($sellerData)
                ? $sellerData
                : [];

        $customerId =
            max(
                0,
                (int) (
                    $sellerData[
                        'customer_id'
                    ]
                    ?? 0
                )
            );

        if ($customerId <= 0) {
            return self::emptySellerData(
                $sellerData
            );
        }

        try {
            $customerRepository =
                new CustomerRepository();

            $customer =
                $customerRepository->findById(
                    $customerId
                );

            if ($customer === null) {
                return self::emptySellerData(
                    $sellerData
                );
            }

            $profileRepository =
                new CustomerProfileRepository();

            $profile =
                $profileRepository
                    ->findByCustomerId(
                        $customerId
                    );

            if ($profile === null) {
                return self::emptySellerData(
                    $sellerData
                );
            }

            $phoneCallUrl =
                $profile->getPhoneCallUrl()
                ?? '';

            $whatsappUrl =
                $profile->getWhatsappUrl()
                ?? '';

            /*
             * Añade al enlace de WhatsApp un mensaje
             * personalizado con el título del anuncio.
             */
            if ($whatsappUrl !== '') {
                $advertisementTitle =
                    self::resolveAdvertisementTitle(
                        $advertisementId
                    );

                $message =
                    self::buildWhatsappMessage(
                        $advertisementTitle
                    );

                $whatsappUrl =
                    add_query_arg(
                        [
                            'text' =>
                                $message,
                        ],
                        $whatsappUrl
                    );
            }

            $avatarUrl =
                '';

            $avatarAttachmentId =
                $profile
                    ->getAvatarAttachmentId();

            if (
                $avatarAttachmentId !== null
                && $avatarAttachmentId > 0
            ) {
                $resolvedAvatarUrl =
                    wp_get_attachment_image_url(
                        $avatarAttachmentId,
                        'thumbnail'
                    );

                if (
                    is_string(
                        $resolvedAvatarUrl
                    )
                ) {
                    $avatarUrl =
                        $resolvedAvatarUrl;
                }
            }

            $profileUrl =
                (string) apply_filters(
                    'dsm_customer_public_profile_url',
                    '',
                    $customerId
                );

            return array_merge(
                $sellerData,
                [
                    'customer_id' =>
                        $customerId,

                    'display_name' =>
                        $profile
                            ->getDisplayName(),

                    'avatar_url' =>
                        $avatarUrl,

                    'profile_url' =>
                        esc_url_raw(
                            $profileUrl
                        ),

                    /*
                     * Se deja vacío hasta que el modelo Customer
                     * exponga una fecha pública de alta estable.
                     */
                    'member_since' =>
                        (string) (
                            $sellerData[
                                'member_since'
                            ]
                            ?? ''
                        ),

                    'rating_average' =>
                        $sellerData[
                            'rating_average'
                        ]
                        ?? null,

                    'rating_count' =>
                        max(
                            0,
                            (int) (
                                $sellerData[
                                    'rating_count'
                                ]
                                ?? 0
                            )
                        ),

                    'allows_phone_calls' =>
                        $profile
                            ->allowsPhoneCalls(),

                    'allows_whatsapp' =>
                        $profile
                            ->allowsWhatsapp(),

                    'has_valid_contact' =>
                        $profile
                            ->hasValidContactMethod(),

                    'phone_call_url' =>
                        esc_url_raw(
                            $phoneCallUrl
                        ),

                    'whatsapp_url' =>
                        esc_url_raw(
                            $whatsappUrl
                        ),
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Clientes] No se pudo construir '
                    . 'el vendedor público del anuncio %d: %s',
                    $advertisementId,
                    $exception->getMessage()
                )
            );

            return self::emptySellerData(
                $sellerData
            );
        }
    }

    /**
     * Recupera únicamente el título necesario para generar
     * el mensaje inicial de WhatsApp.
     */
    private static function resolveAdvertisementTitle(
        int $advertisementId
    ): string {
        if ($advertisementId <= 0) {
            return '';
        }

        global $wpdb;

        $tableName =
            $wpdb->prefix
            . 'dsm_ads';

        $sql =
            $wpdb->prepare(
                "
                SELECT title
                FROM {$tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $advertisementId
            );

        if (!is_string($sql)) {
            return '';
        }

        $title =
            $wpdb->get_var(
                $sql
            );

        return is_string($title)
            ? sanitize_text_field(
                $title
            )
            : '';
    }

    /**
     * Construye el mensaje inicial que recibirá el vendedor.
     */
    private static function buildWhatsappMessage(
        string $advertisementTitle
    ): string {
        if ($advertisementTitle === '') {
            return __(
                'Hola, he visto tu anuncio en DeSegundaMuda y me gustaría recibir más información.',
                'dsm-clientes'
            );
        }

        return sprintf(
            __(
                'Hola, he visto tu anuncio "%s" en DeSegundaMuda y me gustaría recibir más información.',
                'dsm-clientes'
            ),
            $advertisementTitle
        );
    }

    /**
     * Devuelve una estructura pública segura cuando el vendedor
     * o su perfil no pueden recuperarse.
     *
     * @param array<string, mixed> $sellerData
     *
     * @return array<string, mixed>
     */
    private static function emptySellerData(
        array $sellerData
    ): array {
        return array_merge(
            $sellerData,
            [
                'allows_phone_calls' =>
                    false,

                'allows_whatsapp' =>
                    false,

                'has_valid_contact' =>
                    false,

                'phone_call_url' =>
                    '',

                'whatsapp_url' =>
                    '',
            ]
        );
    }

    private function __construct()
    {
    }
}