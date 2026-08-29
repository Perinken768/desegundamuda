(function () {
    'use strict';

    function initCarousel(carousel) {
        const viewport =
            carousel.querySelector(
                '[data-dsm-carousel-viewport]'
            );

        const previousButton =
            carousel.querySelector(
                '[data-dsm-carousel-previous]'
            );

        const nextButton =
            carousel.querySelector(
                '[data-dsm-carousel-next]'
            );

        if (
            !viewport
            || !previousButton
            || !nextButton
        ) {
            return;
        }

        function getScrollAmount() {
            const card =
                viewport.querySelector(
                    '.dsm-promotion-card'
                );

            if (!card) {
                return viewport.clientWidth;
            }

            const styles =
                window.getComputedStyle(
                    viewport
                );

            const gap =
                parseFloat(
                    styles.columnGap
                    || styles.gap
                    || '0'
                );

            return card.getBoundingClientRect().width
                + gap;
        }

        function updateButtons() {
            const maximum =
                Math.max(
                    0,
                    viewport.scrollWidth
                    - viewport.clientWidth
                );

            const position =
                viewport.scrollLeft;

            previousButton.disabled =
                position <= 2;

            nextButton.disabled =
                position >= maximum - 2;

            carousel.classList.toggle(
                'is-scrollable',
                maximum > 2
            );
        }

        previousButton.addEventListener(
            'click',
            function () {
                viewport.scrollBy({
                    left:
                        -getScrollAmount(),
                    behavior:
                        'smooth'
                });
            }
        );

        nextButton.addEventListener(
            'click',
            function () {
                viewport.scrollBy({
                    left:
                        getScrollAmount(),
                    behavior:
                        'smooth'
                });
            }
        );

        viewport.addEventListener(
            'scroll',
            function () {
                window.requestAnimationFrame(
                    updateButtons
                );
            },
            {
                passive:
                    true
            }
        );

        window.addEventListener(
            'resize',
            updateButtons
        );

        updateButtons();
    }

    function init() {
        document
            .querySelectorAll(
                '[data-dsm-promotion-carousel]'
            )
            .forEach(
                initCarousel
            );
    }

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            init
        );
    } else {
        init();
    }
})();
