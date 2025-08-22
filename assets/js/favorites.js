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
                alert(response.data.message);
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
                alert(response.data.message);
            }
        });
    });
});
