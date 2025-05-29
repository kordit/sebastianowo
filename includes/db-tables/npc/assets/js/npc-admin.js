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
                        $('#is_starting_dialog').prop('checked', dialog.is_starting_dialog == 1);

                        // Załaduj warunki dialogu
                        if (dialog.conditions) {
                            $('.conditions-manager[data-context="dialog"] .conditions-data').val(dialog.conditions);

                            // Przeładuj interfejs warunków
                            if (window.NPCConditionsManager) {
                                const conditionsManager = new window.NPCConditionsManager();
                                conditionsManager.loadExistingConditions();
                            }
                        }
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

                        // Załaduj warunki odpowiedzi
                        if (answer.conditions) {
                            $('.conditions-manager[data-context="answer"] .conditions-data').val(answer.conditions);

                            // Przeładuj interfejs warunków
                            if (window.NPCConditionsManager) {
                                const conditionsManager = new window.NPCConditionsManager();
                                conditionsManager.loadExistingConditions();
                            }
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

            // Waliduj warunki
            if (window.NPCConditionsManager) {
                const errors = window.NPCConditionsManager.validateAllConditions();
                if (errors.length > 0) {
                    event.preventDefault();
                    this.showNotice('Błędy w warunkach: ' + errors.join(', '), 'error');
                    return false;
                }
            }

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
            } else {
                console.warn('jQuery UI sortable is not available. Drag and drop functionality will not work.');
            }
        }
        
        // Aktualizuje kolejność dialogów po przeciągnięciu
        updateDialogOrder(event, ui) {
            const dialogIds = [];
            
            $('.dialog-item').each(function(index) {
                const dialogId = $(this).data('dialog-id');
                dialogIds.push({
                    id: dialogId,
                    order: index
                });
            });

            // Wysyłamy dane AJAX do zapisania kolejności
            $.ajax({
                url: npcAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'npc_update_dialog_order',
                    dialog_order: JSON.stringify(dialogIds),
                    nonce: npcAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Dialog order updated successfully');
                    }
                }
            });
        }
        
        // Aktualizuje kolejność odpowiedzi po przeciągnięciu
        updateAnswerOrder(event, ui) {
            const $list = $(event.target);
            const dialogId = $list.closest('.dialog-answers').find('.add-answer-btn').data('dialog-id');
            const answerIds = [];
            
            $list.find('li').each(function(index) {
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
                success: function(response) {
                    if (response.success) {
                        console.log('Answer order updated successfully');
                    }
                }
            });
        }
    }

    // Dialog tree visualization (for complex dialog flows)
    class DialogTreeViewer {
        constructor() {
            this.initTreeView();
        }

        initTreeView() {
            // Add tree view toggle button
            if ($('.dialogs-container .dialog-item').length > 2) {
                $('.dialog-actions').append(
                    '<button type="button" class="button" id="toggle-tree-view">Widok drzewa</button>'
                );

                $(document).on('click', '#toggle-tree-view', this.toggleTreeView.bind(this));
            }
        }

        toggleTreeView() {
            const $container = $('.dialogs-container');
            $container.toggleClass('tree-view');

            if ($container.hasClass('tree-view')) {
                this.renderTreeView();
                $('#toggle-tree-view').text('Widok listy');
            } else {
                this.renderListView();
                $('#toggle-tree-view').text('Widok drzewa');
            }
        }

        renderTreeView() {
            // Implementation for tree visualization would go here
            // This could use a library like D3.js or vis.js for advanced visualization
            console.log('Tree view rendering...');
        }

        renderListView() {
            // Return to default list view
            $('.dialogs-container').removeClass('tree-view');
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
                const status = $row.find('.status-badge').hasClass('status-active') ? 'active' : 'inactive';
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
        new DialogTreeViewer();
        new NPCTableEnhancements();
    });

})(jQuery);
