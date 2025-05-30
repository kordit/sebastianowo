/**
 * Form Validator
 * Walidacja formularzy NPC
 */

(function ($) {
    'use strict';

    class FormValidator {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Form validation
            $(document).on('submit', '#dialog-form', this.validateDialogForm.bind(this));
            $(document).on('submit', '#answer-form', this.validateAnswerForm.bind(this));
            $(document).on('submit', '.npc-form', this.validateNPCForm.bind(this));
        }

        validateDialogForm(event) {
            const text = $('#dialog_text').val().trim();

            if (!text) {
                event.preventDefault();
                window.notificationManager?.showNotice('Tekst dialogu jest wymagany.', 'error');
                $('#dialog_text').focus();
                return false;
            }

            return true;
        }

        validateAnswerForm(event) {
            const text = $('#answer_text').val().trim();

            if (!text) {
                event.preventDefault();
                window.notificationManager?.showNotice('Tekst odpowiedzi jest wymagany.', 'error');
                $('#answer_text').focus();
                return false;
            }

            return true;
        }

        validateNPCForm(event) {
            const name = $('#name').val().trim();

            if (!name) {
                event.preventDefault();
                window.notificationManager?.showNotice('Nazwa NPC jest wymagana.', 'error');
                $('#name').focus();
                return false;
            }

            // Walidacja URL obrazka
            const imageUrl = $('#image_url').val().trim();
            if (imageUrl && !this.isValidUrl(imageUrl)) {
                event.preventDefault();
                window.notificationManager?.showNotice('Podaj prawidłowy URL obrazka.', 'error');
                $('#image_url').focus();
                return false;
            }

            return true;
        }

        isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
    }

    // Globalna instancja
    window.FormValidator = FormValidator;
    window.formValidator = new FormValidator();

})(jQuery);
