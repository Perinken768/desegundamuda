<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return static function (): void {
    global $wpdb;

    require_once ABSPATH
        . 'wp-admin/includes/upgrade.php';

    $streamsTable =
        $wpdb->prefix
        . 'dsm_live_streams';

    $itemsTable =
        $wpdb->prefix
        . 'dsm_live_items';

    $reservationsTable =
        $wpdb->prefix
        . 'dsm_live_reservations';

    $charsetCollate =
        $wpdb->get_charset_collate();

    /*
     * =========================================================
     * DIRECTOS
     * =========================================================
     */

    $streamsSql = "
        CREATE TABLE {$streamsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            customer_id bigint(20) unsigned NOT NULL,

            title varchar(190) NOT NULL,

            slug varchar(190) NOT NULL,

            description text DEFAULT NULL,

            platform varchar(30) NOT NULL,

            platform_url varchar(1000) NOT NULL,

            status varchar(30) NOT NULL DEFAULT 'draft',

            starts_at datetime DEFAULT NULL,

            ended_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY slug (
                slug
            ),

            KEY customer_id (
                customer_id
            ),

            KEY status (
                status
            ),

            KEY customer_status (
                customer_id,
                status
            ),

            KEY starts_at (
                starts_at
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $streamsSql
    );

    /*
     * =========================================================
     * ARTÍCULOS DEL DIRECTO
     * =========================================================
     *
     * source_type:
     *
     * advertisement
     * inventory
     *
     * Para anuncios:
     *
     * advertisement_id contiene el anuncio.
     * product_id / variant_id son NULL.
     *
     * Para inventario:
     *
     * product_id y variant_id identifican la variante.
     * advertisement_id es NULL.
     *
     * allocated_quantity indica cuántas unidades del
     * inventario desea ofrecer el vendedor en ese directo.
     *
     * Un anuncio normal siempre tendrá allocated_quantity = 1.
     */

    $itemsSql = "
        CREATE TABLE {$itemsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            live_id bigint(20) unsigned NOT NULL,

            source_type varchar(30) NOT NULL,

            advertisement_id bigint(20) unsigned DEFAULT NULL,

            product_id bigint(20) unsigned DEFAULT NULL,

            variant_id bigint(20) unsigned DEFAULT NULL,

            position int(10) unsigned NOT NULL DEFAULT 0,

            live_number int(10) unsigned NOT NULL,

            allocated_quantity int(10) unsigned NOT NULL DEFAULT 1,

            status varchar(30) NOT NULL DEFAULT 'available',

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            UNIQUE KEY live_number_unique (
                live_id,
                live_number
            ),

            KEY live_id (
                live_id
            ),

            KEY source_type (
                source_type
            ),

            KEY advertisement_id (
                advertisement_id
            ),

            KEY product_id (
                product_id
            ),

            KEY variant_id (
                variant_id
            ),

            KEY live_position (
                live_id,
                position
            ),

            KEY live_status (
                live_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $itemsSql
    );

    /*
     * =========================================================
     * RESERVAS REALIZADAS DESDE DIRECTOS
     * =========================================================
     *
     * Esta tabla NO sustituye las reservas del catálogo.
     *
     * Para inventory, source_reservation_id podrá apuntar
     * a la reserva real de DSM Catálogo / Multitienda.
     *
     * Para advertisement, advertisement_id permitirá
     * relacionar la reserva con el anuncio que posteriormente
     * se marque como reservado.
     */

    $reservationsSql = "
        CREATE TABLE {$reservationsTable} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            live_id bigint(20) unsigned NOT NULL,

            live_item_id bigint(20) unsigned NOT NULL,

            buyer_customer_id bigint(20) unsigned NOT NULL,

            seller_customer_id bigint(20) unsigned NOT NULL,

            advertisement_id bigint(20) unsigned DEFAULT NULL,

            product_id bigint(20) unsigned DEFAULT NULL,

            variant_id bigint(20) unsigned DEFAULT NULL,

            quantity int(10) unsigned NOT NULL DEFAULT 1,

            source_reservation_id bigint(20) unsigned DEFAULT NULL,

            status varchar(30) NOT NULL DEFAULT 'reserved',

            reserved_at datetime NOT NULL,

            released_at datetime DEFAULT NULL,

            completed_at datetime DEFAULT NULL,

            created_at datetime NOT NULL,

            updated_at datetime NOT NULL,

            PRIMARY KEY (id),

            KEY live_id (
                live_id
            ),

            KEY live_item_id (
                live_item_id
            ),

            KEY buyer_customer_id (
                buyer_customer_id
            ),

            KEY seller_customer_id (
                seller_customer_id
            ),

            KEY advertisement_id (
                advertisement_id
            ),

            KEY variant_id (
                variant_id
            ),

            KEY source_reservation_id (
                source_reservation_id
            ),

            KEY live_item_status (
                live_item_id,
                status
            )
        ) {$charsetCollate};
    ";

    dbDelta(
        $reservationsSql
    );

    /*
     * Comprobación final.
     */

    foreach (
        [
            $streamsTable,
            $itemsTable,
            $reservationsTable,
        ]
        as $tableName
    ) {
        $exists =
            $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $tableName
                )
            );

        if ($exists !== $tableName) {
            throw new RuntimeException(
                sprintf(
                    'No se pudo crear la tabla %s de DSM Directos.',
                    $tableName
                )
            );
        }
    }
};
