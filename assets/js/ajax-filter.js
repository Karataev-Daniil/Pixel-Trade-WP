jQuery(document).ready(function ($) {
  function fetchProducts() {
    $.ajax({
      url: ajaxFilterData.ajaxurl,
      type: 'GET',
      data: $('#filter-form').serialize() + '&action=filter_products',
      success: function (response) {
        $('#product-results').html(response);
      },
      error: function () {
        $('#product-results').html('<p>Ошибка при загрузке товаров.</p>');
      }
    });
  }

  fetchProducts();

  $('#filter-form').on('submit', function (e) {
    e.preventDefault();
    fetchProducts();
  });
});
