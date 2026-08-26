<?php

declare(strict_types=1);

namespace DSM\Mfa\Challenge;

if (!defined('ABSPATH')) {
    exit;
}

final class MfaChallengeRepository
{
    private const TRANSIENT_PREFIX =
        'dsm_mfa_challenge_';

    private const ACTIVE_PREFIX =
        'dsm_mfa_active_';

    public function save(
        MfaChallenge $challenge
    ): bool {
        $remainingLifetime =
            $challenge->getExpiresAt()
            - time();

        if ($remainingLifetime <= 0) {
            return false;
        }

        $saved =
            set_transient(
                $this->getTransientKey(
                    $challenge->getToken()
                ),
                $challenge->toArray(),
                $remainingLifetime
            );

        if (!$saved) {
            return false;
        }

        set_transient(
            $this->getActiveKey(
                $challenge->getContext(),
                $challenge->getIdentifier()
            ),
            $challenge->getToken(),
            $remainingLifetime
        );

        return true;
    }

    public function find(
        string $token
    ): ?MfaChallenge {
        $token =
            sanitize_text_field(
                $token
            );

        if ($token === '') {
            return null;
        }

        $data =
            get_transient(
                $this->getTransientKey(
                    $token
                )
            );

        if (!is_array($data)) {
            return null;
        }

        $challenge =
            MfaChallenge::fromArray(
                $data
            );

        if ($challenge === null) {
            $this->delete($token);

            return null;
        }

        if ($challenge->isExpired()) {
            $this->delete($token);

            return null;
        }

        /*
         * Aunque el transient del desafío todavía exista,
         * solo será válido si continúa siendo el desafío
         * activo de esa identidad.
         */
        $activeToken =
            $this->findActiveToken(
                $challenge->getContext(),
                $challenge->getIdentifier()
            );

        if (
            $activeToken === null
            || !hash_equals(
                $activeToken,
                $token
            )
        ) {
            $this->deleteChallengeTransient(
                $token
            );

            return null;
        }

        return $challenge;
    }

    public function delete(
        string $token
    ): bool {
        $token =
            sanitize_text_field(
                $token
            );

        if ($token === '') {
            return false;
        }

        /*
         * Leemos directamente el transient porque find()
         * depende a su vez del índice activo.
         */
        $data =
            get_transient(
                $this->getTransientKey(
                    $token
                )
            );

        $challenge =
            is_array($data)
                ? MfaChallenge::fromArray(
                    $data
                )
                : null;

        $deleted =
            $this->deleteChallengeTransient(
                $token
            );

        if ($challenge !== null) {
            $activeToken =
                $this->findActiveToken(
                    $challenge->getContext(),
                    $challenge->getIdentifier()
                );

            /*
             * No eliminamos el índice de otro desafío
             * posterior.
             */
            if (
                $activeToken !== null
                && hash_equals(
                    $activeToken,
                    $token
                )
            ) {
                delete_transient(
                    $this->getActiveKey(
                        $challenge->getContext(),
                        $challenge->getIdentifier()
                    )
                );
            }
        }

        return $deleted;
    }

    public function deleteActive(
        string $context,
        string $identifier
    ): void {
        $activeToken =
            $this->findActiveToken(
                $context,
                $identifier
            );

        if ($activeToken === null) {
            return;
        }

        $this->deleteChallengeTransient(
            $activeToken
        );

        delete_transient(
            $this->getActiveKey(
                $context,
                $identifier
            )
        );
    }

    private function findActiveToken(
        string $context,
        string $identifier
    ): ?string {
        $token =
            get_transient(
                $this->getActiveKey(
                    $context,
                    $identifier
                )
            );

        if (
            !is_string($token)
            || $token === ''
        ) {
            return null;
        }

        return $token;
    }

    private function deleteChallengeTransient(
        string $token
    ): bool {
        return delete_transient(
            $this->getTransientKey(
                $token
            )
        );
    }

    private function getTransientKey(
        string $token
    ): string {
        return self::TRANSIENT_PREFIX
            . hash(
                'sha256',
                $token
            );
    }

    private function getActiveKey(
        string $context,
        string $identifier
    ): string {
        /*
         * Tampoco exponemos contextos o identificadores
         * directamente en el nombre del transient.
         */
        return self::ACTIVE_PREFIX
            . hash(
                'sha256',
                $context
                . ':'
                . $identifier
            );
    }
}
