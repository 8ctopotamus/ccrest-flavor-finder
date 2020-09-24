<?php

function renderModal($catsFilters, $sizeFilters) { ?>
  <div id="<?php echo CCREST_FLAVOR_FINDER_PLUGIN_SLUG; ?>-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">FIND YOUR FLAVOR</h2>
        <span class="close">&times;</span>
      </div>
      <div class="modal-body">
        <form>
          <div class="ccrest-flavor-finder-search-wrap">
            <input id="<?php echo CCREST_FLAVOR_FINDER_PLUGIN_SLUG; ?>-search" type="search" />
            <button type="submit">Search</button>
            <button id="<?php echo CCREST_FLAVOR_FINDER_PLUGIN_SLUG; ?>-reset-filters" type-="button">reset</button>
          </div>
          <h3>Filter by</h3>
          <?php echo $catsFilters; ?>
          <h3>Size</h3>
          <?php echo $sizeFilters; ?>
        </form>
      </div>
    </div> 
  </div>
<?php }

add_shortcode( CCREST_FLAVOR_FINDER_PLUGIN_SLUG, 'ccrest_flavor_finder_shorcode_func' );
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
    $allergensHTML .= '<input class="allergen" id="'.$allergen.'" name="'.$allergen.'" value="'.$allergen.'" type="checkbox" >';
    $allergensHTML .= '<label for="'.$allergen.'">' . $allergen . '</label><br>';
    $allergensHTML .=  '</li>';
  }
  $allergensFilters = '<ul class="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-filterset">' . $allergensHTML . '</ul>';

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
      $catsHTML .= '<input class="cat" id="'.$category->slug.'" name="'.$category->slug.'" value="'.$category->slug.'" type="checkbox">';
      $catsHTML .= '<label for="'.$category->slug.'">' . $category->name . '</label><br>';
      $catsHTML .=  '</li>';
    }
  }
  $catsFilters = '<ul class="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-filterset">' . $catsHTML . '</ul>';


  $sizes = ['three_gallon', 'scround', 'quart', 'pint', 'cup'];
  $sizesHTML = '';
  foreach ($sizes as $size) {
    $sizesHTML .= '<li>';
    $sizesHTML .= '<input class="size" id="'. $size.'" name="'. $size.'" value="'. $size.'" type="checkbox">';
    $sizesHTML .= '<label for="'. $size.'">' .  $size . '</label><br>';
    $sizesHTML .=  '</li>';
  }
  $sizeFilters = '<ul class="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-filterset">' . $sizesHTML . '</ul>';

  add_action( 'wp_footer', function () use ($catsFilters, $sizeFilters) { 
    renderModal($catsFilters, $sizeFilters);
  });

  return '<div id="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'"> 
    <button id="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-modal-trigger">FIND YOUR FLAVOR</button>
    
    <div class="loader">Loading...</div>
    
    <div id="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-results-stats">
      <h4><span class="count"></span> results</h3>
      <button id="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-reset-results">RESET</button>
    </div>
    <div id="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-results"></div>
  </div>';
}