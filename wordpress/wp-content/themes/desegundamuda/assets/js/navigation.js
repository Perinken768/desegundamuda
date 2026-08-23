document.addEventListener(
    'DOMContentLoaded',
    () => {
        const button =
            document.querySelector(
                '.dsm-navigation-toggle'
            );

        const navigation =
            document.querySelector(
                '.dsm-primary-navigation'
            );

        if (!button || !navigation) {
            return;
        }

        button.addEventListener(
            'click',
            () => {
                const expanded =
                    button.getAttribute(
                        'aria-expanded'
                    ) === 'true';

                button.setAttribute(
                    'aria-expanded',
                    expanded
                        ? 'false'
                        : 'true'
                );

                navigation.classList.toggle(
                    'dsm-primary-navigation--open',
                    !expanded
                );
            }
        );
    }
);
