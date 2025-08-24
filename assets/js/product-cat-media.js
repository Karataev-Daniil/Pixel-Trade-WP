jQuery(document).ready(function($){
    var mediaUploader;

    $('#category_image_upload_button').click(function(e){
        e.preventDefault();

        if (mediaUploader) { mediaUploader.open(); return; }

        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Выберите изображение категории',
            button: { text: 'Выбрать' },
            multiple: false
        });

        mediaUploader.on('select', function(){
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#category_image').val(attachment.id);
            $('#category-image-wrapper').html('<img src="'+attachment.sizes.thumbnail.url+'">');
        });

        mediaUploader.open();
    });

    $('#category_image_remove_button').click(function(e){
        e.preventDefault();
        $('#category_image').val('');
        $('#category-image-wrapper').html('');
    });
});
