<?php

declare(strict_types=1);

namespace DSM\Anuncios\Advertisement;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estados disponibles para los anuncios.
 *
 * Concepto importante:
 *
 * Un anuncio cuenta para el límite de anuncios abiertos
 * mientras se encuentre en alguno de estos estados:
 *
 * - draft
 * - pending
 * - active
 * - reserved
 *
 * Deja de contar cuando queda:
 *
 * - closed
 * - rejected
 *
 * Por tanto, el límite de una suscripción no representa
 * anuncios creados históricamente, sino anuncios abiertos
 * simultáneamente.
 */
final class AdvertisementStatus
{
    public const DRAFT =
        'draft';

    public const PENDING =
        'pending';

    public const ACTIVE =
        'active';

    public const RESERVED =
        'reserved';

    public const CLOSED =
        'closed';

    public const REJECTED =
        'rejected';

    /**
     * Devuelve todos los estados disponibles.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::ACTIVE,
            self::RESERVED,
            self::CLOSED,
            self::REJECTED,
        ];
    }

    public static function isValid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::all(),
            true
        );
    }

    /**
     * Indica si el anuncio puede editarse directamente
     * por su propietario.
     */
    public static function canBeEditedByCustomer(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
            ],
            true
        );
    }

    /**
     * Indica si el anuncio puede enviarse para publicación
     * o revisión automática.
     */
    public static function canBeSubmitted(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
            ],
            true
        );
    }

    /**
     * Indica si administración puede publicar el anuncio.
     */
    public static function canBePublished(
        string $status
    ): bool {
        return $status
            === self::PENDING;
    }

    /**
     * Indica si administración puede rechazar el anuncio.
     */
    public static function canBeRejected(
        string $status
    ): bool {
        return $status
            === self::PENDING;
    }

    /**
     * Indica si el anuncio puede marcarse como reservado.
     */
    public static function canBeReserved(
        string $status
    ): bool {
        return $status
            === self::ACTIVE;
    }

    /**
     * Indica si una reserva puede liberarse y volver
     * el anuncio al estado activo.
     */
    public static function canBeReleased(
        string $status
    ): bool {
        return $status
            === self::RESERVED;
    }

    /**
     * Indica si el anuncio puede cerrarse.
     */
    public static function canBeClosed(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::ACTIVE,
                self::RESERVED,
            ],
            true
        );
    }

    /**
     * Indica si el propietario puede eliminar el anuncio.
     */
    public static function canBeDeletedByCustomer(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::DRAFT,
                self::REJECTED,
                self::CLOSED,
            ],
            true
        );
    }

    /**
     * Indica si el anuncio puede mostrarse públicamente
     * en el marketplace.
     */
    public static function isPublic(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::ACTIVE,
                self::RESERVED,
            ],
            true
        );
    }

    /**
     * Devuelve los estados que consumen una plaza dentro
     * del límite de anuncios abiertos de una suscripción.
     *
     * @return array<int, string>
     */
    public static function statusesCountingTowardsActiveLimit(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::ACTIVE,
            self::RESERVED,
        ];
    }

    /**
     * Indica si un estado concreto consume una plaza
     * dentro del límite de anuncios abiertos.
     */
    public static function countsTowardsActiveLimit(
        string $status
    ): bool {
        return in_array(
            $status,
            self::statusesCountingTowardsActiveLimit(),
            true
        );
    }

    /**
     * Indica si el anuncio está definitivamente fuera
     * del conjunto de anuncios abiertos.
     */
    public static function isFinished(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::CLOSED,
                self::REJECTED,
            ],
            true
        );
    }

    private function __construct()
    {
    }
}