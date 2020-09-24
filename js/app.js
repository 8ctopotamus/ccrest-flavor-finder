(($) => {
  const { CCREST_FLAVOR_FINDER_PLUGIN_SLUG, ADMIN_AJAX_URL } = wp_data
  const $originalResults = $(`.et_pb_blog_grid_wrapper`)
  const $app = $(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}`)
  const $loading = $app.find(`.loader`)
  const $results = $app.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-results`)
  const $resultsStats = $app.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-results-stats`)
  const $count = $resultsStats.find('.count')
  const $resetResults = $app.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-reset-results`)
  const $modalTrigger = $app.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-modal-trigger`)
  const $modal = $(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-modal`)
  const $resetFilters = $modal.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-reset-filters`)
  const $close = $modal.find(`.close`)
  const $searchForm = $modal.find(`form`)
  const $searchInput = $modal.find(`#${CCREST_FLAVOR_FINDER_PLUGIN_SLUG}-search`)
  const $checkboxes = $modal.find('[type=checkbox]')
  const $catCheckboxes = $modal.find('[type=checkbox].cat')
  const $sizeCheckboxes = $modal.find('[type=checkbox].size')

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

  const resetFilters = e => {
    e.preventDefault()
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

  $searchForm.on(`submit`, e => {
    e.preventDefault()
    setLoading(true)
    $results.empty()
    $originalResults.hide()
    closeModal()
    const s = $searchInput.val().trim()
    const cats = $catCheckboxes
      .filter(function() { return $(this).attr('checked') })
      .map(function() { return $(this).val().trim() })
      .get()
      .join(',')
    const sizes = $sizeCheckboxes
      .filter(function() { return $(this).attr('checked') })
      .map(function() { return $(this).val().trim() })
      .get()
    $.ajax({
      url: ADMIN_AJAX_URL,
      type: `POST`,
      data: { 
        action: `ccrest_woo_filter_actions`,
        do: `search_products`,
        s,
        cats,
        sizes,
      }
    })
    .then(res => {
      const json = JSON.parse(res)
      console.log(json)
      if (json && json.status === 200) {
        results = json.data
        toggleResultStatsDisplay(true)
        $results.show()
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