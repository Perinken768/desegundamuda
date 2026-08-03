(function ($) {
    'use strict';

    $(function () {
        initBrandLogoSelector();
        initConfirmationLinks();
    });

    function initBrandLogoSelector() {
        const selectButton =
            document.getElementById(
                'dsm-select-brand-logo'
            );

        const removeButton =
            document.getElementById(
                'dsm-remove-brand-logo'
            );

        const logoInput =
            document.getElementById(
                'dsm-brand-logo-id'
            );

        const preview =
            document.getElementById(
                'dsm-brand-logo-preview'
            );

        if (
            !selectButton
            || !removeButton
            || !logoInput
            || !preview
        ) {
            return;
        }

        if (
            typeof wp === 'undefined'
            || typeof wp.media !== 'function'
        ) {
            return;
        }

        let mediaFrame = null;

        selectButton.addEventListener(
            'click',
            function (event) {
                event.preventDefault();

                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }

                mediaFrame = wp.media({
                    title: 'Seleccionar logotipo de marca',
                    button: {
                        text: 'Usar este logotipo'
                    },
                    library: {
                        type: 'image'
                    },
                    multiple: false
                });

                mediaFrame.on(
                    'select',
                    function () {
                        const selection =
                            mediaFrame
                                .state()
                                .get('selection')
                                .first();

                        if (!selection) {
                            return;
                        }

                        const attachment =
                            selection.toJSON();

                        const imageUrl =
                            getAttachmentPreviewUrl(
                                attachment
                            );

                        logoInput.value =
                            String(
                                attachment.id
                            );

                        preview.innerHTML = '';

                        const image =
                            document.createElement(
                                'img'
                            );

                        image.src =
                            imageUrl;

                        image.alt =
                            attachment.alt
                            || attachment.title
                            || '';

                        image.className =
                            'dsm-brand-logo-image';

                        preview.appendChild(
                            image
                        );

                        removeButton.disabled =
                            false;
                    }
                );

                mediaFrame.open();
            }
        );

        removeButton.addEventListener(
            'click',
            function (event) {
                event.preventDefault();

                logoInput.value = '';

                preview.innerHTML = '';

                const placeholder =
                    document.createElement(
                        'div'
                    );

                placeholder.className =
                    'dsm-media-placeholder';

                const icon =
                    document.createElement(
                        'span'
                    );

                icon.className =
                    'dashicons dashicons-format-image';

                icon.setAttribute(
                    'aria-hidden',
                    'true'
                );

                const text =
                    document.createElement(
                        'span'
                    );

                text.textContent =
                    'Sin logotipo seleccionado';

                placeholder.appendChild(
                    icon
                );

                placeholder.appendChild(
                    text
                );

                preview.appendChild(
                    placeholder
                );

                removeButton.disabled =
                    true;
            }
        );
    }

    function getAttachmentPreviewUrl(
        attachment
    ) {
        if (
            attachment.sizes
            && attachment.sizes.medium
            && attachment.sizes.medium.url
        ) {
            return attachment.sizes.medium.url;
        }

        if (
            attachment.sizes
            && attachment.sizes.thumbnail
            && attachment.sizes.thumbnail.url
        ) {
            return attachment.sizes.thumbnail.url;
        }

        return attachment.url || '';
    }

    function initConfirmationLinks() {
        const links =
            document.querySelectorAll(
                '[data-dsm-confirm]'
            );

        links.forEach(
            function (link) {
                link.addEventListener(
                    'click',
                    function (event) {
                        const message =
                            link.getAttribute(
                                'data-dsm-confirm'
                            );

                        if (
                            message
                            && !window.confirm(message)
                        ) {
                            event.preventDefault();
                        }
                    }
                );
            }
        );
    }
})(jQuery);