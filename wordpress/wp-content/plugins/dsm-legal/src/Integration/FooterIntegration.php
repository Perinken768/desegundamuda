<?php

declare(strict_types=1);

namespace DSM\Legal\Integration;

use DSM\Legal\Document\LegalDocumentRegistry;

if (!defined('ABSPATH')) {
    exit;
}

final class FooterIntegration
{
    public static function register(): void
    {
        /*
         * Devuelve todos los documentos legales públicos.
         *
         * Otros plugins y el tema pueden consumir este
         * contrato sin conocer internamente DSM Legal.
         */
        add_filter(
            'dsm_legal_documents',
            [
                self::class,
                'filterDocuments',
            ],
            10,
            1
        );

        /*
         * Devuelve los enlaces recomendados para el footer.
         */
        add_filter(
            'dsm_legal_footer_links',
            [
                self::class,
                'filterFooterLinks',
            ],
            10,
            1
        );

        /*
         * Permite solicitar la URL de un documento por clave.
         */
        add_filter(
            'dsm_legal_document_url',
            [
                self::class,
                'filterDocumentUrl',
            ],
            10,
            2
        );
    }

    /**
     * @param mixed $documents
     *
     * @return array<string, array<string, string>>
     */
    public static function filterDocuments(
        mixed $documents
    ): array {
        $registry =
            new LegalDocumentRegistry();

        $result = [];

        foreach (
            $registry->all()
            as $key => $document
        ) {
            $result[
                $key
            ] = [
                'key' =>
                    $document->getKey(),

                'title' =>
                    $document->getTitle(),

                'slug' =>
                    $document->getSlug(),

                'url' =>
                    home_url(
                        '/legal/'
                        . $document->getSlug()
                        . '/'
                    ),
            ];
        }

        return $result;
    }

    /**
     * @param mixed $links
     *
     * @return array<int, array<string, string>>
     */
    public static function filterFooterLinks(
        mixed $links
    ): array {
        $registry =
            new LegalDocumentRegistry();

        /*
         * Orden deliberado del footer.
         */
        $keys = [
            'legal_notice',
            'privacy',
            'cookies',
            'terms',
            'marketplace_rules',
        ];

        $result = [];

        foreach (
            $keys
            as $key
        ) {
            $document =
                $registry->findByKey(
                    $key
                );

            if ($document === null) {
                continue;
            }

            $result[] = [
                'key' =>
                    $document->getKey(),

                'label' =>
                    $document->getTitle(),

                'url' =>
                    home_url(
                        '/legal/'
                        . $document->getSlug()
                        . '/'
                    ),
            ];
        }

        return $result;
    }

    public static function filterDocumentUrl(
        mixed $url,
        mixed $key
    ): string {
        $registry =
            new LegalDocumentRegistry();

        $document =
            $registry->findByKey(
                sanitize_key(
                    (string) $key
                )
            );

        if ($document === null) {
            return is_string($url)
                ? $url
                : '';
        }

        return home_url(
            '/legal/'
            . $document->getSlug()
            . '/'
        );
    }

    private function __construct()
    {
    }
}
