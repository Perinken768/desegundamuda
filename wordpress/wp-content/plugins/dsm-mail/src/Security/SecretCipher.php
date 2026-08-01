<?php

declare(strict_types=1);

namespace DSM\Mail\Security;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

final class SecretCipher
{
    private const CIPHER = 'aes-256-gcm';

    public function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }

        $ivLength = openssl_cipher_iv_length(
            self::CIPHER
        );

        if ($ivLength === false || $ivLength <= 0) {
            throw new RuntimeException(
                'No se pudo determinar el tamaño del vector de cifrado.'
            );
        }

        $iv = random_bytes($ivLength);
        $tag = '';

        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new RuntimeException(
                'No se pudo cifrar la contraseña SMTP.'
            );
        }

        return base64_encode(
            json_encode(
                [
                    'iv' => base64_encode($iv),
                    'tag' => base64_encode($tag),
                    'data' => base64_encode($encrypted),
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function decrypt(string $encryptedValue): string
    {
        if ($encryptedValue === '') {
            return '';
        }

        try {
            $decodedJson = base64_decode(
                $encryptedValue,
                true
            );

            if ($decodedJson === false) {
                return '';
            }

            $payload = json_decode(
                $decodedJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (
                !is_array($payload)
                || !isset(
                    $payload['iv'],
                    $payload['tag'],
                    $payload['data']
                )
            ) {
                return '';
            }

            $iv = base64_decode(
                (string) $payload['iv'],
                true
            );

            $tag = base64_decode(
                (string) $payload['tag'],
                true
            );

            $data = base64_decode(
                (string) $payload['data'],
                true
            );

            if (
                $iv === false
                || $tag === false
                || $data === false
            ) {
                return '';
            }

            $plainText = openssl_decrypt(
                $data,
                self::CIPHER,
                $this->getKey(),
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $plainText === false
                ? ''
                : $plainText;
        } catch (\Throwable) {
            return '';
        }
    }

    private function getKey(): string
    {
        $material = AUTH_KEY
            . SECURE_AUTH_KEY
            . AUTH_SALT
            . SECURE_AUTH_SALT;

        return hash(
            'sha256',
            $material,
            true
        );
    }
}