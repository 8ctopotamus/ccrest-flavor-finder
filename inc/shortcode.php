<?php

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

  return '<div id="'.PLUGIN_SLUG.'"> 
    <form>
      <input id="'.PLUGIN_SLUG.'-search" type="search" />
      <button type="submit">Search</button>
    </form>
    <button id="'.PLUGIN_SLUG.'-reset">reset</button>
    <h6>Filter by</h6>
    '.$catsFilters.'
    <h6>Excludes following allergens</h6>
    '.$allergensFilters.'
    <div id="'.PLUGIN_SLUG.'-results"></div>
  </div>';
}