<?php

declare(strict_types=1);

namespace DSM\Catalogo\Admin\Variant;

use DSM\Catalogo\Admin\VariantAdminController;
use RuntimeException;
use Throwable;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activa o desactiva una variante del catálogo.
 *
 * Reglas:
 *
 * - La variante debe existir.
 * - Una variante archivada no puede activarse.
 * - Una variante predeterminada no puede quedar inactiva.
 * - La operación actualiza updated_at.
 * - Después de actuar vuelve al listado conservando el producto.
 */
final class VariantStatusAction
{
    private const CAPABILITY =
        'manage_options';

    private wpdb $database;

    private string $variantsTable;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;

        $this->variantsTable =
            $wpdb->prefix
            . 'dsm_product_variants';
    }

    /**
     * Procesa la petición de cambio de estado.
     */
    public function handle(): void
    {
        $this->assertPermission();

        $variantId =
            $this->getVariantId();

        check_admin_referer(
            VariantAdminController::
                getNonceAction()
                . '_status_'
                . $variantId,

            VariantAdminController::
                getNonceField()
        );

        $requestedActive =
            $this->getRequestedActive();

        $productId = 0;

        try {
            $variant =
                $this->findVariant(
                    $variantId
                );

            if ($variant === null) {
                throw new RuntimeException(
                    'No se encontró la variante.'
                );
            }

            $productId =
                (int) $variant[
                    'product_id'
                ];

            $isArchived =
                !empty(
                    $variant[
                        'archived_at'
                    ]
                );

            $isDefault =
                (int) (
                    $variant[
                        'is_default'
                    ]
                    ?? 0
                ) === 1;

            $currentActive =
                (int) (
                    $variant[
                        'is_active'
                    ]
                    ?? 0
                ) === 1;

            if (
                $requestedActive
                && $isArchived
            ) {
                throw new RuntimeException(
                    'No se puede activar una variante archivada.'
                );
            }

            if (
                !$requestedActive
                && $isDefault
            ) {
                throw new RuntimeException(
                    'No se puede desactivar la variante predeterminada. Selecciona primero otra variante como predeterminada.'
                );
            }

            /*
             * Si el estado solicitado ya coincide con el actual,
             * consideramos la operación correcta e idempotente.
             */
            if (
                $currentActive
                !== $requestedActive
            ) {
                $this->updateStatus(
                    $variantId,
                    $requestedActive
                );
            }

            do_action(
                'dsm_catalogo_variant_status_changed',
                $variantId,
                $productId,
                $requestedActive,
                get_current_user_id()
            );

            do_action(
                'dsm_audit_event',
                'catalog.variant_status_changed',
                [
                    'variant_id' =>
                        $variantId,

                    'product_id' =>
                        $productId,

                    'is_active' =>
                        $requestedActive,

                    'actor_type' =>
                        'wordpress_user',

                    'actor_id' =>
                        get_current_user_id(),
                ]
            );

            $status =
                $requestedActive
                    ? 'variant_activated'
                    : 'variant_deactivated';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Catálogo] Error cambiando el estado de la variante '
                . $variantId
                . ': '
                . $exception->getMessage()
            );

            $status =
                'status_error';
        }

        $this->redirect(
            $productId,
            $status
        );
    }

    /**
     * Obtiene una variante para validar la operación.
     *
     * @return array<string, mixed>|null
     */
    private function findVariant(
        int $variantId
    ): ?array {
        if ($variantId <= 0) {
            return null;
        }

        $sql =
            $this->database->prepare(
                "
                SELECT
                    id,
                    product_id,
                    is_default,
                    is_active,
                    archived_at
                FROM {$this->variantsTable}
                WHERE id = %d
                LIMIT 1
                ",
                $variantId
            );

        if (!is_string($sql)) {
            return null;
        }

        $variant =
            $this->database->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($variant)
            ? $variant
            : null;
    }

    /**
     * Actualiza el estado de la variante.
     */
    private function updateStatus(
        int $variantId,
        bool $active
    ): void {
        $result =
            $this->database->update(
                $this->variantsTable,
                [
                    'is_active' =>
                        $active
                            ? 1
                            : 0,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'id' =>
                        $variantId,
                ],
                [
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo actualizar la variante: %s',
                    $this->database->last_error
                )
            );
        }
    }

    /**
     * Obtiene el ID enviado en la URL.
     */
    private function getVariantId(): int
    {
        $variantId =
            isset($_GET['variant_id'])
                ? absint(
                    wp_unslash(
                        (string) $_GET[
                            'variant_id'
                        ]
                    )
                )
                : 0;

        if ($variantId <= 0) {
            wp_die(
                esc_html__(
                    'No se ha indicado una variante válida.',
                    'dsm-catalogo'
                ),
                esc_html__(
                    'Solicitud no válida',
                    'dsm-catalogo'
                ),
                [
                    'response' => 400,
                ]
            );
        }

        return $variantId;
    }

    /**
     * Obtiene el estado solicitado.
     */
    private function getRequestedActive(): bool
    {
        if (!isset($_GET['is_active'])) {
            wp_die(
                esc_html__(
                    'No se ha indicado el estado de la variante.',
                    'dsm-catalogo'
                ),
                esc_html__(
                    'Solicitud no válida',
                    'dsm-catalogo'
                ),
                [
                    'response' => 400,
                ]
            );
        }

        $value =
            sanitize_key(
                wp_unslash(
                    (string) $_GET[
                        'is_active'
                    ]
                )
            );

        if (
            !in_array(
                $value,
                [
                    '0',
                    '1',
                ],
                true
            )
        ) {
            wp_die(
                esc_html__(
                    'El estado solicitado no es válido.',
                    'dsm-catalogo'
                ),
                esc_html__(
                    'Solicitud no válida',
                    'dsm-catalogo'
                ),
                [
                    'response' => 400,
                ]
            );
        }

        return $value === '1';
    }

    /**
     * Redirige al listado conservando el producto.
     */
    private function redirect(
        int $productId,
        string $status
    ): never {
        $arguments = [
            'page' =>
                VariantAdminController::
                    getMenuSlug(),

            'dsm_status' =>
                $status,
        ];

        if ($productId > 0) {
            $arguments[
                'product_id'
            ] = $productId;
        }

        wp_safe_redirect(
            add_query_arg(
                $arguments,
                admin_url('admin.php')
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
                'No tienes permisos para cambiar el estado de variantes.',
                'dsm-catalogo'
            ),
            esc_html__(
                'Acceso denegado',
                'dsm-catalogo'
            ),
            [
                'response' => 403,
            ]
        );
    }
}