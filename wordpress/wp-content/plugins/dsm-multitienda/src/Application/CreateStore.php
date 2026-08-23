<?php

declare(strict_types=1);

namespace DSM\Multitienda\Application;

use DSM\Multitienda\Store\Store;
use DSM\Multitienda\Store\StoreRepository;
use DSM\Multitienda\Store\StoreStatus;
use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class CreateStore
{
    public function __construct(
        private readonly MultistoreAccessService $accessService =
            new MultistoreAccessService(),

        private readonly StoreRepository $storeRepository =
            new StoreRepository()
    ) {
    }

    public function execute(
        int $customerId,
        string $name,
        string $slug,
        ?string $description = null,
        ?string $island = null,
        ?string $locationText = null
    ): Store {
        $this->accessService
            ->assertHasAccess(
                $customerId
            );

        $existingStore =
            $this->storeRepository
                ->findByCustomerId(
                    $customerId
                );

        if ($existingStore !== null) {
            throw new RuntimeException(
                'El cliente ya tiene una tienda creada.'
            );
        }

        $name =
            trim(
                sanitize_text_field(
                    $name
                )
            );

        if ($name === '') {
            throw new RuntimeException(
                'El nombre de la tienda es obligatorio.'
            );
        }

        $slug =
            sanitize_title(
                $slug
            );

        if ($slug === '') {
            $slug =
                sanitize_title(
                    $name
                );
        }

        if ($slug === '') {
            throw new RuntimeException(
                'No se pudo generar un slug válido para la tienda.'
            );
        }

        return $this->storeRepository
            ->create(
                customerId:
                    $customerId,

                name:
                    $name,

                slug:
                    $slug,

                description:
                    $description,

                logoAttachmentId:
                    null,

                island:
                    $island,

                locationText:
                    $locationText,

                status:
                    StoreStatus::DRAFT
            );
    }
}
