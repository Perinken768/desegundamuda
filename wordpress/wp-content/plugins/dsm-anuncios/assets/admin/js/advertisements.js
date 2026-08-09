(() => {
    'use strict';

    const SELECTOR =
        '[data-dsm-confirm-form]';

    const bindConfirmation =
        (form) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (
                form.dataset.dsmConfirmBound
                === '1'
            ) {
                return;
            }

            form.dataset.dsmConfirmBound =
                '1';

            form.addEventListener(
                'submit',
                (event) => {
                    const message =
                        (
                            form.getAttribute(
                                'data-dsm-confirm-form'
                            )
                            ?? ''
                        ).trim();

                    if (message === '') {
                        return;
                    }

                    const confirmed =
                        window.confirm(
                            message
                        );

                    if (confirmed) {
                        return;
                    }

                    event.preventDefault();
                }
            );
        };

    const initialize =
        () => {
            const forms =
                document.querySelectorAll(
                    SELECTOR
                );

            forms.forEach(
                bindConfirmation
            );
        };

    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            initialize,
            {
                once: true,
            }
        );

        return;
    }

    initialize();
})();