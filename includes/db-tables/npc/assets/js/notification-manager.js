/**
 * Notification Manager
 * System powiadomień dla NPC Admin
 */

(function ($) {
    'use strict';

    class NotificationManager {
        constructor() {
            // Klasa gotowa do użycia
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

        showAutoSaveIndicator() {
            let $indicator = $('.auto-save-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<div class="auto-save-indicator">Zapisano automatycznie</div>');
                $('.npc-form').prepend($indicator);
            }

            $indicator.fadeIn(200).delay(2000).fadeOut(200);
        }
    }

    // Globalna instancja
    window.NotificationManager = NotificationManager;
    window.notificationManager = new NotificationManager();

})(jQuery);
