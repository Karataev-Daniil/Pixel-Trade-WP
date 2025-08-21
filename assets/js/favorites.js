jQuery(document).ready(function($){

    // Добавить в избранное
    $(document).on('click', '.add-to-favorites', function(e){
        e.preventDefault();
        let product_id = $(this).data('id');

        $.post(favorites_ajax.url, {
            action: 'add_to_favorites',
            product_id: product_id
        }, function(response){
            alert(response.data.message);
        });
    });

    // Удалить из избранного
    $(document).on('click', '.remove-from-favorites', function(e){
        e.preventDefault();
        let product_id = $(this).data('id');

        $.post(favorites_ajax.url, {
            action: 'remove_from_favorites',
            product_id: product_id
        }, function(response){
            alert(response.data.message);
            location.reload(); // обновляем страницу избранного
        });
    });

});
