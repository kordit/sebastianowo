/**
 * Tab Manager
 * Zarządza zakładkami lokalizacji
 */

(function ($) {
    'use strict';

    class TabManager {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initLocationTabs();
            this.initFormTabs();
        }

        bindEvents() {
            // Location tabs
            $(document).on('click', '.location-tabs .nav-tab', this.switchTab.bind(this));
        }

        initLocationTabs() {
            // Ustaw aktywny tab na podstawie URL hash lub pierwszy tab
            this.setActiveTabFromUrl();
        }

        initFormTabs() {
            // Obsługa zakładek w formularzach NPC
            $('.form-tabs .nav-tab').on('click', this.switchFormTab.bind(this));

            // Ustaw aktywną zakładkę formularza
            $('.form-tabs .nav-tab:first').addClass('nav-tab-active');
            $('.form-tab-content:first').addClass('active');
        }

        switchTab(event) {
            event.preventDefault();

            const $clickedTab = $(event.currentTarget);
            const targetLocation = $clickedTab.data('location');

            console.log('Switching to tab:', targetLocation);

            // Usuń aktywną klasę ze wszystkich tabów
            $('.location-tabs .nav-tab').removeClass('nav-tab-active');

            // Dodaj aktywną klasę do klikniętego tabu
            $clickedTab.addClass('nav-tab-active');

            // Ukryj wszystkie zawartości tabów
            $('.tab-pane').removeClass('active');

            // Pokaż zawartość odpowiedniego tabu
            $(`#tab-${targetLocation}`).addClass('active');

            // Aktualizuj URL hash (opcjonalnie)
            if (history.pushState) {
                const newUrl = window.location.pathname + window.location.search + '#tab-' + targetLocation;
                history.pushState(null, null, newUrl);
            }

            // Re-initialize sortable for the new visible dialogs
            window.sortableManager?.reinitializeSortableForTab(targetLocation);
        }

        switchFormTab(event) {
            event.preventDefault();

            const $clickedTab = $(event.currentTarget);
            const targetTab = $clickedTab.data('tab');

            // Usuń aktywną klasę ze wszystkich tabów
            $('.form-tabs .nav-tab').removeClass('nav-tab-active');

            // Dodaj aktywną klasę do klikniętego tabu
            $clickedTab.addClass('nav-tab-active');

            // Ukryj wszystkie zawartości tabów
            $('.form-tab-content').removeClass('active');

            // Pokaż zawartość odpowiedniego tabu
            $(`#${targetTab}`).addClass('active');
        }

        setActiveTabFromUrl() {
            const hash = window.location.hash;

            if (hash && hash.startsWith('#tab-')) {
                const targetLocation = hash.substring(5); // Remove '#tab-'
                const $targetTab = $(`.location-tabs .nav-tab[data-location="${targetLocation}"]`);

                if ($targetTab.length > 0) {
                    // Trigger click on the target tab
                    $targetTab.trigger('click');
                    return;
                }
            }

            // If no valid hash found, ensure first tab is active
            const $firstTab = $('.location-tabs .nav-tab:first');
            if ($firstTab.length > 0) {
                $firstTab.trigger('click');
            }
        }
    }

    // Globalna instancja
    window.TabManager = TabManager;
    window.tabManager = new TabManager();

})(jQuery);
