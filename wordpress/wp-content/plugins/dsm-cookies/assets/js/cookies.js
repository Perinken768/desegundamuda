document.addEventListener(
    'DOMContentLoaded',
    () => {
        const consent =
            document.querySelector(
                '[data-dsm-cookie-consent]'
            );

        if (!consent) {
            return;
        }

        const preferences =
            consent.querySelector(
                '[data-dsm-cookie-preferences]'
            );

        const configureButton =
            consent.querySelector(
                '[data-dsm-cookie-configure]'
            );

        const saveButton =
            consent.querySelector(
                '[data-dsm-cookie-save]'
            );

        const openPreferences =
            () => {
                consent.hidden = false;

                if (preferences) {
                    preferences.hidden = false;
                }

                if (configureButton) {
                    configureButton.hidden = true;
                }

                if (saveButton) {
                    saveButton.hidden = false;
                }
            };

        if (configureButton) {
            configureButton.addEventListener(
                'click',
                openPreferences
            );
        }

        document
            .querySelectorAll(
                '[data-dsm-cookie-open]'
            )
            .forEach(
                (button) => {
                    button.addEventListener(
                        'click',
                        (event) => {
                            event.preventDefault();

                            openPreferences();
                        }
                    );
                }
            );
    }
);
