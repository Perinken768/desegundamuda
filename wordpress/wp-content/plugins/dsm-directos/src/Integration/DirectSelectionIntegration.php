<?php

declare(strict_types=1);

namespace DSM\Directos\Integration;

use DSM\Directos\Application\DirectAccessService;
use DSM\Directos\Direct\DirectItemRepository;
use DSM\Directos\Direct\DirectRepository;
use DSM\Directos\Frontend\DirectSelectionController;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectSelectionIntegration
{
    public static function register(): void
    {
        $integration =
            new self();

        /*
         * =====================================================
         * MIS ANUNCIOS
         * =====================================================
         */

        add_action(
            'wp_enqueue_scripts',
            [
                $integration,
                'enqueueAssets',
            ]
        );

        add_action(
            'dsm_customer_advertisements_header_actions',
            [
                $integration,
                'renderBackToDirect',
            ],
            20,
            1
        );

        add_action(
            'dsm_store_inventory_header_actions',
            [
                $integration,
                'renderBackToDirect',
            ],
            20,
            1
        );

        add_action(
            'dsm_customer_advertisement_actions',
            [
                $integration,
                'renderAdvertisementAction',
            ],
            20,
            2
        );

        /*
         * =====================================================
         * INVENTARIO
         * =====================================================
         */

        add_action(
            'dsm_store_inventory_direct_header',
            [
                $integration,
                'renderInventoryHeader',
            ]
        );

        add_action(
            'dsm_store_inventory_direct_cell',
            [
                $integration,
                'renderInventoryCell',
            ],
            10,
            1
        );
    }

    public function enqueueAssets(): void
    {
        if (
            !is_page('mi-tienda')
            && !is_page('mis-anuncios')
            && !is_page('mis-directos')
            && !is_page('directo')
            && !is_page('directos')
        ) {
            return;
        }

        $stylePath =
            DSM_DIRECTOS_PATH
            . 'assets/public/css/directos.css';

        wp_enqueue_style(
            'dsm-directos-public',
            DSM_DIRECTOS_URL
                . 'assets/public/css/directos.css',
            [],
            is_file($stylePath)
                ? (string) filemtime($stylePath)
                : DSM_DIRECTOS_VERSION
        );

        if (
            is_page('mi-tienda')
            || is_page('mis-anuncios')
        ) {
            wp_enqueue_script(
                'dsm-directos-selection',
                DSM_DIRECTOS_URL
                    . 'assets/public/js/direct-selection.js',
                [],
                DSM_DIRECTOS_VERSION,
                true
            );

            wp_localize_script(
                'dsm-directos-selection',
                'dsmDirectosSelection',
                [
                    'ajaxUrl' =>
                        admin_url(
                            'admin-ajax.php'
                        ),
                ]
            );
        }

        if (is_page('directo')) {
            $reservationScriptPath =
                DSM_DIRECTOS_PATH
                . 'assets/public/js/direct-reservation.js';

            wp_enqueue_script(
                'dsm-directos-reservation',
                DSM_DIRECTOS_URL
                    . 'assets/public/js/direct-reservation.js',
                [],
                is_file(
                    $reservationScriptPath
                )
                    ? (string) filemtime(
                        $reservationScriptPath
                    )
                    : DSM_DIRECTOS_VERSION,
                true
            );

            wp_localize_script(
                'dsm-directos-reservation',
                'dsmDirectReservation',
                [
                    'ajaxUrl' =>
                        admin_url(
                            'admin-ajax.php'
                        ),
                ]
            );
        }
    }

    public function renderBackToDirect(
        mixed $customerId = null
    ): void {
        if ($customerId === null) {
            $customer =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            $customerId =
                is_array($customer)
                    ? max(
                        0,
                        (int) (
                            $customer['id']
                            ?? 0
                        )
                    )
                    : 0;
        } else {
            $customerId =
                max(
                    0,
                    (int) $customerId
                );
        }

        if ($customerId <= 0) {
            return;
        }

        try {
            if (
                !(
                    new DirectAccessService()
                )->hasAccess(
                    $customerId
                )
            ) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        ?>
        <a
            class="
                dsm-button
                dsm-button--secondary
                dsm-directos-back-button
            "
            href="<?php
            echo esc_url(
                home_url(
                    '/mis-directos/'
                )
            );
            ?>"
        >
            Mi directo
        </a>
        <?php
    }

    public function renderAdvertisementAction(
        mixed $advertisement,
        mixed $customerId
    ): void {
        /*
         * El hook dsm_customer_advertisement_actions
         * recibe el anuncio en formato array.
         *
         * Conservamos también compatibilidad con objeto
         * por si otra integración reutiliza este método.
         */

        $advertisementId = 0;
        $status = '';

        if (is_array($advertisement)) {
            $advertisementId =
                max(
                    0,
                    (int) (
                        $advertisement['id']
                        ?? 0
                    )
                );

            $status =
                sanitize_key(
                    (string) (
                        $advertisement['status']
                        ?? ''
                    )
                );
        } elseif (
            is_object($advertisement)
            && method_exists(
                $advertisement,
                'getId'
            )
            && method_exists(
                $advertisement,
                'getStatus'
            )
        ) {
            $advertisementId =
                max(
                    0,
                    (int) $advertisement
                        ->getId()
                );

            $status =
                sanitize_key(
                    (string) $advertisement
                        ->getStatus()
                );
        }

        $customerId =
            max(
                0,
                (int) $customerId
            );

        if (
            $advertisementId <= 0
            || $customerId <= 0
        ) {
            return;
        }

        /*
         * Solo los anuncios ACTIVOS pueden
         * incorporarse a Mi directo.
         */
        if ($status !== 'active') {
            return;
        }

        try {
            $access =
                new DirectAccessService();

            if (
                !$access->hasAccess(
                    $customerId
                )
            ) {
                return;
            }

            $direct =
                (
                    new DirectRepository()
                )->findByCustomerId(
                    $customerId
                );

            if ($direct === null) {
                return;
            }

            $liveId =
                max(
                    0,
                    (int) (
                        $direct['id']
                        ?? 0
                    )
                );

            if ($liveId <= 0) {
                return;
            }

            $selected =
                (
                    new DirectItemRepository()
                )->hasAdvertisement(
                    $liveId,
                    $advertisementId
                );

            $this->renderToggleForm(
                action:
                    DirectSelectionController::
                        ADVERTISEMENT_ACTION,

                idName:
                    'advertisement_id',

                itemId:
                    $advertisementId,

                nonceAction:
                    DirectSelectionController::
                        getAdvertisementNonceAction(
                            $advertisementId
                        ),

                label:
                    $selected
                        ? 'Quitar del directo'
                        : 'Añadir al directo',

                extraButtonClass:
                    'dsm-directos-advertisement-toggle'
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Directos] Error mostrando acción de anuncio '
                . $advertisementId
                . ': '
                . $exception->getMessage()
            );
        }
    }

    public function renderInventoryHeader(): void
    {
        echo '<th class="is-action">';
        echo esc_html__(
            'Directo',
            'dsm-directos'
        );
        echo '</th>';
    }

    public function renderInventoryCell(
        mixed $row
    ): void {
        if (!is_array($row)) {
            return;
        }

        $variantId =
            max(
                0,
                (int) (
                    $row['variant_id']
                    ?? 0
                )
            );

        echo '<td data-label="Directo" class="is-action">';

        /*
         * Producto sin variantes:
         * no puede añadirse al directo.
         */
        if ($variantId <= 0) {
            echo '—';
            echo '</td>';
            return;
        }

        $customer =
            apply_filters(
                'dsm_current_customer_context',
                null
            );

        $customerId =
            is_array($customer)
                ? max(
                    0,
                    (int) (
                        $customer['id']
                        ?? 0
                    )
                )
                : 0;

        if ($customerId <= 0) {
            echo '—';
            echo '</td>';
            return;
        }

        $isActive =
            (int) (
                $row['is_active']
                ?? 0
            ) === 1;

        $tracksStock =
            (int) (
                $row['track_stock']
                ?? 0
            ) === 1;

        $availableStock =
            max(
                0,
                (int) (
                    $row['stock_quantity']
                    ?? 0
                )
                - (int) (
                    $row['stock_reserved']
                    ?? 0
                )
            );

        try {
            $access =
                new DirectAccessService();

            if (
                !$access->canUseInventory(
                    $customerId
                )
            ) {
                echo '—';
                echo '</td>';
                return;
            }

            $direct =
                (
                    new DirectRepository()
                )->findByCustomerId(
                    $customerId
                );

            if ($direct === null) {
                echo '—';
                echo '</td>';
                return;
            }

            $liveId =
                max(
                    0,
                    (int) (
                        $direct['id']
                        ?? 0
                    )
                );

            if ($liveId <= 0) {
                echo '—';
                echo '</td>';
                return;
            }

            $selected =
                (
                    new DirectItemRepository()
                )->hasVariant(
                    $liveId,
                    $variantId
                );

            /*
             * Si ya estaba incluida permitimos quitarla
             * aunque posteriormente se quede sin stock.
             */
            if (
                !$selected
                && (
                    !$isActive
                    || !$tracksStock
                    || $availableStock <= 0
                )
            ) {
                echo '—';
                echo '</td>';
                return;
            }

            $this->renderToggleForm(
                action:
                    DirectSelectionController::
                        VARIANT_ACTION,

                idName:
                    'variant_id',

                itemId:
                    $variantId,

                nonceAction:
                    DirectSelectionController::
                        getVariantNonceAction(
                            $variantId
                        ),

                label:
                    $selected
                        ? 'Quitar'
                        : 'Añadir',

                /*
                 * Misma clase que el botón Gestionar.
                 * Así tienen exactamente la misma escala.
                 */
                extraButtonClass:
                    'dsm-store-inventory__manage '
                    . 'dsm-directos-inventory-toggle',

                redirectAnchor:
                    'dsm-inventory-row-'
                    . $variantId
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Directos] Error mostrando variante en directo: '
                . $exception->getMessage()
            );

            echo '—';
        }

        echo '</td>';
    }

    private function renderToggleForm(
        string $action,
        string $idName,
        int $itemId,
        string $nonceAction,
        string $label,
        string $extraButtonClass = '',
        string $redirectAnchor = ''
    ): void {
        $requestUri =
            isset($_SERVER['REQUEST_URI'])
                ? wp_unslash(
                    (string) $_SERVER[
                        'REQUEST_URI'
                    ]
                )
                : '/';

        $redirect =
            home_url(
                $requestUri
            );

        if ($redirectAnchor !== '') {
            $redirect .= '#'
                . rawurlencode(
                    $redirectAnchor
                );
        }

        ?>
        <form
            method="post"
            action="<?php
            echo esc_url(
                admin_url(
                    'admin-post.php'
                )
            );
            ?>"
            class="dsm-directos-toggle-form"
        >

            <input
                type="hidden"
                name="action"
                value="<?php
                echo esc_attr(
                    $action
                );
                ?>"
            >

            <input
                type="hidden"
                name="<?php
                echo esc_attr(
                    $idName
                );
                ?>"
                value="<?php
                echo esc_attr(
                    (string) $itemId
                );
                ?>"
            >

            <input
                type="hidden"
                name="redirect_to"
                value="<?php
                echo esc_attr(
                    $redirect
                );
                ?>"
            >

            <?php
            wp_nonce_field(
                $nonceAction,
                DirectSelectionController::
                    NONCE_FIELD
            );
            ?>

            <button
                type="submit"
                class="<?php
                echo esc_attr(
                    trim(
                        'dsm-button '
                        . 'dsm-button--secondary '
                        . $extraButtonClass
                    )
                );
                ?>"
            >
                <?php
                echo esc_html(
                    $label
                );
                ?>
            </button>

        </form>
        <?php
    }

    private function __construct()
    {
    }
}
