<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\DirectAccessService;
use DSM\Directos\Direct\DirectRepository;
use DSM\Directos\Direct\DirectItemRepository;
use DSM\Anuncios\Advertisement\AdvertisementRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerDirectsShortcode
{
    public const SHORTCODE =
        'dsm_customer_directs';

    private const DEFAULT_LOGIN_PATH =
        '/iniciar-sesion/';

    private const DEFAULT_DIRECTS_PATH =
        '/mis-directos/';

    public static function register(): void
    {
        add_shortcode(
            self::SHORTCODE,
            [
                self::class,
                'render',
            ]
        );
    }

    public static function render(): string
    {
        try {
            $customerContext =
                apply_filters(
                    'dsm_current_customer_context',
                    null
                );

            $customerId =
                is_array(
                    $customerContext
                )
                    ? max(
                        0,
                        (int) (
                            $customerContext['id']
                            ?? 0
                        )
                    )
                    : 0;

            if ($customerId <= 0) {
                self::redirectToLogin();
            }

            $accessService =
                new DirectAccessService();

            $hasAccess =
                $accessService->hasAccess(
                    $customerId
                );

            $canUseAdvertisements =
                $accessService
                    ->canUseAdvertisements(
                        $customerId
                    );

            $canUseInventory =
                $accessService
                    ->canUseInventory(
                        $customerId
                    );

            $repository =
                new DirectRepository();

            $direct =
                $hasAccess
                    ? $repository
                        ->findByCustomerId(
                            $customerId
                        )
                    : null;

            $directSection =
                isset($_GET['direct_section'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET[
                                'direct_section'
                            ]
                        )
                    )
                    : 'dashboard';

            $allowedSections = [
                'dashboard',
                'configure',
                'items',
            ];

            if (
                !in_array(
                    $directSection,
                    $allowedSections,
                    true
                )
            ) {
                $directSection =
                    'dashboard';
            }

            if (
                $direct === null
                && $directSection === 'items'
            ) {
                $directSection =
                    'configure';
            }

            /*
             * =================================================
             * PREPARAR PRENDAS
             * =================================================
             */

            $availableAdvertisements = [];
            $inventoryRows = [];
            $selectedItems = [];

            if (
                $hasAccess
                && $direct !== null
                && $directSection === 'items'
            ) {
                $advertisementRepository =
                    new AdvertisementRepository();

                $customerAdvertisements =
                    $advertisementRepository
                        ->findByCustomer(
                            $customerId,
                            200,
                            0
                        );

                foreach (
                    $customerAdvertisements
                    as $advertisement
                ) {
                    if (
                        $advertisement->getStatus()
                        !== 'active'
                    ) {
                        continue;
                    }

                    $availableAdvertisements[] =
                        $advertisement;
                }

                $selectedItems =
                    (
                        new DirectItemRepository()
                    )->findByLiveId(
                        (int) $direct['id']
                    );

                if (
                    $canUseInventory
                    && class_exists(
                        '\\DSM\\Multitienda\\Store\\StoreRepository'
                    )
                    && class_exists(
                        '\\DSM\\Catalogo\\Variant\\ProductVariantRepository'
                    )
                ) {
                    $storeRepository =
                        new \DSM\Multitienda\Store\StoreRepository();

                    $store =
                        $storeRepository
                            ->findByCustomerId(
                                $customerId
                            );

                    if ($store !== null) {
                        $variantRepository =
                            new \DSM\Catalogo\Variant\ProductVariantRepository();

                        $inventoryOffset = 0;

                        do {
                            $batch =
                                $variantRepository
                                    ->findInventoryByStore(
                                        storeId:
                                            $store->getId(),

                                        limit:
                                            250,

                                        offset:
                                            $inventoryOffset,

                                        search:
                                            null,

                                        stockStatus:
                                            null
                                    );

                            foreach ($batch as $row) {
                                $variantId =
                                    max(
                                        0,
                                        (int) (
                                            $row['variant_id']
                                            ?? 0
                                        )
                                    );

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

                                $available =
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

                                if (
                                    $variantId <= 0
                                    || !$isActive
                                    || !$tracksStock
                                    || $available <= 0
                                ) {
                                    continue;
                                }

                                $row['available_stock'] =
                                    $available;

                                $inventoryRows[] =
                                    $row;
                            }

                            $inventoryOffset +=
                                count($batch);
                        } while (
                            count($batch) === 250
                        );
                    }
                }
            }

            $notice =
                isset($_GET['notice'])
                    ? sanitize_key(
                        wp_unslash(
                            (string) $_GET['notice']
                        )
                    )
                    : '';

            $error =
                isset($_GET['error'])
                    ? sanitize_text_field(
                        wp_unslash(
                            (string) $_GET['error']
                        )
                    )
                    : '';

            $template =
                DSM_DIRECTOS_PATH
                . 'templates/account/customer-directs.php';

            if (!is_file($template)) {
                throw new \RuntimeException(
                    'No se encontró la plantilla de Mi directo.'
                );
            }

            ob_start();

            require $template;

            $output =
                ob_get_clean();

            return is_string($output)
                ? $output
                : '';
        } catch (Throwable $exception) {
            error_log(
                '[DSM Directos] No se pudo cargar Mi directo: '
                . $exception->getMessage()
            );

            return '<div class="dsm-account-empty-state">'
                . '<p>No se pudo cargar DSM Directos.</p>'
                . '</div>';
        }
    }

    private static function redirectToLogin(): never
    {
        wp_safe_redirect(
            add_query_arg(
                [
                    'redirect_to' =>
                        home_url(
                            self::DEFAULT_DIRECTS_PATH
                        ),
                ],
                home_url(
                    self::DEFAULT_LOGIN_PATH
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
