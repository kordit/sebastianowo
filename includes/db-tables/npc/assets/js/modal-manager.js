/**
 * Modal Manager
 * Zarządza otwieraniem/zamykaniem modali dla NPC
 */

(function ($) {
    'use strict';

    class ModalManager {
        constructor() {
            this.filterEnabled = true; // Stan filtrowania - domyślnie włączone
            this.init();
        }

        init() {
            this.bindEvents();
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

            // Toggle button dla filtrowania dialogów
            $(document).on('click', '#toggle-dialog-filter', this.toggleDialogFilter.bind(this));
        }

        openDialogModal(mode, event) {
            event.preventDefault();

            const $modal = $('#dialog-modal');
            const $form = $('#dialog-form');

            // Reset form
            $form[0].reset();

            if (mode === 'edit') {
                const $button = $(event.target);
                const dialogId = $button.data('dialog-id');

                if (dialogId) {
                    this.loadDialogData(dialogId);
                    $('#dialog-id').val(dialogId);
                    $('#dialog-action').val('update_dialog');
                    $('#modal-title').text('Edytuj dialog');
                }
            } else {
                $('#dialog-id').val('');
                $('#dialog-action').val('create_dialog');
                $('#modal-title').text('Dodaj nowy dialog');

                // Przedustaw lokalizację z aktywnej zakładki
                const $activeTab = $('.tab-pane.active');
                if ($activeTab.length) {
                    const currentLocation = $activeTab.data('location');
                    if (currentLocation && currentLocation !== '__none__') {
                        $('#dialog_location').val(currentLocation);
                    }
                }
            }

            // Show modal
            $modal.show();
            $('body').addClass('modal-open');
        }

        openAnswerModal(mode, event) {
            event.preventDefault();

            const $modal = $('#answer-modal');
            const $form = $('#answer-form');

            // Reset form
            $form[0].reset();

            // Reset actions manager tylko dla nowych odpowiedzi
            if (window.answerActionsManager) {
                if (mode === 'create') {
                    console.log('Creating new answer - resetting actions');
                    window.answerActionsManager.loadActions([]);
                } else {
                    console.log('Editing existing answer - keeping current actions until data loads');
                }
            } else {
                console.warn('answerActionsManager not available');
            }

            // Pobierz lokalizację aktualnego dialogu
            const $dialogItem = $(event.target).closest('.dialog-item');
            const dialogId = $dialogItem.data('dialog-id');
            let currentLocation = null;

            if ($dialogItem.length) {
                // Pobierz lokalizację z aktywnej zakładki
                const $activeTab = $('.tab-pane.active');
                if ($activeTab.length) {
                    currentLocation = $activeTab.data('location');
                }
            }

            if (mode === 'edit') {
                const $button = $(event.target);
                const answerId = $button.data('answer-id');

                if (answerId) {
                    // Najpierw filtruj dialogi, potem załaduj dane odpowiedzi
                    this.filterNextDialogOptions(currentLocation);
                    this.loadAnswerData(answerId);
                    $('#answer_id').val(answerId);
                    $('#answer-action').val('update_answer');
                }
            } else {
                $('#answer_id').val('');
                $('#answer-action').val('create_answer');

                // Set dialog ID for new answers
                if (dialogId) {
                    $('#answer-dialog-id').val(dialogId);
                }

                // Filtruj dialogi dla nowych odpowiedzi
                this.filterNextDialogOptions(currentLocation);
            }

            // Show modal
            $modal.show();
            $('body').addClass('modal-open');
        }

        closeModal() {
            $('.npc-modal').hide();
            $('body').removeClass('modal-open');

            // Usuń informacje o filtracji
            $('.dialog-filter-info').remove();
        }

        closeModalOnOverlay(event) {
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
                        $('#dialog_location').val(dialog.location || '');
                        $('#dialog_order').val(dialog.dialog_order);
                    } else {
                        window.notificationManager?.showNotice('Błąd podczas ładowania danych dialogu.', 'error');
                    }
                })
                .fail(() => {
                    window.notificationManager?.showNotice('Błąd połączenia z serwerem.', 'error');
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
                        window.notificationManager?.showNotice('Błąd podczas ładowania danych odpowiedzi.', 'error');
                    }
                })
                .fail(() => {
                    window.notificationManager?.showNotice('Błąd połączenia z serwerem.', 'error');
                });
        }

        /**
         * Filtruje opcje "Następny dialog" według lokalizacji
         */
        filterNextDialogOptions(currentLocation) {
            const $select = $('#answer_next_dialog_id');
            
            // Sprawdź czy element select istnieje
            if (!$select.length) {
                console.warn('ModalManager: Element select #answer_next_dialog_id nie został znaleziony');
                return;
            }
            
            const $options = $select.find('option');
            let visibleCount = 0;
            let totalCount = $options.length - 1; // Minus opcja placeholder

            // Sprawdź czy są jakiekolwiek opcje
            if (totalCount <= 0) {
                console.warn('ModalManager: Brak opcji dialogów do filtrowania');
                $('.dialog-filter-info').remove();
                const $info = $('<div class="dialog-filter-info" style="margin-top: 5px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; color: #856404;">⚠️ Brak dostępnych dialogów. Utwórz nowy dialog, aby móc go wybrać.</div>');
                $select.after($info);
                return;
            }

            // Pokaż wszystkie opcje najpierw
            $options.each(function () {
                $(this).css('display', '');
            });

            // Jeśli filtrowanie jest wyłączone, pokaż wszystkie dialogi
            if (!this.filterEnabled) {
                $('.dialog-filter-info').remove();
                if (totalCount > 0) {
                    const infoText = `🌍 Wszystkie dialogi (${totalCount})`;
                    const $info = $('<div class="dialog-filter-info" style="margin-top: 5px; padding: 5px 8px; background: #f0f8d0; border-left: 3px solid #46b450; font-size: 12px; color: #0d5a0d;">' + infoText + '</div>');
                    $select.after($info);
                }
                return;
            }

            // Normalizuj currentLocation - traktuj puste wartości jako '__none__'
            if (!currentLocation || currentLocation === '' || currentLocation === null || currentLocation === undefined) {
                currentLocation = '__none__';
            }

            if (currentLocation === '__none__') {
                // Jeśli dialog jest bez lokalizacji, pokaż wszystkie dialogi bez lokalizacji
                $options.each(function () {
                    const $option = $(this);
                    if ($option.val() !== '') { // Pomijaj opcję placeholder
                        const optionLocation = $option.data('location');
                        if (optionLocation && optionLocation !== '__none__' && optionLocation !== '') {
                            $option.css('display', 'none');
                        } else {
                            visibleCount++;
                        }
                    }
                });
            } else {
                // Jeśli dialog ma lokalizację, pokaż tylko dialogi z tej samej lokalizacji
                $options.each(function () {
                    const $option = $(this);
                    if ($option.val() !== '') { // Pomijaj opcję placeholder
                        const optionLocation = $option.data('location');
                        if (optionLocation !== currentLocation) {
                            $option.css('display', 'none');
                        } else {
                            visibleCount++;
                        }
                    }
                });
            }

            // Zaktualizuj informację o filtracji
            this.updateFilterInfo(visibleCount, totalCount, currentLocation);
        }

        /**
         * Aktualizuje informację o filtracji dialogów
         */
        updateFilterInfo(visibleCount, totalCount, currentLocation) {
            // Usuń poprzednią informację
            $('.dialog-filter-info').remove();

            if (visibleCount === 0 && totalCount > 0) {
                // Brak dialogów w tej lokalizacji
                const locationName = currentLocation === '__none__' ? 'bez lokalizacji' : currentLocation;
                const infoText = `⚠️ Brak dialogów w lokalizacji "${locationName}". Przełącz się na inną zakładkę lub utwórz nowy dialog.`;

                const $info = $('<div class="dialog-filter-info" style="margin-top: 5px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; color: #856404;">' + infoText + '</div>');
                $('#answer_next_dialog_id').after($info);
            } else if (visibleCount < totalCount) {
                // Część dialogów została ukryta
                const locationName = currentLocation === '__none__' ? 'bez lokalizacji' : currentLocation;
                const infoText = `📍 Filtrowanie: ${visibleCount} z ${totalCount} dialogów (lokalizacja: ${locationName})`;

                const $info = $('<div class="dialog-filter-info" style="margin-top: 5px; padding: 5px 8px; background: #e7f3ff; border-left: 3px solid #0073aa; font-size: 12px; color: #0073aa;">' + infoText + '</div>');
                $('#answer_next_dialog_id').after($info);
            }
        }

        /**
         * Przełącza stan filtrowania dialogów
         */
        toggleDialogFilter() {
            try {
                this.filterEnabled = !this.filterEnabled;
                const $button = $('#toggle-dialog-filter');
                const $select = $('#answer_next_dialog_id');

                // Sprawdź czy elementy istnieją
                if (!$button.length || !$select.length) {
                    console.warn('ModalManager: Nie można znaleźć elementów do przełączania filtrowania');
                    return;
                }

                if (this.filterEnabled) {
                    // Włącz filtrowanie
                    $button.attr('title', 'Pokaż wszystkie dialogi').text('🔍');

                    // Ponownie zastosuj filtrowanie
                    const $activeTab = $('.tab-pane.active');
                    let currentLocation = null;
                    if ($activeTab.length) {
                        currentLocation = $activeTab.data('location');
                    }
                    this.filterNextDialogOptions(currentLocation);
                } else {
                    // Wyłącz filtrowanie - pokaż wszystkie dialogi
                    $button.attr('title', 'Filtruj dialogi według lokalizacji').text('🌍');

                    const $options = $select.find('option');

                    // Pokaż wszystkie opcje
                    $options.each(function () {
                        $(this).css('display', '');
                    });

                    // Aktualizuj informację
                    $('.dialog-filter-info').remove();
                    const totalCount = $options.length - 1; // Minus opcja placeholder
                    if (totalCount > 0) {
                        const infoText = `🌍 Wszystkie dialogi (${totalCount})`;
                        const $info = $('<div class="dialog-filter-info" style="margin-top: 5px; padding: 5px 8px; background: #f0f8d0; border-left: 3px solid #46b450; font-size: 12px; color: #0d5a0d;">' + infoText + '</div>');
                        $select.after($info);
                    }
                }
            } catch (error) {
                console.error('ModalManager: Błąd podczas przełączania filtrowania dialogów:', error);
                window.notificationManager?.showNotice('Wystąpił błąd podczas przełączania filtrowania dialogów.', 'error');
            }
        }
    }

    // Globalna instancja
    window.ModalManager = ModalManager;
    window.modalManager = new ModalManager();

})(jQuery);
