<?php

declare(strict_types=1);

namespace DSM\Clientes\Frontend;

use DSM\Clientes\Application\UpdateCustomerProfile;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Profile\CustomerProfileRepository;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procesa la actualización pública del perfil del cliente.
 *
 * Para el MVP:
 *
 * - phone es el único número de contacto;
 * - allow_phone_calls habilita las llamadas;
 * - allow_whatsapp habilita WhatsApp;
 * - whatsapp_phone permanece reservado y no se procesa;
 * - area_id identifica la división territorial seleccionada;
 * - municipality_id identifica el municipio del área.
 */
final class ProfileController
{
    public const ACTION =
        'dsm_customer_profile_update';

    public const NONCE_ACTION =
        'dsm_customer_profile_update';

    public const NONCE_FIELD =
        'dsm_profile_nonce';

    public static function register(): void
    {
        add_action(
            'admin_post_nopriv_'
            . self::ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );

        add_action(
            'admin_post_'
            . self::ACTION,
            [
                self::class,
                'handleUpdate',
            ]
        );
    }

    public static function handleUpdate(): void
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_FIELD
        );

        $authenticatedCustomer =
            new AuthenticatedCustomer(
                new CustomerSessionRepository(),
                new CustomerRepository()
            );

        $customer =
            $authenticatedCustomer->resolve();

        if ($customer === null) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        $displayName =
            isset($_POST['display_name'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST[
                            'display_name'
                        ]
                    )
                )
                : '';

        $phone =
            isset($_POST['phone'])
                ? sanitize_text_field(
                    wp_unslash(
                        (string) $_POST['phone']
                    )
                )
                : null;

        /*
         * Las casillas no marcadas no se envían en POST.
         */
        $allowPhoneCalls =
            isset(
                $_POST[
                    'allow_phone_calls'
                ]
            )
            && self::normalizeCheckbox(
                $_POST[
                    'allow_phone_calls'
                ]
            );

        $allowWhatsapp =
            isset(
                $_POST[
                    'allow_whatsapp'
                ]
            )
            && self::normalizeCheckbox(
                $_POST[
                    'allow_whatsapp'
                ]
            );

        $bio =
            isset($_POST['bio'])
                ? sanitize_textarea_field(
                    wp_unslash(
                        (string) $_POST['bio']
                    )
                )
                : null;

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

        $municipalityId =
            isset($_POST['municipality_id'])
                ? absint(
                    wp_unslash(
                        (string) $_POST[
                            'municipality_id'
                        ]
                    )
                )
                : 0;

        $areaId =
            $areaId > 0
                ? $areaId
                : null;

        $municipalityId =
            $municipalityId > 0
                ? $municipalityId
                : null;

        try {
            self::validateLocation(
                $areaId,
                $municipalityId
            );

            $profileRepository =
                new CustomerProfileRepository();

            $service =
                new UpdateCustomerProfile(
                    $profileRepository
                );

            /*
             * Actualizamos primero los datos personales
             * y las preferencias de contacto.
             */
            $service->execute(
                $customer->getId(),
                $displayName,
                $phone,
                $allowPhoneCalls,
                $allowWhatsapp,
                $bio
            );

            /*
             * La ubicación se actualiza por separado para
             * mantener desacoplada la lógica territorial.
             */
            $profileRepository
                ->updateLocation(
                    $customer->getId(),
                    $areaId,
                    $municipalityId
                );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'profile_updated' =>
                            '1',
                    ],
                    home_url(
                        '/editar-perfil/'
                    )
                )
            );

            exit;
        } catch (Throwable $exception) {
            error_log(
                sprintf(
                    '[DSM Clientes] Error actualizando perfil del cliente %d: %s',
                    $customer->getId(),
                    $exception->getMessage()
                )
            );

            wp_safe_redirect(
                add_query_arg(
                    [
                        'profile_error' =>
                            self::resolveErrorCode(
                                $exception
                            ),
                    ],
                    home_url(
                        '/editar-perfil/'
                    )
                )
            );

            exit;
        }
    }

    /**
     * Comprueba que el área y el municipio enviados
     * pertenecen al catálogo activo de DSM Ubicaciones.
     */
    private static function validateLocation(
        ?int $areaId,
        ?int $municipalityId
    ): void {
        if (
            $municipalityId !== null
            && $areaId === null
        ) {
            throw new RuntimeException(
                'No se puede seleccionar un municipio sin indicar un área.'
            );
        }

        /*
         * La ubicación puede quedar vacía.
         *
         * Más adelante podremos hacerla obligatoria para
         * determinadas operaciones, como publicar anuncios.
         */
        if ($areaId === null) {
            return;
        }

        $areas =
            apply_filters(
                'dsm_location_areas',
                [],
                null,
                null
            );

        if (!is_array($areas)) {
            throw new RuntimeException(
                'No se pudo consultar el catálogo de áreas.'
            );
        }

        $areaExists =
            false;

        foreach ($areas as $area) {
            if (!is_array($area)) {
                continue;
            }

            if (
                (int) (
                    $area['id']
                    ?? 0
                ) === $areaId
            ) {
                $areaExists =
                    true;

                break;
            }
        }

        if (!$areaExists) {
            throw new RuntimeException(
                'El área seleccionada no es válida o está inactiva.'
            );
        }

        if ($municipalityId === null) {
            return;
        }

        $municipalities =
            apply_filters(
                'dsm_location_municipalities',
                [],
                $areaId
            );

        if (!is_array($municipalities)) {
            throw new RuntimeException(
                'No se pudo consultar el catálogo de municipios.'
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
     * Convierte los valores habituales de una casilla HTML
     * en un booleano seguro.
     */
    private static function normalizeCheckbox(
        mixed $value
    ): bool {
        $value =
            strtolower(
                trim(
                    (string) wp_unslash(
                        $value
                    )
                )
            );

        return in_array(
            $value,
            [
                '1',
                'true',
                'yes',
                'on',
            ],
            true
        );
    }

    /**
     * Devuelve un código breve para mostrar el tipo de error
     * sin exponer detalles internos en la URL pública.
     */
    private static function resolveErrorCode(
        Throwable $exception
    ): string {
        $message =
            strtolower(
                $exception->getMessage()
            );

        if (
            str_contains(
                $message,
                'municipio'
            )
        ) {
            return 'invalid_municipality';
        }

        if (
            str_contains(
                $message,
                'área'
            )
            || str_contains(
                $message,
                'area'
            )
        ) {
            return 'invalid_area';
        }

        if (
            str_contains(
                $message,
                'teléfono'
            )
            || str_contains(
                $message,
                'whatsapp'
            )
        ) {
            return 'invalid_contact';
        }

        return 'invalid_data';
    }

    private function __construct()
    {
    }
}