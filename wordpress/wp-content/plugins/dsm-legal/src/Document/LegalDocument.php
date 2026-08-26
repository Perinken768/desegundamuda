<?php

declare(strict_types=1);

namespace DSM\Legal\Document;

if (!defined('ABSPATH')) {
    exit;
}

final class LegalDocument
{
    public function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly string $slug,
        private readonly string $content,
        private readonly string $version,
        private readonly string $updatedAt
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
