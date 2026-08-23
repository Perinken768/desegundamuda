<?php

declare(strict_types=1);

namespace DSM\Multitienda\Integration;

use DSM\Catalogo\Product\ProductRepository;
use DSM\Catalogo\Reservation\ProductReservation;
use DSM\Catalogo\Variant\ProductVariantRepository;
use DSM\Core\Mail\MailerRegistry;
use DSM\Multitienda\Store\Store;
use DSM\Whatsapp\Application\WhatsAppLinkBuilder;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class ReservationNotificationService
{
    /**
     * @param array<string, mixed> $buyerContext
     */
    public function notify(
        Store $store,
        ProductReservation $reservation,
        array $buyerContext
    ): void {
        if (!MailerRegistry::has()) {
            throw new RuntimeException(
                'No hay ningún servicio de correo disponible.'
            );
        }

        $product =
            (new ProductRepository())
                ->findById(
                    $reservation->getProductId()
                );

        if ($product === null) {
            throw new RuntimeException(
                'No se encontró el producto de la reserva.'
            );
        }

        $variant =
            (new ProductVariantRepository())
                ->findById(
                    $reservation->getVariantId()
                );

        if ($variant === null) {
            throw new RuntimeException(
                'No se encontró la variante de la reserva.'
            );
        }

        $sellerContext =
            apply_filters(
                'dsm_customer_context_by_id',
                null,
                $store->getCustomerId()
            );

        if (!is_array($sellerContext)) {
            throw new RuntimeException(
                'No se pudo obtener el vendedor de la reserva.'
            );
        }

        $sellerEmail =
            sanitize_email(
                (string) (
                    $sellerContext['email']
                    ?? ''
                )
            );

        $sellerName =
            trim(
                (string) (
                    $sellerContext['display_name']
                    ?? ''
                )
            );

        if ($sellerName === '') {
            $sellerName =
                $store->getName();
        }

        $buyerEmail =
            sanitize_email(
                (string) (
                    $buyerContext['email']
                    ?? ''
                )
            );

        if (!is_email($sellerEmail)) {
            throw new RuntimeException(
                'El vendedor no tiene un correo electrónico válido.'
            );
        }

        if (!is_email($buyerEmail)) {
            throw new RuntimeException(
                'El comprador no tiene un correo electrónico válido.'
            );
        }

        $buyerName =
            trim(
                (string) (
                    $buyerContext['display_name']
                    ?? ''
                )
            );

        if ($buyerName === '') {
            $buyerName =
                $buyerEmail;
        }

        $variantDetails = [];

        if ($variant->hasSize()) {
            $variantDetails[] =
                'Talla: '
                . $variant->getSizeValue();
        }

        if ($variant->hasColor()) {
            $variantDetails[] =
                'Color: '
                . $variant->getColorValue();
        }

        $variantText =
            $variantDetails !== []
                ? implode(
                    ' · ',
                    $variantDetails
                )
                : 'Variante #'
                    . $variant->getId();

        /*
         * =====================================================
         * WHATSAPP VENDEDOR -> COMPRADOR
         * =====================================================
         *
         * El mensaje se abre ya preparado en WhatsApp,
         * pero el vendedor puede modificarlo libremente
         * antes de enviarlo.
         */

        $quantity =
            $reservation->getQuantity();

        $unitText =
            $quantity === 1
                ? 'unidad'
                : 'unidades';

        $whatsappMessage =
            sprintf(
                "Hola, soy %s.\n\n"
                . "He recibido su reserva.\n\n"
                . "Me pongo en contacto con usted para organizar la entrega.\n\n"
                . "Un saludo.",
                $sellerName
            );

        $buyerWhatsappUrl =
            (new WhatsAppLinkBuilder())
                ->buildForCustomer(
                    $reservation
                        ->getBuyerCustomerId(),
                    $whatsappMessage
                );

        $reservationUrl =
            add_query_arg(
                [
                    'store_section' =>
                        'reservations',

                    'reservation_id' =>
                        $reservation->getId(),
                ],
                home_url(
                    '/mi-tienda/'
                )
            );

        $sellerSubject =
            sprintf(
                'Nueva reserva #%d en DeSegundaMuda',
                $reservation->getId()
            );

        $sellerMessage =
            $this->buildSellerMessage(
                storeName:
                    $store->getName(),

                buyerName:
                    $buyerName,

                productName:
                    $product->getName(),

                variantText:
                    $variantText,

                quantity:
                    $reservation->getQuantity(),

                reservationId:
                    $reservation->getId(),

                reservationUrl:
                    $reservationUrl,

                whatsappUrl:
                    $buyerWhatsappUrl
            );

        $buyerSubject =
            sprintf(
                'Reserva #%d recibida en DeSegundaMuda',
                $reservation->getId()
            );

        $buyerMessage =
            $this->buildBuyerMessage(
                buyerName:
                    $buyerName,

                storeName:
                    $store->getName(),

                productName:
                    $product->getName(),

                variantText:
                    $variantText,

                quantity:
                    $reservation->getQuantity(),

                reservationId:
                    $reservation->getId()
            );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        $errors = [];

        try {
            MailerRegistry::get()->send(
                $sellerEmail,
                $sellerSubject,
                $sellerMessage,
                $headers
            );
        } catch (Throwable $exception) {
            $errors[] =
                'vendedor: '
                . $exception->getMessage();
        }

        /*
         * Aunque falle el correo del vendedor,
         * intentamos igualmente informar al comprador.
         */
        try {
            MailerRegistry::get()->send(
                $buyerEmail,
                $buyerSubject,
                $buyerMessage,
                $headers
            );
        } catch (Throwable $exception) {
            $errors[] =
                'comprador: '
                . $exception->getMessage();
        }

        if ($errors !== []) {
            throw new RuntimeException(
                'Falló el envío de notificaciones de reserva ('
                . implode(
                    '; ',
                    $errors
                )
                . ').'
            );
        }
    }

    private function buildSellerMessage(
        string $storeName,
        string $buyerName,
        string $productName,
        string $variantText,
        int $quantity,
        int $reservationId,
        string $reservationUrl,
        string $whatsappUrl
    ): string {
        return sprintf(
            '<div style="font-family:Arial,sans-serif;line-height:1.6;max-width:680px;margin:auto;">'
            . '<h2>Nueva reserva en DeSegundaMuda</h2>'
            . '<p>Hola,</p>'
            . '<p><strong>%s</strong> ha realizado una nueva reserva en <strong>%s</strong>.</p>'
            . '<table style="border-collapse:collapse;width:100%%;margin:20px 0;">'
            . '<tr><td><strong>Reserva</strong></td><td>#%d</td></tr>'
            . '<tr><td><strong>Producto</strong></td><td>%s</td></tr>'
            . '<tr><td><strong>Variante</strong></td><td>%s</td></tr>'
            . '<tr><td><strong>Cantidad</strong></td><td>%d</td></tr>'
            . '</table>'
            . '<p>Contacta con el comprador para acordar los detalles de la entrega.</p>'
            . '<p style="margin:24px 0;">'
            . '<a href="%s" style="%s">Contactar por WhatsApp</a>'
            . '</p>'
            . '<p style="margin:24px 0;">'
            . '<a href="%s" style="%s">Gestionar reserva</a>'
            . '</p>'
            . '<p>Completa o libera la reserva desde tu panel de DeSegundaMuda.</p>'
            . '</div>',
            esc_html($buyerName),
            esc_html($storeName),
            $reservationId,
            esc_html($productName),
            esc_html($variantText),
            $quantity,
            esc_url($whatsappUrl),
            $this->buttonStyle(),
            esc_url($reservationUrl),
            $this->buttonStyle()
        );
    }

    private function buildBuyerMessage(
        string $buyerName,
        string $storeName,
        string $productName,
        string $variantText,
        int $quantity,
        int $reservationId
    ): string {
        return sprintf(
            '<div style="font-family:Arial,sans-serif;line-height:1.6;max-width:680px;margin:auto;">'
            . '<h2>Hemos recibido tu reserva</h2>'
            . '<p>Hola %s,</p>'
            . '<p>Tu reserva se ha tramitado correctamente.</p>'
            . '<table style="border-collapse:collapse;width:100%%;margin:20px 0;">'
            . '<tr><td><strong>Reserva</strong></td><td>#%d</td></tr>'
            . '<tr><td><strong>Tienda</strong></td><td>%s</td></tr>'
            . '<tr><td><strong>Producto</strong></td><td>%s</td></tr>'
            . '<tr><td><strong>Variante</strong></td><td>%s</td></tr>'
            . '<tr><td><strong>Cantidad</strong></td><td>%d</td></tr>'
            . '</table>'
            . '<p>Hemos avisado al vendedor y se pondrá en contacto contigo a la mayor brevedad posible.</p>'
            . '<p>Un saludo,<br>DeSegundaMuda</p>'
            . '</div>',
            esc_html($buyerName),
            $reservationId,
            esc_html($storeName),
            esc_html($productName),
            esc_html($variantText),
            $quantity
        );
    }

    private function buttonStyle(): string
    {
        return
            'display:inline-block;'
            . 'padding:10px 14px;'
            . 'margin:4px;'
            . 'background:#2271b1;'
            . 'color:#ffffff;'
            . 'text-decoration:none;'
            . 'border-radius:4px;';
    }
}
