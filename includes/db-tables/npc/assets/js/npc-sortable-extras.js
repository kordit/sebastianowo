/**
 * NPC Sortable Extras - dodatkowe funkcje dla sortowania dialogów i odpowiedzi
 */

(function ($) {
    'use strict';

    // Czekaj na załadowanie NPCAdmin
    $(document).ready(function () {
        if (typeof NPCAdmin === 'undefined') {
            console.warn('NPCAdmin is not defined. Sortable extras will not work.');
            return;
        }

        // Dodaj metodę showOrderUpdateNotice do klasy NPCAdmin
        NPCAdmin.prototype.showOrderUpdateNotice = function (message) {
            let $notice = $('.order-update-notice');

            if ($notice.length === 0) {
                $notice = $('<div class="order-update-notice" style="position: fixed; bottom: 20px; right: 20px; background-color: rgba(0,124,186,0.8); color: white; padding: 10px 15px; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 9999; opacity: 0;"></div>');
                $('body').append($notice);
            }

            $notice.text(message);
            $notice.animate({ opacity: 1 }, 300).delay(2000).animate({ opacity: 0 }, 300, function () {
                $(this).remove();
            });
        };

        // Rozszerz metodę updateAnswerOrder w NPCAdmin
        const originalUpdateAnswerOrder = NPCAdmin.prototype.updateAnswerOrder;

        NPCAdmin.prototype.updateAnswerOrder = function (event, ui) {
            // Wywołaj oryginalną metodę
            originalUpdateAnswerOrder.call(this, event, ui);

            // Dodaj własną logikę
            const self = this;
            // Nadpisz callback success
            const ajaxSettings = $.ajaxSettings;
            const originalAjax = $.ajax;

            $.ajax = function (settings) {
                if (settings.url === npcAdmin.ajax_url && settings.data && settings.data.action === 'npc_update_answer_order') {
                    const originalSuccess = settings.success;
                    settings.success = function (response) {
                        if (originalSuccess) originalSuccess(response);
                        if (response.success) {
                            self.showOrderUpdateNotice('Kolejność odpowiedzi zaktualizowana');
                        }
                    };
                }
                return originalAjax(settings);
            };

            setTimeout(function () {
                $.ajax = originalAjax;
            }, 100);
        };

        // Dodaj wskaźniki wizualne dla elementów sortowanych
        function enhanceSortableElements() {
            // Dodaj wskaźnik dla pierwszego dialogu
            $('.dialog-item:first').addClass('first-dialog');

            // Dodaj wskazówkę dotyczącą przeciągania do nagłówków dialogów
            $('.dialog-header').append('<span class="sort-hint" style="font-size: 12px; color: #999; margin-left: 10px; opacity: 0.7;">(przeciągnij aby zmienić kolejność)</span>');

            // Dodaj wskazówkę dotyczącą przeciągania do odpowiedzi
            $('.dialog-answers ul li').append('<span class="sort-hint" style="font-size: 11px; color: #999; margin-left: 5px; opacity: 0.7;">(przeciągnij)</span>');
        }

        // Wywołaj funkcję po załadowaniu strony
        setTimeout(enhanceSortableElements, 500);
    });

})(jQuery);
