<?php

declare(strict_types=1);

namespace DSM\Promocionar\Frontend;

use DateTimeImmutable;
use DSM\Clientes\Authentication\AuthenticatedCustomer;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Promocionar\Promotion\PromotionAssignment;
use DSM\Promocionar\Promotion\PromotionAssignmentRepository;
use DSM\Promocionar\Promotion\PromotionWallet;
use DSM\Promocionar\Promotion\PromotionWalletRepository;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerPromotionsShortcode
{
    public const SHORTCODE =
        'dsm_customer_promotions';

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
        $customerId =
            self::resolveAuthenticatedCustomerId();

        if ($customerId <= 0) {
            wp_safe_redirect(
                home_url(
                    '/iniciar-sesion/'
                )
            );

            exit;
        }

        try {
            $walletRepository =
                new PromotionWalletRepository();

            $assignmentRepository =
                new PromotionAssignmentRepository();

            $wallets =
                $walletRepository->findByCustomer(
                    $customerId
                );

            $assignments =
                $assignmentRepository->findByCustomer(
                    $customerId
                );

            $walletsById =
                self::indexWallets(
                    $wallets
                );

            $activePromotions = [];
            $history = [];

            foreach ($assignments as $assignment) {
                $wallet =
                    $walletsById[
                        $assignment->getWalletId()
                    ]
                    ?? null;

                $advertisement =
                    self::resolveAdvertisement(
                        $assignment
                            ->getAdvertisementId()
                    );

                $item = [
                    'assignment' =>
                        $assignment,

                    'wallet' =>
                        $wallet,

                    'advertisement' =>
                        $advertisement,

                    'live_consumed_seconds' =>
                        self::calculateLiveConsumedSeconds(
                            $assignment,
                            $wallet
                        ),

                    'live_remaining_seconds' =>
                        self::calculateLiveRemainingSeconds(
                            $assignment,
                            $wallet
                        ),
                ];

                if ($assignment->isActive()) {
                    $activePromotions[] =
                        $item;
                } else {
                    $history[] =
                        $item;
                }
            }

            $availableWallets =
                array_values(
                    array_filter(
                        $wallets,
                        static fn (
                            PromotionWallet $wallet
                        ): bool =>
                            $wallet->canBeAssigned()
                    )
                );

            return self::renderTemplate(
                [
                    'customerId' =>
                        $customerId,

                    'wallets' =>
                        $wallets,

                    'availableWallets' =>
                        $availableWallets,

                    'activePromotions' =>
                        $activePromotions,

                    'history' =>
                        $history,
                ]
            );
        } catch (Throwable $exception) {
            return self::renderError(
                $exception->getMessage()
            );
        }
    }

    /**
     * @param array<int, PromotionWallet> $wallets
     *
     * @return array<int, PromotionWallet>
     */
    private static function indexWallets(
        array $wallets
    ): array {
        $indexed = [];

        foreach ($wallets as $wallet) {
            $indexed[
                $wallet->getId()
            ] = $wallet;
        }

        return $indexed;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function resolveAdvertisement(
        int $advertisementId
    ): ?array {
        if ($advertisementId <= 0) {
            return null;
        }

        $context =
            apply_filters(
                'dsm_advertisement_context_by_id',
                null,
                $advertisementId
            );

        return is_array($context)
            ? $context
            : null;
    }

    private static function calculateLiveConsumedSeconds(
        PromotionAssignment $assignment,
        ?PromotionWallet $wallet
    ): int {
        if (!$assignment->isActive()) {
            return $assignment
                ->getConsumedSeconds();
        }

        if ($wallet === null) {
            return 0;
        }

        $now =
            new DateTimeImmutable(
                current_time(
                    'mysql',
                    true
                )
            );

        $elapsed =
            max(
                0,
                $now->getTimestamp()
                - $assignment
                    ->getStartedAt()
                    ->getTimestamp()
            );

        return min(
            $elapsed,
            $wallet->getRemainingSeconds()
        );
    }

    private static function calculateLiveRemainingSeconds(
        PromotionAssignment $assignment,
        ?PromotionWallet $wallet
    ): int {
        if ($wallet === null) {
            return 0;
        }

        if (!$assignment->isActive()) {
            return $wallet
                ->getRemainingSeconds();
        }

        return max(
            0,
            $wallet->getRemainingSeconds()
            - self::calculateLiveConsumedSeconds(
                $assignment,
                $wallet
            )
        );
    }

    private static function resolveAuthenticatedCustomerId(): int
    {
        try {
            $authenticatedCustomer =
                new AuthenticatedCustomer(
                    new CustomerSessionRepository(),
                    new CustomerRepository()
                );

            $customer =
                $authenticatedCustomer->resolve();

            return $customer !== null
                ? $customer->getId()
                : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    private static function renderTemplate(
        array $variables
    ): string {
        $template =
            DSM_PROMOCIONAR_PATH
            . 'templates/account/'
            . 'customer-promotions.php';

        if (!is_file($template)) {
            return self::renderError(
                __(
                    'No se encontró la plantilla de promociones.',
                    'dsm-promocionar'
                )
            );
        }

        extract(
            $variables,
            EXTR_SKIP
        );

        ob_start();

        include $template;

        $output =
            ob_get_clean();

        return is_string($output)
            ? $output
            : '';
    }

    private static function renderError(
        string $message
    ): string {
        return sprintf(
            '<div class="dsm-account-notice dsm-account-notice--error">%s</div>',
            esc_html(
                $message
            )
        );
    }

    private function __construct()
    {
    }
}
