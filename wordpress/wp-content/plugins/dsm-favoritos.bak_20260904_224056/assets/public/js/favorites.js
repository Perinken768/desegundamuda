(function () {
    'use strict';

    function initializeFavoriteForms() {
        const forms =
            document.querySelectorAll(
                '[data-dsm-favorite-form]'
            );

        forms.forEach(function (form) {
            if (
                form.dataset.dsmFavoriteReady
                === '1'
            ) {
                return;
            }

            form.dataset.dsmFavoriteReady =
                '1';

            form.addEventListener(
                'submit',
                function () {
                    const button =
                        form.querySelector(
                            '[data-dsm-favorite-button]'
                        );

                    if (!button) {
                        return;
                    }

                    button.disabled =
                        true;

                    button.classList.add(
                        'is-loading'
                    );

                    button.setAttribute(
                        'aria-busy',
                        'true'
                    );
                }
            );
        });
    }

    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            initializeFavoriteForms
        );
    } else {
        initializeFavoriteForms();
    }
})();