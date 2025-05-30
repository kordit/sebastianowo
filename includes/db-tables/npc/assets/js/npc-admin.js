/**
 * NPC Admin Panel JavaScript
 */

(function ($) {
    'use strict';

    class NPCAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initImageUploader();
            this.initSortable();
            this.initLocationTabs();
            this.initFormTabs(); // Dodanie inicjalizacji zakładek formularza
        }

        bindEvents() {
            // Modal controls dla dialogów
            $(document).on('click', '#add-dialog-btn', this.openDialogModal.bind(this, 'create'));
            $(document).on('click', '.edit-dialog-btn', this.openDialogModal.bind(this, 'edit'));
            $(document).on('click', '.modal-close, .modal-cancel', this.closeModal.bind(this));
            $(document).on('click', '.npc-modal', this.closeModalOnOverlay.bind(this));

            // Modal controls dla odpowiedzi
            $(document).on('click', '.add-answer-btn', this.openAnswerModal.bind(this, 'create'));
            $(document).on('click', '.edit-answer-btn', this.openAnswerModal.bind(this, 'edit'));

            // Location tabs
            $(document).on('click', '.location-tabs .nav-tab', this.switchTab.bind(this));

            // Form validation
            $(document).on('submit', '#dialog-form', this.validateDialogForm.bind(this));
            $(document).on('submit', '#answer-form', this.validateAnswerForm.bind(this));
            $(document).on('submit', '.npc-form', this.validateNPCForm.bind(this));

            // Auto-save drafts (optional)
            this.setupAutoSave();
        }

        openDialogModal(mode, event) {
            event.preventDefault();

            const $modal = $('#dialog-modal');
            const $form = $('#dialog-form');

            // Reset form
            $form[0].reset();
            $('#dialog-id').val('');

            if (mode === 'create') {
                $('#modal-title').text('Dodaj nowy dialog');
                $('#dialog-action').val('create_dialog');
            } else if (mode === 'edit') {
                const $dialogItem = $(event.target).closest('.dialog-item');
                const dialogId = $dialogItem.data('dialog-id');

                $('#modal-title').text('Edytuj dialog');
                $('#dialog-action').val('update_dialog');
                $('#dialog-id').val(dialogId);

                // Load dialog data via AJAX
                this.loadDialogData(dialogId);
            }

            $modal.fadeIn(200);
            $('#dialog_title').focus();
        }

        openAnswerModal(mode, event) {
            event.preventDefault();

            const $modal = $('#answer-modal');
            const $form = $('#answer-form');

            // Reset form
            $form[0].reset();
            $('#answer-id').val('');

            if (mode === 'create') {
                const dialogId = $(event.target).data('dialog-id');
                $('#answer-modal-title').text('Dodaj nową odpowiedź');
                $('#answer-action').val('create_answer');
                $('#answer-dialog-id').val(dialogId);

                // Resetuj warunki
                $('.conditions-manager[data-context="answer"] .conditions-data').val('[]');
                this.clearConditionsUI('answer');
            } else if (mode === 'edit') {
                const answerId = $(event.target).data('answer-id');
                const dialogId = $(event.target).data('dialog-id');

                $('#answer-modal-title').text('Edytuj odpowiedź');
                $('#answer-action').val('update_answer');
                $('#answer-id').val(answerId);
                $('#answer-dialog-id').val(dialogId);

                // Load answer data via AJAX
                this.loadAnswerData(answerId);
            }

            $modal.fadeIn(200);
            $('#answer_text').focus();
        }

        closeModal() {
            // Dodajemy efekt płynnego wyjścia
            $('.npc-modal').fadeOut(300, function () {
                // Resetujemy formularze po ukryciu modalu
                $('#dialog-form, #answer-form').trigger('reset');
            });
        }

        closeModalOnOverlay(event) {
            // Zamykamy modal tylko gdy kliknięcie trafia w overlay, nie w jego zawartość
            if (event.target === event.currentTarget) {
                this.closeModal();
            }
        }

        loadDialogData(dialogId) {
            const data = {
                action: 'npc_get_dialog',
                dialog_id: dialogId,
                nonce: npcAdmin.nonce
            };

            $.post(npcAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        const dialog = response.data;
                        $('#dialog_title').val(dialog.title);
                        $('#dialog_content').val(dialog.content);
                        $('#dialog_order').val(dialog.dialog_order);

                        // Load location field
                        if (dialog.location) {
                            $('#dialog_location').val(dialog.location);
                        } else {
                            $('#dialog_location').val('');
                        }

                        // Nie używamy już pola is_starting_dialog
                        // Dialog początkowy to ten, który ma najmniejszy dialog_order
                    } else {
                        this.showNotice('Błąd podczas ładowania danych dialogu.', 'error');
                    }
                })
                .fail(() => {
                    this.showNotice('Błąd połączenia z serwerem.', 'error');
                });
        }

        loadAnswerData(answerId) {
            const data = {
                action: 'npc_get_answer',
                answer_id: answerId,
                nonce: npcAdmin.nonce
            };

            $.post(npcAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        const answer = response.data;
                        $('#answer_text').val(answer.text);
                        $('#answer_order').val(answer.answer_order);
                        $('#answer_next_dialog_id').val(answer.next_dialog_id || '');

                        // Załaduj akcje jeśli istnieją
                        if (window.answerActionsManager && answer.actions) {
                            let actions = [];
                            try {
                                actions = typeof answer.actions === 'string' ? JSON.parse(answer.actions) : answer.actions;
                            } catch (e) {
                                console.error('Error parsing actions:', e);
                                actions = [];
                            }
                            window.answerActionsManager.loadActions(actions);
                        }

                    } else {
                        this.showNotice('Błąd podczas ładowania danych odpowiedzi.', 'error');
                    }
                })
                .fail(() => {
                    this.showNotice('Błąd połączenia z serwerem.', 'error');
                });
        }

        clearConditionsUI(context) {
            const $manager = $(`.conditions-manager[data-context="${context}"]`);
            $manager.find('.conditions-list').html('<div class="no-conditions"><p>Brak warunków. Element będzie zawsze widoczny.</p></div>');
        }

        validateNPCForm(event) {
            const name = $('#name').val().trim();

            if (!name) {
                event.preventDefault();
                this.showNotice('Nazwa NPC jest wymagana.', 'error');
                $('#name').focus();
                // Przełącz na zakładkę z danymi podstawowymi
                $('.npc-tab-link[href="#tab-basic"]').click();
                return false;
            }

            // Additional validation
            const imageUrl = $('#image_url').val().trim();
            if (imageUrl && !this.isValidUrl(imageUrl)) {
                event.preventDefault();
                this.showNotice('Podaj prawidłowy URL obrazka.', 'error');
                $('#image_url').focus();
                // Przełącz na zakładkę z danymi podstawowymi
                $('.npc-tab-link[href="#tab-basic"]').click();
                return false;
            }

            // Sprawdź czy statystyki są w zakresie 0-100
            const stats = ['strength', 'defence', 'dexterity', 'perception', 'technical', 'charisma',
                'combat', 'steal', 'craft', 'trade', 'relations', 'street'];

            let invalidStat = false;
            let tabToShow = '';

            stats.forEach(stat => {
                const value = parseInt($('#' + stat).val() || 0);
                if (value < 0 || value > 100) {
                    event.preventDefault();
                    invalidStat = stat;

                    // Określ odpowiednią zakładkę dla danej statystyki
                    if (['strength', 'defence', 'dexterity', 'perception', 'technical', 'charisma'].includes(stat)) {
                        tabToShow = '#tab-stats';
                    } else {
                        tabToShow = '#tab-skills';
                    }

                    return false; // przerwij pętlę forEach
                }
            });

            if (invalidStat) {
                this.showNotice(`Wartość dla ${invalidStat} musi być między 0 a 100.`, 'error');
                $('#' + invalidStat).focus();
                // Przełącz na odpowiednią zakładkę
                $('.npc-tab-link[href="' + tabToShow + '"]').click();
                return false;
            }

            return true;
        }

        validateDialogForm(event) {
            const title = $('#dialog_title').val().trim();
            const content = $('#dialog_content').val().trim();

            if (!title) {
                event.preventDefault();
                this.showNotice('Tytuł dialogu jest wymagany.', 'error');
                $('#dialog_title').focus();
                return false;
            }

            if (!content) {
                event.preventDefault();
                this.showNotice('Treść dialogu jest wymagana.', 'error');
                $('#dialog_content').focus();
                return false;
            }

            return true;
        }

        validateAnswerForm(event) {
            const text = $('#answer_text').val().trim();

            if (!text) {
                event.preventDefault();
                this.showNotice('Tekst odpowiedzi jest wymagany.', 'error');
                $('#answer_text').focus();
                return false;
            }

            // Usunięto walidację warunków

            return true;
        }

        initImageUploader() {
            let mediaUploader;

            $(document).on('click', '#upload-image-btn', function (e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Wybierz obrazek NPC',
                    button: {
                        text: 'Wybierz obrazek'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                mediaUploader.on('select', function () {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#image_url').val(attachment.url);

                    // Show preview
                    let $preview = $('.image-preview');
                    if ($preview.length === 0) {
                        $preview = $('<div class="image-preview"></div>');
                        $('#image_url').parent().append($preview);
                    }

                    $preview.html(`<img src="${attachment.url}" style="max-width: 100px; height: auto; margin-top: 10px;">`);
                });

                mediaUploader.open();
            });
        }

        setupAutoSave() {
            let autoSaveTimeout;

            $(document).on('input', '.npc-form input, .npc-form textarea, .npc-form select', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    this.autoSaveDraft();
                }, 5000); // Auto-save after 5 seconds of inactivity
            });
        }

        autoSaveDraft() {
            const $form = $('.npc-form');
            const npcId = $('input[name="npc_id"]').val();

            if (!npcId) return; // Only auto-save existing NPCs

            const formData = $form.serializeArray();
            const data = {
                action: 'npc_auto_save',
                nonce: npcAdmin.nonce
            };

            formData.forEach(item => {
                data[item.name] = item.value;
            });

            $.post(npcAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.showAutoSaveIndicator();
                    }
                });
        }

        showAutoSaveIndicator() {
            let $indicator = $('.auto-save-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<div class="auto-save-indicator">Zapisano automatycznie</div>');
                $('.npc-form').prepend($indicator);
            }

            $indicator.fadeIn(200).delay(2000).fadeOut(200);
        }

        isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        showNotice(message, type = 'success') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Odrzuć to powiadomienie.</span>
                    </button>
                </div>
            `);

            $('.wrap .wp-heading-inline').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.slideUp(300, function () {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function () {
                $notice.slideUp(300, function () {
                    $(this).remove();
                });
            });
        }

        // Funkcja pokazująca subtelne powiadomienie o aktualizacji kolejności
        showOrderUpdateNotice(message) {
            let $notice = $('.order-update-notice');

            if ($notice.length === 0) {
                $notice = $('<div class="order-update-notice" style="position: fixed; bottom: 20px; right: 20px; background-color: rgba(0,124,186,0.8); color: white; padding: 10px 15px; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 9999; opacity: 0;"></div>');
                $('body').append($notice);
            }

            $notice.text(message);
            $notice.animate({ opacity: 1 }, 300).delay(2000).animate({ opacity: 0 }, 300, function () {
                $(this).remove();
            });
        }

        // Funkcja inicjalizująca sortowanie
        initSortable() {
            // Jeśli jQuery UI jest dostępne
            if ($.fn.sortable) {
                console.log('Initializing sortable functionality...');

                // Inicjalizuj sortowanie dla dialogów
                $('.dialogs-container').sortable({
                    items: '.dialog-item',
                    handle: '.dialog-header',
                    placeholder: 'dialog-item-placeholder',
                    opacity: 0.7,
                    update: (event, ui) => {
                        this.updateDialogOrder(event, ui);
                    }
                });

                // Inicjalizuj sortowanie dla odpowiedzi
                $('.dialog-answers ul').sortable({
                    items: 'li',
                    placeholder: 'answer-item-placeholder',
                    opacity: 0.7,
                    update: (event, ui) => {
                        this.updateAnswerOrder(event, ui);
                    }
                });

                // Dodaj wskaźniki wizualne dla elementów sortowanych
                this.enhanceSortableElements();
            } else {
                console.warn('jQuery UI sortable is not available. Drag and drop functionality will not work.');
            }
        }

        // Dodaje wizualne wskaźniki dla elementów sortowanych
        enhanceSortableElements() {
            // Oznacz pierwszy dialog jako początkowy
            $('.dialog-item:first').addClass('first-dialog');

            // Dodaj wskazówki dotyczące przeciągania
        }

        // Aktualizuje kolejność dialogów po przeciągnięciu
        updateDialogOrder(event, ui) {
            const dialogIds = [];

            $('.dialog-item').each(function (index) {
                const dialogId = $(this).data('dialog-id');
                dialogIds.push({
                    id: dialogId,
                    order: index
                });
            });

            // Oznacz pierwszy dialog jako początkowy (wizualnie)
            $('.dialog-item').removeClass('first-dialog');
            $('.dialog-item:first').addClass('first-dialog');

            // Wysyłamy dane AJAX do zapisania kolejności
            $.ajax({
                url: npcAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'npc_update_dialog_order',
                    dialog_order: JSON.stringify(dialogIds),
                    nonce: npcAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Pokaż subtelne powiadomienie o sukcesie
                        this.showOrderUpdateNotice('Kolejność dialogów zaktualizowana');

                        // Odśwież stronę, aby upewnić się, że wszystkie dialogi są widoczne
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    }
                }
            });
        }

        // Aktualizuje kolejność odpowiedzi po przeciągnięciu
        updateAnswerOrder(event, ui) {
            const $list = $(event.target);
            const dialogId = $list.closest('.dialog-answers').find('.add-answer-btn').data('dialog-id');
            const answerIds = [];

            $list.find('li').each(function (index) {
                const answerId = $(this).find('.edit-answer-btn').data('answer-id');
                answerIds.push({
                    id: answerId,
                    order: index
                });
            });

            // Wysyłamy dane AJAX do zapisania kolejności
            $.ajax({
                url: npcAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'npc_update_answer_order',
                    dialog_id: dialogId,
                    answer_order: JSON.stringify(answerIds),
                    nonce: npcAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showOrderUpdateNotice('Kolejność odpowiedzi zaktualizowana');
                    }
                }
            });
        }

        // Funkcja inicjalizująca taby lokalizacji
        initLocationTabs() {
            console.log('Initializing location tabs functionality...');

            // Sprawdź czy istnieją taby lokalizacji
            if ($('.location-tabs').length === 0) {
                console.log('No location tabs found');
                return;
            }

            // Ustaw aktywny tab na podstawie URL hash lub pierwszy tab
            this.setActiveTabFromUrl();
        }

        // Funkcja przełączania tabów
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
            this.reinitializeSortableForTab(targetLocation);
        }

        // Ustaw aktywny tab na podstawie URL hash
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
            if ($firstTab.length > 0 && !$firstTab.hasClass('nav-tab-active')) {
                $firstTab.trigger('click');
            }
        }

        // Przeinicjalizuj sortowanie dla konkretnego tabu
        reinitializeSortableForTab(location) {
            if ($.fn.sortable) {
                const $container = $(`.dialogs-container[data-location="${location}"]`);

                // Destroy existing sortable if exists
                if ($container.hasClass('ui-sortable')) {
                    $container.sortable('destroy');
                }

                // Reinitialize sortable for this container
                $container.sortable({
                    items: '.dialog-item',
                    handle: '.dialog-header',
                    placeholder: 'dialog-item-placeholder',
                    opacity: 0.7,
                    update: (event, ui) => {
                        this.updateDialogOrder(event, ui);
                    }
                });

                // Also reinitialize answer sorting
                $container.find('.dialog-answers ul').each((index, element) => {
                    const $ul = $(element);
                    if ($ul.hasClass('ui-sortable')) {
                        $ul.sortable('destroy');
                    }

                    $ul.sortable({
                        items: 'li',
                        placeholder: 'answer-item-placeholder',
                        opacity: 0.7,
                        update: (event, ui) => {
                            this.updateAnswerOrder(event, ui);
                        }
                    });
                });
            }
        }

        // Inicjalizacja zakładek formularza NPC
        initFormTabs() {
            // Usprawniona obsługa zakładek
            $(document).on('click', '.npc-tab-link', function (e) {
                e.preventDefault();
                const target = $(this).attr('href');

                // Animowane przejście
                $('.npc-tab-content').removeClass('active');
                $('.npc-tab-link').removeClass('active');

                // Efekt animacji
                setTimeout(() => {
                    $(target).addClass('active');
                    $(this).addClass('active');

                    // Aktualizacja URL dla lepszej nawigacji
                    if (history.pushState) {
                        history.replaceState(null, null, target);
                    }

                    // Animowane przewinięcie do zakładki na urządzeniach mobilnych
                    if (window.innerWidth < 782) {
                        $('html, body').animate({
                            scrollTop: $(target).offset().top - 100
                        }, 300);
                    }
                }, 100);
            });

            // Otwórz zakładkę wskazaną w URL
            if (window.location.hash && $(window.location.hash).length) {
                $('.npc-tab-link[href="' + window.location.hash + '"]').click();
            } else {
                // Domyślnie otwiera pierwszą zakładkę jeśli nie ma hasha
                $('.npc-tab-link').first().click();
            }

            // Dostosowanie wysokości zakładki - używamy funkcji anonimowej
            const adjustTabContentHeight = function () {
                // Pozwala na automatyczne dostosowanie wysokości zawartości zakładek
                const activeContent = $('.npc-tab-content.active');
                if (activeContent.length) {
                    const contentHeight = activeContent.outerHeight();
                    $('.npc-form').css('min-height', contentHeight + 80);
                }
            };

            // Wywołujemy od razu i rejestrujemy na zdarzenie resize
            adjustTabContentHeight();
            $(window).on('resize', adjustTabContentHeight);
        }
    }

    // Enhanced table interactions
    class NPCTableEnhancements {
        constructor() {
            this.initSorting();
            this.initFiltering();
            this.initBulkActions();
        }

        initSorting() {
            $('.npc-list-table th').each(function () {
                const $th = $(this);
                if (!$th.hasClass('no-sort')) {
                    $th.addClass('sortable').css('cursor', 'pointer');
                    $th.on('click', function () {
                        // Implement sorting logic
                        console.log('Sorting by:', $th.text());
                    });
                }
            });
        }

        initFiltering() {
            // Add quick filters
            const $filterBar = $(`
                <div class="npc-filter-bar" style="margin: 15px 0;">
                    <select id="status-filter">
                        <option value="">Wszystkie statusy</option>
                        <option value="active">Aktywne</option>
                        <option value="inactive">Nieaktywne</option>
                    </select>
                    <input type="text" id="location-filter" placeholder="Filtruj po lokalizacji..." style="margin-left: 10px;">
                    <button type="button" class="button" id="clear-filters" style="margin-left: 10px;">Wyczyść filtry</button>
                </div>
            `);

            $('.npc-list-container').prepend($filterBar);

            // Implement filtering logic
            $('#status-filter, #location-filter').on('change input', this.applyFilters.bind(this));
            $('#clear-filters').on('click', this.clearFilters.bind(this));
        }

        initBulkActions() {
            // Add checkboxes and bulk action dropdown
            // Implementation would go here
        }

        applyFilters() {
            const statusFilter = $('#status-filter').val();
            const locationFilter = $('#location-filter').val().toLowerCase();

            $('.npc-list-table tbody tr').each(function () {
                const $row = $(this);
                const location = $row.find('.column-location').text().toLowerCase();

                let showRow = true;

                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }

                if (locationFilter && location.indexOf(locationFilter) === -1) {
                    showRow = false;
                }

                $row.toggle(showRow);
            });
        }

        clearFilters() {
            $('#status-filter').val('');
            $('#location-filter').val('');
            $('.npc-list-table tbody tr').show();
        }
    }

    // Initialize when document is ready
    $(document).ready(function () {
        new NPCAdmin();
        new NPCTableEnhancements();
    });

})(jQuery);
