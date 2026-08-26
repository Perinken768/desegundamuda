document.addEventListener(
    'DOMContentLoaded',
    () => {
        const carousels =
            document.querySelectorAll(
                '[data-dsm-advertising-carousel]'
            );

        carousels.forEach(
            (carousel) => {
                const slides = Array.from(
                    carousel.querySelectorAll(
                        '[data-dsm-advertising-slide]'
                    )
                );

                if (slides.length <= 1) {
                    return;
                }

                const previousButton =
                    carousel.querySelector(
                        '[data-dsm-advertising-previous]'
                    );

                const nextButton =
                    carousel.querySelector(
                        '[data-dsm-advertising-next]'
                    );

                const dots = Array.from(
                    carousel.querySelectorAll(
                        '[data-dsm-advertising-dot]'
                    )
                );

                const interval =
                    Math.max(
                        3000,
                        Number.parseInt(
                            carousel.dataset.interval
                                || '6000',
                            10
                        )
                        || 6000
                    );

                const reducedMotion =
                    window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches;

                let currentIndex = 0;
                let timer = null;

                const showSlide =
                    (requestedIndex) => {
                        currentIndex =
                            (
                                requestedIndex
                                + slides.length
                            )
                            % slides.length;

                        slides.forEach(
                            (slide, index) => {
                                const isActive =
                                    index === currentIndex;

                                slide.hidden =
                                    !isActive;

                                slide.classList.toggle(
                                    'is-active',
                                    isActive
                                );
                            }
                        );

                        dots.forEach(
                            (dot, index) => {
                                const isActive =
                                    index === currentIndex;

                                dot.classList.toggle(
                                    'is-active',
                                    isActive
                                );

                                if (isActive) {
                                    dot.setAttribute(
                                        'aria-current',
                                        'true'
                                    );
                                } else {
                                    dot.removeAttribute(
                                        'aria-current'
                                    );
                                }
                            }
                        );
                    };

                const stop =
                    () => {
                        if (timer !== null) {
                            window.clearInterval(
                                timer
                            );

                            timer = null;
                        }
                    };

                const start =
                    () => {
                        stop();

                        if (reducedMotion) {
                            return;
                        }

                        timer =
                            window.setInterval(
                                () => {
                                    showSlide(
                                        currentIndex + 1
                                    );
                                },
                                interval
                            );
                    };

                previousButton?.addEventListener(
                    'click',
                    () => {
                        showSlide(
                            currentIndex - 1
                        );

                        start();
                    }
                );

                nextButton?.addEventListener(
                    'click',
                    () => {
                        showSlide(
                            currentIndex + 1
                        );

                        start();
                    }
                );

                dots.forEach(
                    (dot) => {
                        dot.addEventListener(
                            'click',
                            () => {
                                const index =
                                    Number.parseInt(
                                        dot.dataset
                                            .dsmAdvertisingDot
                                            || '0',
                                        10
                                    );

                                showSlide(
                                    index
                                );

                                start();
                            }
                        );
                    }
                );

                carousel.addEventListener(
                    'mouseenter',
                    stop
                );

                carousel.addEventListener(
                    'mouseleave',
                    start
                );

                carousel.addEventListener(
                    'focusin',
                    stop
                );

                carousel.addEventListener(
                    'focusout',
                    () => {
                        window.setTimeout(
                            () => {
                                if (
                                    !carousel.contains(
                                        document.activeElement
                                    )
                                ) {
                                    start();
                                }
                            },
                            0
                        );
                    }
                );

                showSlide(0);
                start();
            }
        );
    }
);
