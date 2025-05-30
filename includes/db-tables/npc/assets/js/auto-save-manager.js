/**
 * Auto Save Manager
 * Automatyczne zapisywanie formularzy
 */

(function ($) {
    'use strict';

    class AutoSaveManager {
        constructor() {
            this.autoSaveInterval = null;
            this.init();
        }

        init() {
            this.setupAutoSave();
        }

        setupAutoSave() {
            // Sprawdź czy auto-save jest włączony (można wyłączyć dla testowania)
            const autoSaveEnabled = false; // Ustaw na false aby wyłączyć auto-save

            if (!autoSaveEnabled) {
                return;
            }

            // Auto-save dla formularzy NPC co 30 sekund
            this.autoSaveInterval = setInterval(() => {
                this.performAutoSave();
            }, 30000);

            // Zatrzymaj auto-save przed opuszczeniem strony
            $(window).on('beforeunload', () => {
                if (this.autoSaveInterval) {
                    clearInterval(this.autoSaveInterval);
                }
            });
        }

        performAutoSave() {
            const $form = $('.npc-form');

            if ($form.length === 0) {
                return;
            }

            // Sprawdź czy to jest formularz edycji (ma npc_id)
            const npcId = $form.find('input[name="npc_id"]').val();
            if (!npcId || npcId === '') {
                return;
            }

            // Sprawdź czy formularz został zmodyfikowany
            if (!this.isFormModified($form)) {
                return;
            }

            // Sprawdź czy mamy dostęp do npcAdmin
            if (typeof npcAdmin === 'undefined' || !npcAdmin.nonce || !npcAdmin.ajax_url) {
                return;
            }

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
                        window.notificationManager?.showAutoSaveIndicator();
                    } else {
                        console.warn('Auto-save: Serwer zwrócił błąd:', response.data);
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Auto-save: Błąd podczas auto-save:', status, error);
                    console.error('Auto-save: Response text:', xhr.responseText);
                    console.error('Auto-save: Status code:', xhr.status);
                });
        }

        isFormModified($form) {
            // Sprawdź czy formularz został zmodyfikowany od ostatniego zapisu
            // Prosta implementacja - można rozbudować o bardziej zaawansowaną logikę
            const currentData = $form.serialize();
            const lastSavedData = $form.data('last-saved-data') || '';

            if (currentData !== lastSavedData) {
                $form.data('last-saved-data', currentData);
                return true;
            }

            return false;
        }

        saveManually() {
            this.performAutoSave();
        }
    }

    // Globalna instancja
    window.AutoSaveManager = AutoSaveManager;
    window.autoSaveManager = new AutoSaveManager();

})(jQuery);
