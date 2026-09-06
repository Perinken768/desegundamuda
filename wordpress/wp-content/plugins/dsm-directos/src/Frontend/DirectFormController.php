<?php

declare(strict_types=1);

namespace DSM\Directos\Frontend;

use DSM\Directos\Application\SaveDirectConfiguration;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectFormController
{
    public const SAVE_ACTION =
        'dsm_directos_save_configuration';

    public const NONCE_ACTION =
        'dsm_directos_save_configuration';

    public const NONCE_NAME =
        'dsm_directos_configuration_nonce';

    private const DIRECTS_PATH =
        '/mis-directos/';

    public static function register(): void
    {
        $controller =
            new self();

        add_action(
            'admin_post_'
            . self::SAVE_ACTION,
            [
                $controller,
                'handleSave',
            ]
        );

        add_action(
            'admin_post_nopriv_'
            . self::SAVE_ACTION,
            [
                $controller,
                'handleSave',
            ]
        );
    }

    public function handleSave(): never
    {
        check_admin_referer(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

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

            $directId =
                (
                    new SaveDirectConfiguration()
                )->execute(
                    customerId:
                        $customerId,

                    title:
                        isset($_POST['title'])
                            ? sanitize_text_field(
                                wp_unslash(
                                    (string) $_POST['title']
                                )
                            )
                            : '',

                    description:
                        isset($_POST['description'])
                            ? sanitize_textarea_field(
                                wp_unslash(
                                    (string) $_POST['description']
                                )
                            )
                            : '',

                    platform:
                        isset($_POST['platform'])
                            ? sanitize_key(
                                wp_unslash(
                                    (string) $_POST['platform']
                                )
                            )
                            : '',

                    platformUrl:
                        isset($_POST['platform_url'])
                            ? esc_url_raw(
                                wp_unslash(
                                    (string) $_POST['platform_url']
                                )
                            )
                            : ''
                );

            $this->redirect(
                [
                    'notice' =>
                        'configuration-saved',
                ]
            );
        } catch (Throwable $exception) {
            $this->redirect(
                [
                    'direct_section' =>
                        'configure',

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }

    /**
     * @param array<string, scalar> $args
     */
    private function redirect(
        array $args
    ): never {
        wp_safe_redirect(
            add_query_arg(
                $args,
                home_url(
                    self::DIRECTS_PATH
                )
            )
        );

        exit;
    }

    private function __construct()
    {
    }
}
