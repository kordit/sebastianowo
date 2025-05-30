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
            $('.npc-modal').fadeOut(200);
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
                        $('#dialog_order').val(dialog.dialog_order);

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

                        // Usunięto ładowanie warunków odpowiedzi
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
                return false;
            }

            // Additional validation
            const imageUrl = $('#image_url').val().trim();
            if (imageUrl && !this.isValidUrl(imageUrl)) {
                event.preventDefault();
                this.showNotice('Podaj prawidłowy URL obrazka.', 'error');
                $('#image_url').focus();
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
