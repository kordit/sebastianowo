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

            // Sprawdź czy formularz został zmodyfikowany
            if (!this.isFormModified($form)) {
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
                    }
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
