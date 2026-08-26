<?php

declare(strict_types=1);

namespace DSM\Legal\Frontend;

use DSM\Legal\Document\LegalDocumentRegistry;
use DSM\Legal\Support\TemplateRenderer;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalDocumentController
{
    public static function register(): void
    {
        add_action(
            'init',
            [
                self::class,
                'registerRewriteRules',
            ]
        );

        add_filter(
            'query_vars',
            [
                self::class,
                'registerQueryVars',
            ]
        );

        add_filter(
            'template_include',
            [
                self::class,
                'renderLegalDocument',
            ]
        );

        add_action(
            'wp_enqueue_scripts',
            [
                self::class,
                'enqueueAssets',
            ]
        );
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_rule(
            '^legal/([^/]+)/?$',
            'index.php?dsm_legal_document=$matches[1]',
            'top'
        );
    }

    /**
     * @param array<int, string> $queryVars
     *
     * @return array<int, string>
     */
    public static function registerQueryVars(
        array $queryVars
    ): array {
        $queryVars[] =
            'dsm_legal_document';

        return $queryVars;
    }

    public static function renderLegalDocument(
        string $template
    ): string {
        $slug =
            sanitize_title(
                (string) get_query_var(
                    'dsm_legal_document'
                )
            );

        if ($slug === '') {
            return $template;
        }

        $registry =
            new LegalDocumentRegistry();

        $document =
            $registry->findBySlug(
                $slug
            );

        if ($document === null) {
            global $wp_query;

            $wp_query->set_404();

            status_header(
                404
            );

            nocache_headers();

            return get_404_template();
        }

        add_filter(
            'document_title_parts',
            static function (
                array $parts
            ) use (
                $document
            ): array {
                $parts['title'] =
                    $document->getTitle();

                return $parts;
            }
        );

        $GLOBALS[
            'dsm_legal_document'
        ] =
            $document;

        return DSM_LEGAL_PATH
            . 'templates/document/document.php';
    }

    public static function enqueueAssets(): void
    {
        $slug =
            sanitize_title(
                (string) get_query_var(
                    'dsm_legal_document'
                )
            );

        if ($slug === '') {
            return;
        }

        wp_enqueue_style(
            'dsm-legal',
            DSM_LEGAL_URL
                . 'assets/css/legal.css',
            [],
            DSM_LEGAL_VERSION
        );
    }

    private function __construct()
    {
    }
}
