<?php

declare(strict_types=1);

namespace DSM\Directos\Application;

if (!defined('ABSPATH')) {
    exit;
}

final class DirectVideoEmbedService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(
        string $platform,
        string $platformUrl
    ): array {
        $platform =
            sanitize_key(
                $platform
            );

        $platformUrl =
            esc_url_raw(
                trim(
                    $platformUrl
                )
            );

        $detectedPlatform =
            $this->detectPlatform(
                $platformUrl
            );

        /*
         * Si el vendedor dejó "Otra" pero la URL identifica
         * claramente la plataforma, usamos la plataforma real.
         */
        if (
            $detectedPlatform !== null
            && (
                $platform === ''
                || $platform === 'other'
            )
        ) {
            $platform =
                $detectedPlatform;
        }

        if ($platform === 'twitch') {
            $resolved =
                $this->resolveTwitch(
                    $platformUrl
                );

            if ($resolved !== null) {
                return $resolved;
            }
        }

        if ($platform === 'youtube') {
            $resolved =
                $this->resolveYouTube(
                    $platformUrl
                );

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return [
            'type' =>
                'external',

            'platform' =>
                $platform !== ''
                    ? $platform
                    : 'other',

            'url' =>
                $platformUrl,

            'embed_url' =>
                '',
        ];
    }

    private function detectPlatform(
        string $url
    ): ?string {
        if ($url === '') {
            return null;
        }

        $host =
            strtolower(
                (string) wp_parse_url(
                    $url,
                    PHP_URL_HOST
                )
            );

        $host =
            preg_replace(
                '/^www\./',
                '',
                $host
            );

        if (
            $host === 'twitch.tv'
            || str_ends_with(
                $host,
                '.twitch.tv'
            )
        ) {
            return 'twitch';
        }

        if (
            $host === 'youtube.com'
            || str_ends_with(
                $host,
                '.youtube.com'
            )
            || $host === 'youtu.be'
        ) {
            return 'youtube';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTwitch(
        string $url
    ): ?array {
        if ($url === '') {
            return null;
        }

        $path =
            trim(
                (string) wp_parse_url(
                    $url,
                    PHP_URL_PATH
                ),
                '/'
            );

        if ($path === '') {
            return null;
        }

        $segments =
            array_values(
                array_filter(
                    explode(
                        '/',
                        $path
                    )
                )
            );

        if ($segments === []) {
            return null;
        }

        $channel =
            sanitize_key(
                (string) $segments[0]
            );

        if ($channel === '') {
            return null;
        }

        $parent =
            strtolower(
                (string) wp_parse_url(
                    home_url('/'),
                    PHP_URL_HOST
                )
            );

        if ($parent === '') {
            return null;
        }

        $embedUrl =
            add_query_arg(
                [
                    'channel' =>
                        $channel,

                    'parent' =>
                        $parent,

                    'autoplay' =>
                        'false',
                ],
                'https://player.twitch.tv/'
            );

        return [
            'type' =>
                'iframe',

            'platform' =>
                'twitch',

            'channel' =>
                $channel,

            'url' =>
                $url,

            'embed_url' =>
                $embedUrl,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveYouTube(
        string $url
    ): ?array {
        if ($url === '') {
            return null;
        }

        $host =
            strtolower(
                (string) wp_parse_url(
                    $url,
                    PHP_URL_HOST
                )
            );

        $host =
            preg_replace(
                '/^www\./',
                '',
                $host
            );

        $path =
            trim(
                (string) wp_parse_url(
                    $url,
                    PHP_URL_PATH
                ),
                '/'
            );

        $videoId = '';

        /*
         * https://youtu.be/VIDEO_ID
         */
        if ($host === 'youtu.be') {
            $segments =
                array_values(
                    array_filter(
                        explode(
                            '/',
                            $path
                        )
                    )
                );

            $videoId =
                isset($segments[0])
                    ? (string) $segments[0]
                    : '';
        }

        /*
         * https://youtube.com/watch?v=VIDEO_ID
         */
        if (
            $videoId === ''
            && (
                $host === 'youtube.com'
                || str_ends_with(
                    $host,
                    '.youtube.com'
                )
            )
        ) {
            $query =
                (string) wp_parse_url(
                    $url,
                    PHP_URL_QUERY
                );

            parse_str(
                $query,
                $queryParameters
            );

            if (
                isset($queryParameters['v'])
                && is_scalar(
                    $queryParameters['v']
                )
            ) {
                $videoId =
                    (string) $queryParameters['v'];
            }
        }

        /*
         * También soportamos:
         *
         * /live/VIDEO_ID
         * /embed/VIDEO_ID
         * /shorts/VIDEO_ID
         */
        if (
            $videoId === ''
            && $path !== ''
        ) {
            $segments =
                array_values(
                    array_filter(
                        explode(
                            '/',
                            $path
                        )
                    )
                );

            if (
                isset(
                    $segments[0],
                    $segments[1]
                )
                && in_array(
                    strtolower(
                        (string) $segments[0]
                    ),
                    [
                        'live',
                        'embed',
                        'shorts',
                    ],
                    true
                )
            ) {
                $videoId =
                    (string) $segments[1];
            }
        }

        $videoId =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '',
                $videoId
            );

        if ($videoId === '') {
            return null;
        }

        $origin =
            home_url('/');

        $embedUrl =
            add_query_arg(
                [
                    'autoplay' =>
                        '0',

                    'rel' =>
                        '0',

                    'origin' =>
                        $origin,
                ],
                'https://www.youtube.com/embed/'
                . rawurlencode(
                    $videoId
                )
            );

        return [
            'type' =>
                'iframe',

            'platform' =>
                'youtube',

            'video_id' =>
                $videoId,

            'url' =>
                $url,

            'embed_url' =>
                $embedUrl,
        ];
    }
}
