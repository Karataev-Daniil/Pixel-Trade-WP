jQuery(document).ready(function($){
    $(document).on('click', '.add-to-favorites', function(e){
        e.preventDefault();
        let product_id = $(this).data('id');

        $.post(favorites_ajax.ajax_url, {
            action: 'add_to_favorites',
            product_id: product_id,
            nonce: favorites_ajax.nonce
        }, function(response){
            if(response.success){
                alert(response.data.message);
            } else {
                if(response.data.login_button){
                    // Если не авторизован → выводим сообщение с кнопкой
                    showFavoritesMessage(response.data.message, response.data.login_button);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    $(document).on('click', '.remove-from-favorites', function(e){
        e.preventDefault();
        let button = $(this);
        let product_id = button.data('id');

        $.post(favorites_ajax.ajax_url, {
            action: 'remove_from_favorites',
            product_id: product_id,
            nonce: favorites_ajax.nonce
        }, function(response){
            if(response.success){
                alert(response.data.message);
                button.closest('.product-card').fadeOut();
            } else {
                if(response.data.login_button){
                    showFavoritesMessage(response.data.message, response.data.login_button);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Функция показа сообщения с кнопкой
    function showFavoritesMessage(message, buttonHtml){
        let container = $('#favorites-message');
        if(container.length === 0){
            $('body').append('<div id="favorites-message" class="favorites-alert"></div>');
            container = $('#favorites-message');
        }
        container.html('<p>'+message+'</p>'+buttonHtml).fadeIn();

        setTimeout(function(){
            container.fadeOut();
        }, 5000);
    }
});
