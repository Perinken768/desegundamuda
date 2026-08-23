<?php

declare(strict_types=1);

namespace DSM\Multitienda\Application;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class StoreLogoService
{
    private const FIELD_NAME =
        'store_logo';

    private const MAX_FILE_SIZE =
        5 * 1024 * 1024;

    /**
     * Devuelve el attachment ID creado.
     *
     * Si no se ha enviado ningún archivo,
     * devuelve null.
     */
    public function upload(): ?int
    {
        if (
            !isset($_FILES[self::FIELD_NAME])
            || !is_array(
                $_FILES[self::FIELD_NAME]
            )
        ) {
            return null;
        }

        $file =
            $_FILES[self::FIELD_NAME];

        $error =
            isset($file['error'])
                ? (int) $file['error']
                : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'No se pudo recibir correctamente el logo.'
            );
        }

        $size =
            isset($file['size'])
                ? (int) $file['size']
                : 0;

        if ($size <= 0) {
            throw new RuntimeException(
                'El archivo del logo está vacío.'
            );
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new RuntimeException(
                'El logo no puede superar los 5 MB.'
            );
        }

        $temporaryName =
            isset($file['tmp_name'])
                ? (string) $file['tmp_name']
                : '';

        $originalName =
            isset($file['name'])
                ? sanitize_file_name(
                    (string) $file['name']
                )
                : '';

        if (
            $temporaryName === ''
            || $originalName === ''
        ) {
            throw new RuntimeException(
                'El archivo del logo no es válido.'
            );
        }

        $fileInfo =
            wp_check_filetype_and_ext(
                $temporaryName,
                $originalName
            );

        $mimeType =
            isset($fileInfo['type'])
                ? (string) $fileInfo['type']
                : '';

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (
            $mimeType === ''
            || !in_array(
                $mimeType,
                $allowedMimeTypes,
                true
            )
        ) {
            throw new RuntimeException(
                'El logo debe ser JPG, PNG o WEBP.'
            );
        }

        require_once ABSPATH
            . 'wp-admin/includes/file.php';

        require_once ABSPATH
            . 'wp-admin/includes/media.php';

        require_once ABSPATH
            . 'wp-admin/includes/image.php';

        $attachmentId =
            media_handle_upload(
                self::FIELD_NAME,
                0
            );

        if (is_wp_error($attachmentId)) {
            throw new RuntimeException(
                'No se pudo guardar el logo: '
                . $attachmentId
                    ->get_error_message()
            );
        }

        $attachmentId =
            (int) $attachmentId;

        if ($attachmentId <= 0) {
            throw new RuntimeException(
                'WordPress no devolvió un identificador válido para el logo.'
            );
        }

        return $attachmentId;
    }

    public function delete(
        ?int $attachmentId
    ): void {
        if (
            $attachmentId === null
            || $attachmentId <= 0
        ) {
            return;
        }

        wp_delete_attachment(
            $attachmentId,
            true
        );
    }
}
