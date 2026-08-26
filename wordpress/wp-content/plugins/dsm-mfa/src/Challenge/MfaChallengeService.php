<?php

declare(strict_types=1);

namespace DSM\Mfa\Challenge;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class MfaChallengeService
{
    private const CODE_MIN =
        100000;

    private const CODE_MAX =
        999999;

    private const LIFETIME_SECONDS =
        600;

    private const MAX_ATTEMPTS =
        5;

    private const MAX_RESENDS =
        3;

    private const RESEND_COOLDOWN_SECONDS =
        60;

    public function __construct(
        private readonly MfaChallengeRepository $repository
    ) {
    }

    /**
     * @return array{
     *     challenge: MfaChallenge,
     *     code: string
     * }
     */
    public function create(
        string $context,
        string $identifier,
        string $email
    ): array {
        $context =
            sanitize_key(
                $context
            );

        $identifier =
            trim(
                $identifier
            );

        $email =
            sanitize_email(
                $email
            );

        if (
            !in_array(
                $context,
                [
                    'customer',
                    'wordpress',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El contexto MFA no es válido.'
            );
        }

        if ($identifier === '') {
            throw new RuntimeException(
                'El identificador MFA no puede estar vacío.'
            );
        }

        if (
            $email === ''
            || !is_email($email)
        ) {
            throw new RuntimeException(
                'El correo electrónico MFA no es válido.'
            );
        }

        /*
         * Solo puede existir un desafío MFA activo
         * por identidad y contexto.
         *
         * Si había uno anterior, lo eliminamos antes
         * de crear el nuevo.
         */
        $this->repository->deleteActive(
            $context,
            $identifier
        );

        $code =
            $this->generateCode();

        $token =
            bin2hex(
                random_bytes(32)
            );

        $now =
            time();

        $challenge =
            new MfaChallenge(
                token: $token,
                context: $context,
                identifier: $identifier,
                email: $email,
                codeHash:
                    wp_hash_password(
                        $code
                    ),
                createdAt: $now,
                expiresAt:
                    $now
                    + self::LIFETIME_SECONDS,
                attempts: 0,
                maxAttempts:
                    self::MAX_ATTEMPTS,
                verified: false,
                resendCount: 0,
                lastSentAt: $now
            );

        if (
            !$this->repository->save(
                $challenge
            )
        ) {
            throw new RuntimeException(
                'No se pudo guardar el desafío MFA.'
            );
        }

        return [
            'challenge' =>
                $challenge,

            'code' =>
                $code,
        ];
    }

    /**
     * Genera un código nuevo para un desafío existente.
     *
     * El código anterior deja de ser válido.
     *
     * @return array{
     *     challenge: MfaChallenge,
     *     code: string
     * }
     */
    public function resend(
        string $token
    ): array {
        $challenge =
            $this->repository->find(
                $token
            );

        if ($challenge === null) {
            throw new RuntimeException(
                'El desafío MFA ya no está disponible.'
            );
        }

        if ($challenge->isVerified()) {
            throw new RuntimeException(
                'El desafío MFA ya ha sido utilizado.'
            );
        }

        if (
            $challenge->getResendCount()
            >= self::MAX_RESENDS
        ) {
            throw new RuntimeException(
                'Se ha alcanzado el máximo de reenvíos.'
            );
        }

        $now =
            time();

        $secondsSinceLastSend =
            $now
            - $challenge->getLastSentAt();

        if (
            $secondsSinceLastSend
            < self::RESEND_COOLDOWN_SECONDS
        ) {
            $remaining =
                self::RESEND_COOLDOWN_SECONDS
                - $secondsSinceLastSend;

            throw new RuntimeException(
                'Debes esperar '
                . $remaining
                . ' segundos antes de solicitar otro código.'
            );
        }

        $code =
            $this->generateCode();

        $updatedChallenge =
            $challenge->withResentCode(
                wp_hash_password(
                    $code
                ),
                $now,
                $now
                + self::LIFETIME_SECONDS
            );

        if (
            !$this->repository->save(
                $updatedChallenge
            )
        ) {
            throw new RuntimeException(
                'No se pudo actualizar el desafío MFA.'
            );
        }

        return [
            'challenge' =>
                $updatedChallenge,

            'code' =>
                $code,
        ];
    }

    public function find(
        string $token
    ): ?MfaChallenge {
        return $this->repository->find(
            $token
        );
    }

    public function verify(
        string $token,
        string $code
    ): bool {
        $challenge =
            $this->repository->find(
                $token
            );

        if ($challenge === null) {
            return false;
        }

        if ($challenge->isVerified()) {
            return false;
        }

        if (
            !$challenge
                ->hasAttemptsRemaining()
        ) {
            $this->repository->delete(
                $token
            );

            return false;
        }

        $code =
            trim(
                $code
            );

        $validFormat =
            preg_match(
                '/^\d{6}$/',
                $code
            ) === 1;

        $validCode =
            $validFormat
            && wp_check_password(
                $code,
                $challenge->getCodeHash()
            );

        if (!$validCode) {
            $failedChallenge =
                $challenge
                    ->withFailedAttempt();

            if (
                !$failedChallenge
                    ->hasAttemptsRemaining()
            ) {
                $this->repository->delete(
                    $token
                );
            } else {
                $this->repository->save(
                    $failedChallenge
                );
            }

            return false;
        }

        $this->repository->delete(
            $token
        );

        return true;
    }

    private function generateCode(): string
    {
        return (string) random_int(
            self::CODE_MIN,
            self::CODE_MAX
        );
    }
}
