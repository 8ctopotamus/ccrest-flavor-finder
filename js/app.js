(($) => {
  const { PLUGIN_SLUG, ADMIN_AJAX_URL } = wp_data
  const $originalResults = $(`.et_pb_blog_grid_wrapper`)
  const $app = $(`#${PLUGIN_SLUG}`)
  const $loading = $app.find(`.loader`)
  const $results = $app.find(`#${PLUGIN_SLUG}-results`)
  const $resultsStats = $app.find(`#${PLUGIN_SLUG}-results-stats`)
  const $count = $resultsStats.find('.count')
  const $resetResults = $app.find(`#${PLUGIN_SLUG}-reset-results`)
  const $modalTrigger = $app.find(`#${PLUGIN_SLUG}-modal-trigger`)
  const $modal = $(`#${PLUGIN_SLUG}-modal`)
  const $resetFilters = $modal.find(`#${PLUGIN_SLUG}-reset-filters`)
  const $close = $modal.find(`.close`)
  const $submit = $modal.find(`#submit`)
  const $searchInput = $modal.find(`#${PLUGIN_SLUG}-search`)
  const $checkboxes = $modal.find('[type=checkbox]')

  let results = []

  const setLoading = bool => bool ? $loading.show() : $loading.hide()
  
  const toggleResultStatsDisplay = bool => bool ? $resultsStats.addClass('open') : $resultsStats.removeClass('open')

  const closeModal = () => $modal.slideUp()
  
  const showModal = () => $modal.show()
  
  const resetResults = () => {
    toggleResultStatsDisplay(false)
    $results.empty().hide()
    $originalResults.fadeIn()
  }

  const resetFilters = () => {
    $searchInput.val('')
    $checkboxes.each(function() {
      $(this).attr('checked', false)
    })
  }

  const renderResult = data => {
    const { title, permalink, thumbnail } = data
    return (
      `<article class="et_pb_post">
        <div class="et_pb_image_container">
          <a href="${permalink}" class="entry-featured-image-url">
            <img src="${thumbnail}" alt="${title}">
          </a>
        </div>
        <h2 class="entry-title">
          <a href="${permalink}">${title}</a>
        </h2>		
      </article>`
    )
  }

  const updateResults = () => {
    const newResultsHTML = results.map(r => renderResult(r)).join('')
    $results.html(newResultsHTML)
  }

  $submit.on(`click`, e => {
    e.preventDefault()
    setLoading(true)
    $results.empty()
    $originalResults.hide()
    closeModal()
    const s = $searchInput.val().trim()
    const cats = $checkboxes
      .filter(function() { return $(this).attr('checked') })
      .map(function() { return $(this).val() })
      .get()
      .join(',')
    $.ajax({
      url: ADMIN_AJAX_URL,
      type: `POST`,
      data: { 
        action: `ccrest_woo_filter_actions`,
        do: `search_products`,
        s,
        cats,
      }
    })
    .then(res => {
      const json = JSON.parse(res)
      if (json && json.status === 200) {
        results = json.data
        toggleResultStatsDisplay(true)
        $count.text(results.length)
        updateResults()
      }
      setLoading(false)
    })
    .fail(err => {
      setLoading(false)
      console.error(`Error fetching products.`)
    })
  })

  $resetResults.on('click', resetResults)
  $resetFilters.on('click', resetFilters)
  $modalTrigger.on('click', showModal)
  $close.on('click', closeModal)
  window.onclick = function(event) {
    if (event.target == $modal.get(0)) {
      $modal.fadeOut()
    }
  } 

})(jQuery)