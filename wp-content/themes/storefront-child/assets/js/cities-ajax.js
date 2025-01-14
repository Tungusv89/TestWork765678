jQuery(document).ready(function ($) {
  function loadCities(search = '') {
    $.ajax({
      url: ajaxData.ajax_url,
      method: 'POST',
      data: {
        action: 'get_cities',
        search: search,
      },
      success: function (response) {
        $('#cities-table-container').html(response)
      },
    })
  }

  // Загрузка таблицы при загрузке страницы
  loadCities()

  // Обработка кнопки поиска
  $('#search-button').on('click', function () {
    const searchValue = $('#search-city').val()
    loadCities(searchValue)
  })

  // Обработка ввода в поле поиска
  $('#search-city').on('keyup', function (e) {
    if (e.key === 'Enter') {
      const searchValue = $(this).val()
      loadCities(searchValue)
    }
  })
})
