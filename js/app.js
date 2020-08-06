(($) => {
  const { PLUGIN_SLUG, ADMIN_AJAX_URL } = wp_data
  console.log(PLUGIN_SLUG)
  const $app = $(`#${PLUGIN_SLUG}`)
  const $form = $app.find(`form`)
  const $searchInput = $app.find(`#${PLUGIN_SLUG}-search`)
  const $checkboxes = $app.find('[type=checkbox]')
  const $results = $app.find(`#${PLUGIN_SLUG}-results`)
  const $reset = $app.find(`#${PLUGIN_SLUG}-reset`)
  const $originalResults = $(`.et_pb_blog_grid_wrapper`)

  let results = []

  const reset = () => {
    $results.empty().hide()
    $originalResults.fadeIn()
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
    $results.empty()
    const newResultsHTML = results.map(r => renderResult(r)).join('')
    $originalResults.hide()
    $results.html(newResultsHTML)
  }

  $form.on(`submit`, e => {
    e.preventDefault()
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
        updateResults()
      }
    })
    .fail(err => console.error(`Error fetching products.`))
  })

  $reset.on('click', reset)
})(jQuery)