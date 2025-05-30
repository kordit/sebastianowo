/**
 * Modal Manager
 * Zarządza otwieraniem/zamykaniem modali dla NPC
 */

(function ($) {
    'use strict';

    class ModalManager {
        constructor() {
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
                    $('#dialog_id').val(dialogId);
                }
            } else {
                $('#dialog_id').val('');
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

            // Reset actions manager if available
            if (window.answerActionsManager) {
                window.answerActionsManager.loadActions([]);
            } else {
                console.warn('answerActionsManager not available');
            }

            if (mode === 'edit') {
                const $button = $(event.target);
                const answerId = $button.data('answer-id');

                if (answerId) {
                    this.loadAnswerData(answerId);
                    $('#answer_id').val(answerId);
                    $('#answer-action').val('update_answer');
                }
            } else {
                $('#answer_id').val('');
                $('#answer-action').val('create_answer');

                // Set dialog ID for new answers
                const dialogId = $(event.target).closest('.dialog-item').data('dialog-id');
                if (dialogId) {
                    $('#answer-dialog-id').val(dialogId);
                }
            }

            // Show modal
            $modal.show();
            $('body').addClass('modal-open');
        }

        closeModal() {
            $('.npc-modal').hide();
            $('body').removeClass('modal-open');
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
                        $('#dialog_text').val(dialog.text);
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
    }

    // Globalna instancja
    window.ModalManager = ModalManager;
    window.modalManager = new ModalManager();

})(jQuery);
