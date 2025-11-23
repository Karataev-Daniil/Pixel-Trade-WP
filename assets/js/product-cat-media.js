jQuery(document).ready(function ($) {
    $(document).on('click', '.category_image_upload_button', function (e) {
        e.preventDefault();

        const target = $(this).data('target');
        const inputField = $('#' + target);
        const wrapper = $('#' + target + '-wrapper');

        const mediaUploader = wp.media({
            title: 'Выберите изображение',
            button: { text: 'Использовать' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            inputField.val(attachment.id);
            wrapper.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:100px;">');
        });

        mediaUploader.open();
    });

    $(document).on('click', '.category_image_remove_button', function (e) {
        e.preventDefault();

        const target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '-wrapper').html('');
    });
});
