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
 * Establece una variante como predeterminada.
 *
 * Reglas:
 *
 * - La variante debe existir.
 * - No puede estar archivada.
 * - La variante seleccionada queda activa.
 * - Todas las demás variantes del producto dejan
 *   de ser predeterminadas.
 * - Solo queda una variante predeterminada por producto.
 * - La operación se realiza dentro de una transacción.
 */
final class VariantDefaultAction
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
     * Procesa la petición.
     */
    public function handle(): void
    {
        $this->assertPermission();

        $variantId =
            $this->getVariantId();

        check_admin_referer(
            VariantAdminController::
                getNonceAction()
                . '_default_'
                . $variantId,

            VariantAdminController::
                getNonceField()
        );

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

            if (
                !empty(
                    $variant[
                        'archived_at'
                    ]
                )
            ) {
                throw new RuntimeException(
                    'No se puede establecer como predeterminada una variante archivada.'
                );
            }

            $this->setAsDefault(
                $variantId,
                $productId
            );

            do_action(
                'dsm_catalogo_variant_default_changed',
                $variantId,
                $productId,
                get_current_user_id()
            );

            do_action(
                'dsm_audit_event',
                'catalog.variant_default_changed',
                [
                    'variant_id' =>
                        $variantId,

                    'product_id' =>
                        $productId,

                    'actor_type' =>
                        'wordpress_user',

                    'actor_id' =>
                        get_current_user_id(),
                ]
            );

            $status =
                'variant_defaulted';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Catálogo] Error estableciendo la variante predeterminada '
                . $variantId
                . ': '
                . $exception->getMessage()
            );

            $status =
                'default_error';
        }

        $this->redirect(
            $productId,
            $status
        );
    }

    /**
     * Busca la variante seleccionada.
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
     * Cambia la variante predeterminada de forma atómica.
     */
    private function setAsDefault(
        int $variantId,
        int $productId
    ): void {
        if ($variantId <= 0) {
            throw new RuntimeException(
                'El identificador de la variante no es válido.'
            );
        }

        if ($productId <= 0) {
            throw new RuntimeException(
                'El identificador del producto no es válido.'
            );
        }

        $transactionStarted =
            $this->database->query(
                'START TRANSACTION'
            );

        if ($transactionStarted === false) {
            throw new RuntimeException(
                'No se pudo iniciar la transacción.'
            );
        }

        try {
            $now =
                current_time(
                    'mysql',
                    true
                );

            /*
             * Bloqueamos las variantes del producto durante
             * el cambio para evitar dos predeterminadas si
             * se reciben peticiones simultáneas.
             */
            $lockSql =
                $this->database->prepare(
                    "
                    SELECT id
                    FROM {$this->variantsTable}
                    WHERE product_id = %d
                    FOR UPDATE
                    ",
                    $productId
                );

            if (!is_string($lockSql)) {
                throw new RuntimeException(
                    'No se pudo preparar el bloqueo de variantes.'
                );
            }

            $lockedRows =
                $this->database->get_col(
                    $lockSql
                );

            if (
                !is_array($lockedRows)
                || $lockedRows === []
            ) {
                throw new RuntimeException(
                    'El producto no tiene variantes disponibles.'
                );
            }

            /*
             * Quitamos la marca predeterminada de todas
             * las variantes del producto.
             */
            $clearSql =
                $this->database->prepare(
                    "
                    UPDATE {$this->variantsTable}
                    SET
                        is_default = 0,
                        updated_at = %s
                    WHERE product_id = %d
                        AND is_default <> 0
                    ",
                    $now,
                    $productId
                );

            if (!is_string($clearSql)) {
                throw new RuntimeException(
                    'No se pudo preparar la actualización de variantes.'
                );
            }

            $cleared =
                $this->database->query(
                    $clearSql
                );

            if ($cleared === false) {
                throw new RuntimeException(
                    'No se pudieron actualizar las variantes anteriores: '
                    . $this->database->last_error
                );
            }

            /*
             * Marcamos la seleccionada y la activamos.
             *
             * archived_at IS NULL evita que pueda establecerse
             * si fue archivada entre la lectura y la escritura.
             */
            $setSql =
                $this->database->prepare(
                    "
                    UPDATE {$this->variantsTable}
                    SET
                        is_default = 1,
                        is_active = 1,
                        updated_at = %s
                    WHERE id = %d
                        AND product_id = %d
                        AND archived_at IS NULL
                    ",
                    $now,
                    $variantId,
                    $productId
                );

            if (!is_string($setSql)) {
                throw new RuntimeException(
                    'No se pudo preparar la variante predeterminada.'
                );
            }

            $updated =
                $this->database->query(
                    $setSql
                );

            if ($updated === false) {
                throw new RuntimeException(
                    'No se pudo establecer la variante predeterminada: '
                    . $this->database->last_error
                );
            }

            if ($updated !== 1) {
                throw new RuntimeException(
                    'La variante no pudo establecerse como predeterminada.'
                );
            }

            $committed =
                $this->database->query(
                    'COMMIT'
                );

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción.'
                );
            }
        } catch (Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    /**
     * Obtiene el ID enviado por la URL.
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
     * Redirige al listado manteniendo el producto.
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
                'No tienes permisos para cambiar la variante predeterminada.',
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