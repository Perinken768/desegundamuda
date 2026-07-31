<?php

declare(strict_types=1);

namespace DSM\Clientes\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Installer
{
    private const DB_VERSION_OPTION = 'dsm_clientes_db_version';

    public static function activate(): void
    {
        self::migrate();
    }

    public static function migrate(): void
    {
        $installedVersion = get_option(self::DB_VERSION_OPTION, '0.0.0');

        if (version_compare($installedVersion, '0.1.0', '<')) {
            self::migration001();

            update_option(
                self::DB_VERSION_OPTION,
                '0.1.0',
                false
            );
        }
    }

    private static function migration001(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();

        $customersTable = $wpdb->prefix . 'dsm_customers';
        $profilesTable  = $wpdb->prefix . 'dsm_customer_profiles';

        $customersSql = "
            CREATE TABLE {$customersTable} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',

                email_verified_at DATETIME NULL,
                last_login_at DATETIME NULL,

                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,

                PRIMARY KEY (id),
                UNIQUE KEY email (email),
                KEY status (status)
            ) {$charsetCollate};
        ";

        $profilesSql = "
            CREATE TABLE {$profilesTable} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_id BIGINT UNSIGNED NOT NULL,

                display_name VARCHAR(150) NOT NULL,
                phone VARCHAR(30) NULL,
                whatsapp_phone VARCHAR(30) NULL,
                avatar_attachment_id BIGINT UNSIGNED NULL,
                bio TEXT NULL,

                island_id BIGINT UNSIGNED NULL,
                municipality_id BIGINT UNSIGNED NULL,

                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,

                PRIMARY KEY (id),
                UNIQUE KEY customer_id (customer_id),
                KEY island_id (island_id),
                KEY municipality_id (municipality_id)
            ) {$charsetCollate};
        ";

        dbDelta($customersSql);
        dbDelta($profilesSql);
    }
}