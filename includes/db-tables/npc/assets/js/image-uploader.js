/**
 * Image Uploader
 * Obsługuje upload obrazów dla NPC
 */

(function ($) {
    'use strict';

    class ImageUploader {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Media uploader dla obrazków
            $(document).on('click', '.upload-image-btn', this.openMediaUploader.bind(this));
            $(document).on('click', '.remove-image-btn', this.removeImage.bind(this));
        }

        openMediaUploader(event) {
            event.preventDefault();

            const $button = $(event.target);
            const targetInput = $button.data('target');

            // Sprawdź czy WordPress Media Library jest dostępna
            if (typeof wp !== 'undefined' && wp.media) {
                const mediaUploader = wp.media({
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
                    $(`#${targetInput}`).val(attachment.url);

                    // Pokaż podgląd obrazka
                    this.showImagePreview(attachment.url, targetInput);
                }.bind(this));

                mediaUploader.open();
            } else {
                window.notificationManager?.showNotice('Media Library nie jest dostępna.', 'error');
            }
        }

        removeImage(event) {
            event.preventDefault();

            const $button = $(event.target);
            const targetInput = $button.data('target');

            $(`#${targetInput}`).val('');
            this.hideImagePreview(targetInput);
        }

        showImagePreview(imageUrl, targetInput) {
            let $preview = $(`.image-preview[data-target="${targetInput}"]`);

            if ($preview.length === 0) {
                $preview = $(`
                    <div class="image-preview" data-target="${targetInput}">
                        <img src="" alt="Podgląd" style="max-width: 200px; max-height: 200px; margin-top: 10px;">
                    </div>
                `);
                $(`#${targetInput}`).after($preview);
            }

            $preview.find('img').attr('src', imageUrl);
            $preview.show();
        }

        hideImagePreview(targetInput) {
            $(`.image-preview[data-target="${targetInput}"]`).hide();
        }

        initImagePreview() {
            // Inicjalizacja podglądu dla istniejących obrazków
            $('input[type="url"][id*="image"]').each(function () {
                const $input = $(this);
                const imageUrl = $input.val();

                if (imageUrl) {
                    window.imageUploader.showImagePreview(imageUrl, $input.attr('id'));
                }
            });
        }
    }

    // Globalna instancja
    window.ImageUploader = ImageUploader;
    window.imageUploader = new ImageUploader();

    // Inicjalizacja podglądu po załadowaniu DOM
    $(document).ready(function () {
        window.imageUploader.initImagePreview();
    });

})(jQuery);
