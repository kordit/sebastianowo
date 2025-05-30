/**
 * Sortable Manager
 * Obsługuje funkcjonalność przeciągania i sortowania
 */

(function ($) {
    'use strict';

    class SortableManager {
        constructor() {
            this.init();
        }

        init() {
            this.initSortable();
        }

        initSortable() {
            // Jeśli jQuery UI jest dostępne
            if ($.fn.sortable) {

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

        enhanceSortableElements() {
            // Oznacz pierwszy dialog jako początkowy
            $('.dialog-item:first').addClass('first-dialog');
        }

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
                        window.notificationManager?.showOrderUpdateNotice('Kolejność dialogów zaktualizowana');

                        // Odśwież stronę, aby upewnić się, że wszystkie dialogi są widoczne
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    } else {
                        window.notificationManager?.showNotice('Błąd podczas aktualizacji kolejności dialogów.', 'error');
                    }
                },
                error: () => {
                    window.notificationManager?.showNotice('Błąd połączenia z serwerem.', 'error');
                }
            });
        }

        updateAnswerOrder(event, ui) {
            const answerIds = [];
            const $answersList = ui.item.closest('ul');

            $answersList.find('li').each(function (index) {
                const answerId = $(this).data('answer-id');
                if (answerId) {
                    answerIds.push({
                        id: answerId,
                        order: index
                    });
                }
            });

            // Wysyłamy dane AJAX do zapisania kolejności
            $.ajax({
                url: npcAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'npc_update_answer_order',
                    answer_order: JSON.stringify(answerIds),
                    nonce: npcAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Pokaż subtelne powiadomienie o sukcesie
                        window.notificationManager?.showOrderUpdateNotice('Kolejność odpowiedzi zaktualizowana');
                    } else {
                        window.notificationManager?.showNotice('Błąd podczas aktualizacji kolejności odpowiedzi.', 'error');
                    }
                },
                error: () => {
                    window.notificationManager?.showNotice('Błąd połączenia z serwerem.', 'error');
                }
            });
        }

        reinitializeSortableForTab(location) {
            // Reinicjalizuj sortowanie dla konkretnej lokalizacji
            const $tabContent = $(`#tab-${location}`);

            $tabContent.find('.dialogs-container').sortable('refresh');
            $tabContent.find('.dialog-answers ul').sortable('refresh');
        }
    }

    // Globalna instancja
    window.SortableManager = SortableManager;
    window.sortableManager = new SortableManager();

})(jQuery);
