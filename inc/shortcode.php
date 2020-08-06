<?php


function renderModal($allergensFilters, $catsFilters) { ?>
  <div id="<?php echo PLUGIN_SLUG; ?>-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">FIND YOUR FLAVOR</h2>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <form>
          <input id="<?php PLUGIN_SLUG; ?>-search" type="search" />
          <button type="submit">Search</button>
        </form>
        <button id="<?php PLUGIN_SLUG; ?>-reset">reset</button>
        <h6>Filter by</h6>
        <?php echo $catsFilters; ?>
        <h6>Exclude the following allergens</h6>
        <?php echo $allergensFilters; ?>
      </div>
      <div class="modal-footer">
        <h3>Modal Footer</h3>
      </div>
    </div> 
  </div>
<?php }

add_shortcode( PLUGIN_SLUG, 'ccrest_flavor_finder_shorcode_func' );
function ccrest_flavor_finder_shorcode_func( $atts ) {
  global $wpdb;
 
  // build allergen filter options
  $allergensACF = $wpdb->get_col( $wpdb->prepare("
    SELECT pm.meta_value FROM {$wpdb->postmeta} pm
    LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
    WHERE pm.meta_key = %s 
    AND p.post_status = %s 
    AND p.post_type = %s
  ", 'allergens', 'publish', 'product' ) );

  $allergens = [];
  foreach($allergensACF as $a) {
    $words = explode(',', $a);
    foreach($words as $w) {
      if (!in_array($w, $allergens))
        $allergens[] = $w;
    }
  }
  $allergensHTML = '';
  foreach($allergens as $allergen) {
    $allergensHTML .= '<li>';
    $allergensHTML .= '<input type="checkbox" id="'.$allergen.'" name="'.$allergen.'" value="'.$allergen.'">';
    $allergensHTML .= '<label for="'.$allergen.'">' . $allergen . '</label><br>';
    $allergensHTML .=  '</li>';
  }
  $allergensFilters = '<ul class="'.PLUGIN_SLUG.'-filterset">' . $allergensHTML . '</ul>';

  // build category filter options
  $cat_args = array(
    'orderby'    => 'name',
    'order'      => 'asc',
    'hide_empty' => true,
  );
  $product_categories = get_terms( 'product_cat', $cat_args );

  $catsHTML = '';
  if( !empty($product_categories) ) {
    foreach ($product_categories as $key => $category) {
      $catsHTML .= '<li>';
      $catsHTML .= '<input type="checkbox" id="'.$category->slug.'" name="'.$category->slug.'" value="'.$category->slug.'">';
      $catsHTML .= '<label for="'.$category->slug.'">' . $category->name . '</label><br>';
      $catsHTML .=  '</li>';
    }
  }
  $catsFilters = '<ul class="'.PLUGIN_SLUG.'-filterset">' . $catsHTML . '</ul>';

  add_action( 'wp_footer', function () use ($allergensFilters, $catsFilters) { 
    renderModal($allergensFilters, $catsFilters);
  });

  return '<div id="'.PLUGIN_SLUG.'"> 
    <button id="'.PLUGIN_SLUG.'-modal-trigger">FIND YOUR FLAVOR</button>
    <div id="'.PLUGIN_SLUG.'-results"></div>
  </div>';
}