(function () {
    'use strict';

    /**
     * Inicializa el filtrado de municipios.
     */
    function initializeMunicipalitySearch() {
        const searchInput =
            document.querySelector(
                '[data-dsm-municipality-search]'
            );

        const rows =
            document.querySelectorAll(
                '[data-dsm-municipality-row]'
            );

        if (
            !(searchInput instanceof HTMLInputElement)
            || rows.length === 0
        ) {
            return;
        }

        function normalizeText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(
                    /[\u0300-\u036f]/g,
                    ''
                )
                .toLowerCase()
                .trim();
        }

        function filterRows() {
            const searchValue =
                normalizeText(
                    searchInput.value
                );

            rows.forEach(
                function (row) {
                    if (
                        !(row instanceof HTMLTableRowElement)
                    ) {
                        return;
                    }

                    const rowSearchValue =
                        normalizeText(
                            row.getAttribute(
                                'data-search'
                            )
                        );

                    const isVisible =
                        searchValue === ''
                        || rowSearchValue.includes(
                            searchValue
                        );

                    row.hidden =
                        !isVisible;
                }
            );
        }

        searchInput.addEventListener(
            'input',
            filterRows
        );
    }

    /**
     * Inicializa el selector de área superior
     * del formulario de alta de áreas.
     */
    function initializeAreaParentSelector() {
        const areaForm =
            document.querySelector(
                '[data-dsm-area-form]'
            );

        if (!(areaForm instanceof HTMLFormElement)) {
            return;
        }

        const countrySelect =
            areaForm.querySelector(
                '[data-dsm-country-select]'
            );

        const parentSelect =
            areaForm.querySelector(
                '[data-dsm-parent-area-select]'
            );

        if (
            !(countrySelect instanceof HTMLSelectElement)
            || !(parentSelect instanceof HTMLSelectElement)
        ) {
            return;
        }

        const originalOptions =
            Array.from(
                parentSelect.options
            ).map(
                function (option) {
                    return {
                        value:
                            option.value,

                        text:
                            option.textContent
                            || '',

                        countryId:
                            option.getAttribute(
                                'data-country-id'
                            )
                            || '',

                        isDefault:
                            option.value === '0',
                    };
                }
            );

        function rebuildParentOptions() {
            const selectedCountryId =
                String(
                    countrySelect.value
                    || ''
                );

            const previousValue =
                String(
                    parentSelect.value
                    || '0'
                );

            parentSelect.innerHTML = '';

            originalOptions.forEach(
                function (optionData) {
                    if (
                        !optionData.isDefault
                        && optionData.countryId
                            !== selectedCountryId
                    ) {
                        return;
                    }

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        optionData.value;

                    option.textContent =
                        optionData.text;

                    if (
                        optionData.countryId !== ''
                    ) {
                        option.setAttribute(
                            'data-country-id',
                            optionData.countryId
                        );
                    }

                    parentSelect.appendChild(
                        option
                    );
                }
            );

            const previousOptionExists =
                Array.from(
                    parentSelect.options
                ).some(
                    function (option) {
                        return option.value
                            === previousValue;
                    }
                );

            parentSelect.value =
                previousOptionExists
                    ? previousValue
                    : '0';
        }

        countrySelect.addEventListener(
            'change',
            rebuildParentOptions
        );

        rebuildParentOptions();
    }

    /**
     * Evita dobles envíos accidentales.
     */
    function initializeSubmitProtection() {
        const forms =
            document.querySelectorAll(
                '.dsm-locations-admin form'
            );

        forms.forEach(
            function (form) {
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                form.addEventListener(
                    'submit',
                    function () {
                        const submitButtons =
                            document.querySelectorAll(
                                '[form="'
                                + CSS.escape(
                                    form.id
                                )
                                + '"], form#'
                                + CSS.escape(
                                    form.id
                                )
                                + ' button[type="submit"]'
                            );

                        submitButtons.forEach(
                            function (button) {
                                if (
                                    button instanceof HTMLButtonElement
                                    || button instanceof HTMLInputElement
                                ) {
                                    button.disabled =
                                        true;
                                }
                            }
                        );
                    }
                );
            }
        );
    }

    function initializeLocationsAdmin() {
        initializeMunicipalitySearch();
        initializeAreaParentSelector();
        initializeSubmitProtection();
    }

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            initializeLocationsAdmin
        );
    } else {
        initializeLocationsAdmin();
    }
})();