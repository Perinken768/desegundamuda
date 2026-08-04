<?php

declare(strict_types=1);

namespace DSM\Clientes\Profile;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de perfiles de clientes.
 *
 * Para el MVP:
 *
 * - phone es el único número utilizado para llamadas y WhatsApp;
 * - allow_phone_calls indica si el cliente acepta llamadas;
 * - allow_whatsapp indica si acepta contacto mediante WhatsApp;
 * - whatsapp_phone se conserva en la base de datos, pero queda
 *   reservado para futuras integraciones y no se modifica desde
 *   el formulario actual.
 */
final class CustomerProfileRepository
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;

        $this->tableName =
            $wpdb->prefix
            . 'dsm_customer_profiles';
    }

    public function findById(
        int $id
    ): ?CustomerProfile {
        global $wpdb;

        if ($id <= 0) {
            return null;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    display_name,
                    phone,
                    allow_phone_calls,
                    whatsapp_phone,
                    allow_whatsapp,
                    avatar_attachment_id,
                    bio,
                    island_id,
                    municipality_id,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE id = %d
                LIMIT 1
                ",
                $id
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function findByCustomerId(
        int $customerId
    ): ?CustomerProfile {
        global $wpdb;

        if ($customerId <= 0) {
            return null;
        }

        $sql =
            $wpdb->prepare(
                "
                SELECT
                    id,
                    customer_id,
                    display_name,
                    phone,
                    allow_phone_calls,
                    whatsapp_phone,
                    allow_whatsapp,
                    avatar_attachment_id,
                    bio,
                    island_id,
                    municipality_id,
                    created_at,
                    updated_at
                FROM {$this->tableName}
                WHERE customer_id = %d
                LIMIT 1
                ",
                $customerId
            );

        if (!is_string($sql)) {
            return null;
        }

        $row =
            $wpdb->get_row(
                $sql,
                ARRAY_A
            );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function create(
        int $customerId,
        string $displayName
    ): CustomerProfile {
        global $wpdb;

        $displayName =
            sanitize_text_field(
                trim($displayName)
            );

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible del perfil no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $displayName
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre visible del perfil es demasiado largo.'
            );
        }

        if (
            $this->findByCustomerId(
                $customerId
            ) !== null
        ) {
            throw new RuntimeException(
                'El cliente ya tiene un perfil asociado.'
            );
        }

        $now =
            current_time(
                'mysql',
                true
            );

        $result =
            $wpdb->insert(
                $this->tableName,
                [
                    'customer_id' =>
                        $customerId,

                    'display_name' =>
                        $displayName,

                    'phone' =>
                        null,

                    'allow_phone_calls' =>
                        0,

                    'whatsapp_phone' =>
                        null,

                    'allow_whatsapp' =>
                        0,

                    'created_at' =>
                        $now,

                    'updated_at' =>
                        $now,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo crear el perfil del cliente.'
            );
        }

        $profile =
            $this->findById(
                (int) $wpdb->insert_id
            );

        if ($profile === null) {
            throw new RuntimeException(
                'El perfil fue creado pero no pudo recuperarse.'
            );
        }

        return $profile;
    }

    /**
     * Actualiza los datos principales y las preferencias
     * de contacto del cliente.
     *
     * El mismo número phone se utiliza para:
     *
     * - llamadas, cuando allowPhoneCalls es true;
     * - WhatsApp, cuando allowWhatsapp es true.
     *
     * El campo whatsapp_phone queda intacto.
     */
    public function update(
        int $customerId,
        string $displayName,
        ?string $phone,
        bool $allowPhoneCalls,
        bool $allowWhatsapp,
        ?string $bio
    ): CustomerProfile {
        global $wpdb;

        $displayName =
            sanitize_text_field(
                trim($displayName)
            );

        $phone =
            $this->normalizePhone(
                $phone
            );

        $bio =
            $this->normalizeNullableTextarea(
                $bio
            );

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        if ($displayName === '') {
            throw new RuntimeException(
                'El nombre visible no puede estar vacío.'
            );
        }

        if (
            mb_strlen(
                $displayName
            ) > 150
        ) {
            throw new RuntimeException(
                'El nombre visible es demasiado largo.'
            );
        }

        if (
            $bio !== null
            && mb_strlen($bio) > 2000
        ) {
            throw new RuntimeException(
                'La biografía es demasiado larga.'
            );
        }

        /*
         * No se permite activar llamadas o WhatsApp sin
         * proporcionar un número válido.
         */
        if (
            (
                $allowPhoneCalls
                || $allowWhatsapp
            )
            && $phone === null
        ) {
            throw new RuntimeException(
                'Debes indicar un número de teléfono para activar llamadas o WhatsApp.'
            );
        }

        /*
         * Si no existe número, se desactivan ambos métodos.
         *
         * Esto evita estados incoherentes en la base de datos.
         */
        if ($phone === null) {
            $allowPhoneCalls =
                false;

            $allowWhatsapp =
                false;
        }

        $profile =
            $this->findByCustomerId(
                $customerId
            );

        if ($profile === null) {
            throw new RuntimeException(
                'No se encontró el perfil del cliente.'
            );
        }

        $result =
            $wpdb->update(
                $this->tableName,
                [
                    'display_name' =>
                        $displayName,

                    'phone' =>
                        $phone,

                    'allow_phone_calls' =>
                        $allowPhoneCalls
                            ? 1
                            : 0,

                    'allow_whatsapp' =>
                        $allowWhatsapp
                            ? 1
                            : 0,

                    'bio' =>
                        $bio,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'customer_id' =>
                        $customerId,
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar el perfil del cliente.'
            );
        }

        $updatedProfile =
            $this->findByCustomerId(
                $customerId
            );

        if ($updatedProfile === null) {
            throw new RuntimeException(
                'El perfil fue actualizado pero no pudo recuperarse.'
            );
        }

        return $updatedProfile;
    }

    /**
     * Actualiza la ubicación del perfil.
     *
     * Se mantiene como operación independiente para no mezclar
     * la gestión del contacto con la de islas y municipios.
     */
    public function updateLocation(
        int $customerId,
        ?int $islandId,
        ?int $municipalityId
    ): CustomerProfile {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $islandId =
            $islandId !== null
            && $islandId > 0
                ? $islandId
                : null;

        $municipalityId =
            $municipalityId !== null
            && $municipalityId > 0
                ? $municipalityId
                : null;

        if (
            $municipalityId !== null
            && $islandId === null
        ) {
            throw new RuntimeException(
                'No se puede seleccionar un municipio sin indicar una isla.'
            );
        }

        if (
            $this->findByCustomerId(
                $customerId
            ) === null
        ) {
            throw new RuntimeException(
                'No se encontró el perfil del cliente.'
            );
        }

        $result =
            $wpdb->update(
                $this->tableName,
                [
                    'island_id' =>
                        $islandId,

                    'municipality_id' =>
                        $municipalityId,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'customer_id' =>
                        $customerId,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            );

        if ($result === false) {
            throw new RuntimeException(
                'No se pudo actualizar la ubicación del perfil.'
            );
        }

        $updatedProfile =
            $this->findByCustomerId(
                $customerId
            );

        if ($updatedProfile === null) {
            throw new RuntimeException(
                'La ubicación fue actualizada pero el perfil no pudo recuperarse.'
            );
        }

        return $updatedProfile;
    }

    /**
     * Actualiza el avatar del cliente.
     */
    public function updateAvatar(
        int $customerId,
        ?int $avatarAttachmentId
    ): CustomerProfile {
        global $wpdb;

        if ($customerId <= 0) {
            throw new RuntimeException(
                'El identificador del cliente no es válido.'
            );
        }

        $avatarAttachmentId =
            $avatarAttachmentId !== null
            && $avatarAttachmentId > 0
                ? $avatarAttachmentId
                : null;

        if (
            $this->findByCustomerId(
                $customerId
            ) === null
        ) {
            throw new RuntimeException(
                'No se encontró el perfil del cliente.'
            );
        }

        $result =
            $wpdb->update(
                $this->tableName,
                [
                    'avatar_attachment_id' =>
                        $avatarAttachmentId,

                    'updated_at' =>
                        current_time(
                            'mysql',
                            true
                        ),
                ],
                [
                    'customer_id' =>
                        $customerId,
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
                'No se pudo actualizar el avatar del cliente.'
            );
        }

        $updatedProfile =
            $this->findByCustomerId(
                $customerId
            );

        if ($updatedProfile === null) {
            throw new RuntimeException(
                'El avatar fue actualizado pero el perfil no pudo recuperarse.'
            );
        }

        return $updatedProfile;
    }

    /**
     * Normaliza el teléfono antes de guardarlo.
     *
     * Formatos aceptados:
     *
     * 600123456
     * 600 123 456
     * +34 600 123 456
     * 0034 600 123 456
     *
     * Resultado:
     *
     * +34600123456
     */
    private function normalizePhone(
        ?string $phone
    ): ?string {
        $normalized =
            CustomerProfile::normalizePhone(
                $phone
            );

        if ($normalized === '') {
            return null;
        }

        /*
         * Un número internacional debe contener entre
         * 8 y 15 cifras según el estándar E.164.
         */
        $digits =
            preg_replace(
                '/\D/',
                '',
                $normalized
            );

        if (!is_string($digits)) {
            throw new RuntimeException(
                'El número de teléfono no es válido.'
            );
        }

        $length =
            strlen($digits);

        if (
            $length < 8
            || $length > 15
        ) {
            throw new RuntimeException(
                'El número de teléfono no tiene una longitud válida.'
            );
        }

        /*
         * Para números españoles se comprueba que, tras +34,
         * existan exactamente nueve cifras.
         */
        if (
            str_starts_with(
                $normalized,
                '+34'
            )
            && strlen($digits) !== 11
        ) {
            throw new RuntimeException(
                'El número español debe contener nueve cifras después del prefijo +34.'
            );
        }

        return $normalized;
    }

    private function normalizeNullableTextarea(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            sanitize_textarea_field(
                trim($value)
            );

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row
    ): CustomerProfile {
        return new CustomerProfile(
            (int) (
                $row['id']
                ?? 0
            ),

            (int) (
                $row['customer_id']
                ?? 0
            ),

            (string) (
                $row['display_name']
                ?? ''
            ),

            isset($row['phone'])
            && $row['phone'] !== null
                ? (string) $row['phone']
                : null,

            (int) (
                $row['allow_phone_calls']
                ?? 0
            ) === 1,

            isset($row['whatsapp_phone'])
            && $row['whatsapp_phone'] !== null
                ? (string) $row[
                    'whatsapp_phone'
                ]
                : null,

            (int) (
                $row['allow_whatsapp']
                ?? 0
            ) === 1,

            isset(
                $row[
                    'avatar_attachment_id'
                ]
            )
            && $row[
                'avatar_attachment_id'
            ] !== null
                ? (int) $row[
                    'avatar_attachment_id'
                ]
                : null,

            isset($row['bio'])
            && $row['bio'] !== null
                ? (string) $row['bio']
                : null,

            isset($row['island_id'])
            && $row['island_id'] !== null
                ? (int) $row['island_id']
                : null,

            isset(
                $row['municipality_id']
            )
            && $row[
                'municipality_id'
            ] !== null
                ? (int) $row[
                    'municipality_id'
                ]
                : null,

            (string) (
                $row['created_at']
                ?? ''
            ),

            (string) (
                $row['updated_at']
                ?? ''
            )
        );
    }
}