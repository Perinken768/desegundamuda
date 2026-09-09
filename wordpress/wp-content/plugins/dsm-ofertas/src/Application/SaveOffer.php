<?php

declare(strict_types=1);

namespace DSM\Ofertas\Application;

use DSM\Ofertas\Offer\OfferRepository;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SaveOffer
{
    public function __construct(
        private readonly OfferRepository $repository =
            new OfferRepository()
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, array<string, mixed>> $rules
     */
    public function execute(
        ?int $offerId,
        array $input,
        array $rules
    ): int {
        global $wpdb;

        $code =
            sanitize_key(
                (string) (
                    $input['code']
                    ?? ''
                )
            );

        $name =
            trim(
                sanitize_text_field(
                    (string) (
                        $input['name']
                        ?? ''
                    )
                )
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la oferta es obligatorio.'
            );
        }

        if ($code === '') {
            $code =
                sanitize_key(
                    $name
                );
        }

        if ($code === '') {
            throw new RuntimeException(
                'No se pudo generar un código válido para la oferta.'
            );
        }

        $scope =
            sanitize_key(
                (string) (
                    $input['scope']
                    ?? 'general'
                )
            );

        if (
            !in_array(
                $scope,
                [
                    'general',
                    'customers',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El ámbito de la oferta no es válido.'
            );
        }

        $status =
            sanitize_key(
                (string) (
                    $input['status']
                    ?? 'draft'
                )
            );

        if (
            !in_array(
                $status,
                [
                    'draft',
                    'active',
                    'paused',
                    'archived',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado de la oferta no es válido.'
            );
        }

        $startsAt =
            $this->normalizeDateTime(
                $input['starts_at']
                ?? null
            );

        $endsAt =
            $this->normalizeDateTime(
                $input['ends_at']
                ?? null
            );

        if (
            $startsAt !== null
            && $endsAt !== null
            && strtotime($endsAt)
                < strtotime($startsAt)
        ) {
            throw new RuntimeException(
                'La fecha de finalización no puede ser anterior '
                . 'a la fecha de inicio.'
            );
        }

        $data = [
            'code' =>
                $code,

            'name' =>
                $name,

            'description' =>
                sanitize_textarea_field(
                    (string) (
                        $input['description']
                        ?? ''
                    )
                ),

            'scope' =>
                $scope,

            'status' =>
                $status,

            'starts_at' =>
                $startsAt,

            'ends_at' =>
                $endsAt,

            'priority' =>
                max(
                    0,
                    (int) (
                        $input['priority']
                        ?? 0
                    )
                ),

            'stackable' =>
                !empty(
                    $input['stackable']
                )
                    ? 1
                    : 0,
        ];

        $transactionStarted =
            $wpdb->query(
                'START TRANSACTION'
            ) !== false;

        if (!$transactionStarted) {
            throw new RuntimeException(
                'No se pudo iniciar la transacción de la oferta.'
            );
        }

        try {
            if (
                $offerId !== null
                && $offerId > 0
            ) {
            $this->repository
                ->update(
                    $offerId,
                    $data
                );

            $resolvedOfferId =
                $offerId;
        } else {
            $resolvedOfferId =
                $this->repository
                    ->create(
                        $data
                    );
        }

        $this->repository
            ->deactivateRules(
                $resolvedOfferId
            );

        $sortOrder = 10;

        foreach ($rules as $rule) {
            if (empty($rule['enabled'])) {
                continue;
            }

            $planId =
                max(
                    0,
                    (int) (
                        $rule['plan_id']
                        ?? 0
                    )
                );

            if ($planId <= 0) {
                continue;
            }

            $benefitType =
                sanitize_key(
                    (string) (
                        $rule['benefit_type']
                        ?? ''
                    )
                );

            if (
                !in_array(
                    $benefitType,
                    [
                        'free_months',
                        'percentage_discount',
                        'fixed_discount',
                        'special_price',
                    ],
                    true
                )
            ) {
                continue;
            }

            $benefitValue =
                max(
                    0,
                    (float) (
                        $rule['benefit_value']
                        ?? 0
                    )
                );

            $durationMonths =
                isset(
                    $rule['duration_months']
                )
                && $rule['duration_months'] !== ''
                    ? max(
                        1,
                        (int) $rule['duration_months']
                    )
                    : null;

            if (
                $benefitType
                === 'free_months'
            ) {
                $benefitValue =
                    max(
                        1,
                        (float) $benefitValue
                    );

                $durationMonths =
                    (int) $benefitValue;
            }

            if (
                $benefitType
                === 'percentage_discount'
                && $benefitValue > 100
            ) {
                throw new RuntimeException(
                    'Un descuento porcentual no puede superar el 100 %.'
                );
            }

            $this->repository
                ->saveRule(
                    $resolvedOfferId,
                    [
                        'plan_id' =>
                            $planId,

                        'benefit_type' =>
                            $benefitType,

                        'benefit_value' =>
                            $benefitValue,

                        'duration_months' =>
                            $durationMonths,

                        'applies_to' =>
                            sanitize_key(
                                (string) (
                                    $rule['applies_to']
                                    ?? 'any_subscription'
                                )
                            ),

                        'sort_order' =>
                            $sortOrder,
                    ]
                );

            $sortOrder += 10;
        }

            $committed =
                $wpdb->query(
                    'COMMIT'
                );

            if ($committed === false) {
                throw new RuntimeException(
                    'No se pudo confirmar la transacción de la oferta.'
                );
            }

            return $resolvedOfferId;
        } catch (\Throwable $exception) {
            $wpdb->query(
                'ROLLBACK'
            );

            throw $exception;
        }
    }

    private function normalizeDateTime(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        $timestamp =
            strtotime(
                $value
            );

        if ($timestamp === false) {
            throw new RuntimeException(
                'Una de las fechas introducidas no es válida.'
            );
        }

        return gmdate(
            'Y-m-d H:i:s',
            $timestamp
        );
    }
}
