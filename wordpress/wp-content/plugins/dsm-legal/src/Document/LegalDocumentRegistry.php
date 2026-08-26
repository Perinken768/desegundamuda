<?php

declare(strict_types=1);

namespace DSM\Legal\Document;

use DSM\Legal\Admin\LegalAdminPage;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalDocumentRegistry
{
    /**
     * @return array<string, LegalDocument>
     */
    public function all(): array
    {
        $definitions = [
            'legal_notice' => [
                'title' => 'Aviso legal',
                'slug' => 'aviso-legal',
                'content' => 'Contenido legal pendiente de definir.',
            ],

            'privacy' => [
                'title' => 'Política de privacidad',
                'slug' => 'politica-de-privacidad',
                'content' => 'Contenido de privacidad pendiente de definir.',
            ],

            'cookies' => [
                'title' => 'Política de cookies',
                'slug' => 'politica-de-cookies',
                'content' => 'Contenido de cookies pendiente de definir.',
            ],

            'terms' => [
                'title' => 'Términos y condiciones',
                'slug' => 'terminos-y-condiciones',
                'content' => 'Contenido de términos pendiente de definir.',
            ],

            'marketplace_rules' => [
                'title' => 'Normas de publicación y uso',
                'slug' => 'normas-del-marketplace',
                'content' => 'Contenido de normas pendiente de definir.',
            ],
        ];

        $documents = [];

        foreach (
            $definitions
            as $key => $definition
        ) {
            $defaultTitle =
                (string) $definition['title'];

            $defaultContent =
                (string) $definition['content'];

            $title =
                trim(
                    (string) get_option(
                        LegalAdminPage::getTitleOptionName(
                            $key
                        ),
                        $defaultTitle
                    )
                );

            if ($title === '') {
                $title = $defaultTitle;
            }

            $content =
                (string) get_option(
                    LegalAdminPage::getContentOptionName(
                        $key
                    ),
                    $defaultContent
                );

            $version =
                trim(
                    (string) get_option(
                        LegalAdminPage::getVersionOptionName(
                            $key
                        ),
                        '1.0'
                    )
                );

            if ($version === '') {
                $version = '1.0';
            }

            $updatedAt =
                trim(
                    (string) get_option(
                        LegalAdminPage::getUpdatedAtOptionName(
                            $key
                        ),
                        ''
                    )
                );

            $documents[$key] =
                new LegalDocument(
                    key: $key,
                    title: $title,
                    slug: (string) $definition['slug'],
                    content: $content,
                    version: $version,
                    updatedAt: $updatedAt
                );
        }

        return $documents;
    }

    public function findBySlug(
        string $slug
    ): ?LegalDocument {
        $slug =
            sanitize_title(
                $slug
            );

        foreach (
            $this->all()
            as $document
        ) {
            if (
                $document->getSlug()
                === $slug
            ) {
                return $document;
            }
        }

        return null;
    }

    public function findByKey(
        string $key
    ): ?LegalDocument {
        $documents =
            $this->all();

        return $documents[
            sanitize_key(
                $key
            )
        ]
            ?? null;
    }
}
