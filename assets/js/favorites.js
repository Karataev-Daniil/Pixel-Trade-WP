jQuery(document).ready(function($){
    function showFavoritesMessage(message, buttonHtml){
        let container = $('#favorites-message');
        if(container.length === 0){
            $('body').append('<div id="favorites-message" class="favorites-alert"></div>');
            container = $('#favorites-message');
        }
        container.html('<p>'+message+'</p>'+buttonHtml).fadeIn();
        setTimeout(()=>{ container.fadeOut(); }, 5000);
    }

    $(document).on('click', '.add-to-favorites', function(e){
        e.preventDefault();
        let product_id = $(this).data('id');
        $.post(favorites_ajax.ajax_url, {
            action: 'add_to_favorites',
            product_id: product_id,
            nonce: favorites_ajax.nonce,
            lang: favorites_ajax.lang
        }, function(response){
            if(response.success){
                alert(response.data.message);
            } else {
                if(response.data.login_button){
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
            nonce: favorites_ajax.nonce,
            lang: favorites_ajax.lang
        }, function(response){
            if(response.success){
                alert(response.data.message);
                button.closest('.favorite-item').fadeOut();
            } else {
                if(response.data.login_button){
                    showFavoritesMessage(response.data.message, response.data.login_button);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    $(document).on('click', '.toggle-favorite', function(e){
        e.preventDefault();
        let button = $(this);
        let svg = button.find('svg');
        let product_id = button.data('id');
        let isActive = svg.attr('fill') && svg.attr('fill') !== 'none';

        let action = isActive ? 'remove_from_favorites' : 'add_to_favorites';
        $.post(favorites_ajax.ajax_url, {
            action: action,
            product_id: product_id,
            nonce: favorites_ajax.nonce,
            lang: favorites_ajax.lang
        }, function(response){
            if(response.success){
                if(isActive){
                    svg.attr('fill','none')
                       .attr('stroke','var(--gray_-6)')
                       .attr('stroke-width','2')
                       .attr('stroke-linecap','round')
                       .attr('stroke-linejoin','round');
                } else {
                    svg.attr('fill','red').attr('stroke','none');
                }
            } else {
                alert(response.data.message);
            }
        });
    });
});
