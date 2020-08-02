(($) => {
  const { PLUGIN_SLUG, ADMIN_AJAX_URL } = wp_data
  const $app = $(`#${PLUGIN_SLUG}`)
  const $form = $app.find(`form`)
  const $searchInput = $app.find(`#${PLUGIN_SLUG}-search`)
  const $originalResults = $(`.et_pb_blog_grid_wrapper`)

  let results = []

  const updateResults = () => {
    const newResultsHTML = results.map(r => {
      console.log(r)
      return r
    })
    console.log(newResultsHTML)
  }

  $form.on(`submit`, e => {
    e.preventDefault()
    const search = $searchInput.val().trim()
    $.ajax({
      url: ADMIN_AJAX_URL,
      type: `POST`,
      data: { 
        action: `ccrest_woo_filter_actions`,
        do: `search_products`,
        search,
      }
    })
    .then(res => {
      const json = JSON.parse(res)
      if (json && json.status === 200) {
        results = json.results
        updateResults()
      }
    })
    .fail(err => console.error(`Error fetching products.`))
  })
})(jQuery)