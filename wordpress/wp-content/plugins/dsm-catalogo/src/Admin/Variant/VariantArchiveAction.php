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
 * Archiva una variante del catálogo.
 *
 * Reglas:
 *
 * - La variante debe existir.
 * - La operación es idempotente.
 * - No puede archivarse si mantiene stock reservado.
 * - Al archivarla queda inactiva.
 * - Deja de ser predeterminada.
 * - Si era la predeterminada, se selecciona otra variante
 *   activa y no archivada del mismo producto.
 * - Si no existe sustituta, se impide archivar.
 * - Todo el proceso se ejecuta en una transacción.
 */
final class VariantArchiveAction
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
     * Procesa la petición de archivado.
     */
    public function handle(): void
    {
        $this->assertPermission();

        $variantId =
            $this->getVariantId();

        check_admin_referer(
            VariantAdminController::
                getNonceAction()
                . '_archive_'
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

            /*
             * Operación idempotente.
             *
             * Si ya estaba archivada, no realizamos escrituras
             * adicionales y devolvemos un resultado correcto.
             */
            if (
                !empty(
                    $variant[
                        'archived_at'
                    ]
                )
            ) {
                $this->redirect(
                    $productId,
                    'variant_archived'
                );
            }

            $stockReserved =
                max(
                    0,
                    (int) (
                        $variant[
                            'stock_reserved'
                        ]
                        ?? 0
                    )
                );

            if ($stockReserved > 0) {
                throw new RuntimeException(
                    'No se puede archivar una variante con stock reservado. Libera o completa primero sus reservas.'
                );
            }

            $this->archiveVariant(
                $variantId,
                $productId
            );

            do_action(
                'dsm_catalogo_variant_archived',
                $variantId,
                $productId,
                get_current_user_id()
            );

            do_action(
                'dsm_audit_event',
                'catalog.variant_archived',
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
                'variant_archived';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Catálogo] Error archivando la variante '
                . $variantId
                . ': '
                . $exception->getMessage()
            );

            $status =
                'archive_error';
        }

        $this->redirect(
            $productId,
            $status
        );
    }

    /**
     * Busca la variante.
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
                    stock_quantity,
                    stock_reserved,
                    is_default,
                    is_active,
                    sort_order,
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
     * Archiva la variante dentro de una transacción.
     */
    private function archiveVariant(
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
            /*
             * Bloqueamos todas las variantes del producto
             * para evitar cambios simultáneos en la variante
             * predeterminada.
             */
            $lockSql =
                $this->database->prepare(
                    "
                    SELECT
                        id,
                        stock_reserved,
                        is_default,
                        is_active,
                        archived_at
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

            $variants =
                $this->database->get_results(
                    $lockSql,
                    ARRAY_A
                );

            if (
                !is_array($variants)
                || $variants === []
            ) {
                throw new RuntimeException(
                    'No existen variantes para el producto.'
                );
            }

            $selectedVariant = null;

            foreach ($variants as $variant) {
                if (
                    (int) $variant['id']
                    === $variantId
                ) {
                    $selectedVariant =
                        $variant;

                    break;
                }
            }

            if ($selectedVariant === null) {
                throw new RuntimeException(
                    'La variante no pertenece al producto indicado.'
                );
            }

            if (
                !empty(
                    $selectedVariant[
                        'archived_at'
                    ]
                )
            ) {
                $this->commit();

                return;
            }

            if (
                (int) (
                    $selectedVariant[
                        'stock_reserved'
                    ]
                    ?? 0
                ) > 0
            ) {
                throw new RuntimeException(
                    'La variante mantiene stock reservado.'
                );
            }

            $wasDefault =
                (int) (
                    $selectedVariant[
                        'is_default'
                    ]
                    ?? 0
                ) === 1;

            $replacementId = 0;

            if ($wasDefault) {
                $replacementId =
                    $this->findReplacementVariantId(
                        $variants,
                        $variantId
                    );

                if ($replacementId <= 0) {
                    throw new RuntimeException(
                        'No se puede archivar la única variante disponible o predeterminada del producto. Crea o activa otra variante primero.'
                    );
                }
            }

            $now =
                current_time(
                    'mysql',
                    true
                );

            /*
             * Archivamos y desactivamos la variante.
             */
            $archiveSql =
                $this->database->prepare(
                    "
                    UPDATE {$this->variantsTable}
                    SET
                        is_active = 0,
                        is_default = 0,
                        archived_at = %s,
                        updated_at = %s
                    WHERE id = %d
                        AND product_id = %d
                        AND archived_at IS NULL
                    ",
                    $now,
                    $now,
                    $variantId,
                    $productId
                );

            if (!is_string($archiveSql)) {
                throw new RuntimeException(
                    'No se pudo preparar el archivado.'
                );
            }

            $archived =
                $this->database->query(
                    $archiveSql
                );

            if ($archived === false) {
                throw new RuntimeException(
                    'No se pudo archivar la variante: '
                    . $this->database->last_error
                );
            }

            if ($archived !== 1) {
                throw new RuntimeException(
                    'La variante no pudo archivarse.'
                );
            }

            /*
             * Si era predeterminada, marcamos la sustituta.
             */
            if (
                $wasDefault
                && $replacementId > 0
            ) {
                $replacementSql =
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
                        $replacementId,
                        $productId
                    );

                if (
                    !is_string(
                        $replacementSql
                    )
                ) {
                    throw new RuntimeException(
                        'No se pudo preparar la variante sustituta.'
                    );
                }

                $replacementUpdated =
                    $this->database->query(
                        $replacementSql
                    );

                if ($replacementUpdated === false) {
                    throw new RuntimeException(
                        'No se pudo establecer la nueva variante predeterminada: '
                        . $this->database->last_error
                    );
                }

                if ($replacementUpdated !== 1) {
                    throw new RuntimeException(
                        'No se encontró una variante válida para sustituir a la archivada.'
                    );
                }
            }

            $this->commit();
        } catch (Throwable $exception) {
            $this->database->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    /**
     * Selecciona una sustituta para la variante predeterminada.
     *
     * Prioridad:
     *
     * 1. Variante activa y no archivada.
     * 2. Menor sort_order.
     * 3. Menor ID.
     *
     * @param array<int, array<string, mixed>> $variants
     */
    private function findReplacementVariantId(
        array $variants,
        int $excludedVariantId
    ): int {
        $candidates =
            array_filter(
                $variants,
                static function (
                    array $variant
                ) use (
                    $excludedVariantId
                ): bool {
                    return
                        (int) (
                            $variant['id']
                            ?? 0
                        ) !== $excludedVariantId

                        && empty(
                            $variant[
                                'archived_at'
                            ]
                        )

                        && (int) (
                            $variant[
                                'is_active'
                            ]
                            ?? 0
                        ) === 1;
                }
            );

        if ($candidates === []) {
            return 0;
        }

        usort(
            $candidates,
            static function (
                array $left,
                array $right
            ): int {
                $leftOrder =
                    (int) (
                        $left[
                            'sort_order'
                        ]
                        ?? 0
                    );

                $rightOrder =
                    (int) (
                        $right[
                            'sort_order'
                        ]
                        ?? 0
                    );

                if (
                    $leftOrder
                    !== $rightOrder
                ) {
                    return $leftOrder
                        <=> $rightOrder;
                }

                return
                    (int) (
                        $left['id']
                        ?? 0
                    )
                    <=>
                    (int) (
                        $right['id']
                        ?? 0
                    );
            }
        );

        return (int) (
            $candidates[0]['id']
            ?? 0
        );
    }

    /**
     * Confirma la transacción.
     */
    private function commit(): void
    {
        $committed =
            $this->database->query(
                'COMMIT'
            );

        if ($committed === false) {
            throw new RuntimeException(
                'No se pudo confirmar la transacción.'
            );
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
                'No tienes permisos para archivar variantes.',
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