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
          <div class="ccrest-flavor-finder-filters-wrap">
          <?php 
            echo $catsFilters; 
            echo $sizeFilters; 
          ?>
          </div>
        </form>
      </div>
    </div> 
  </div>
<?php }

add_shortcode( CCREST_FLAVOR_FINDER_PLUGIN_SLUG, 'ccrest_flavor_finder_shorcode_func' );
function ccrest_flavor_finder_shorcode_func( $atts ) {
  global $wpdb;

  // categories
  $cat_args = array(
    'orderby'    => 'name',
    'order'      => 'asc',
    'hide_empty' => true,
  );
  $product_categories = get_terms( 'product_cat', $cat_args );

  $catsHTML = '';
  if( !empty($product_categories) ) {
    foreach ($product_categories as $key => $category) {
      $catsHTML .= '<div>';
        $catsHTML .= '<label class="ccff-checkbox-container" for="'.$category->slug.'">';
          $catsHTML .= $category->name;
          $catsHTML .= '<input class="cat" id="'.$category->slug.'" name="'.$category->slug.'" value="'.$category->slug.'" type="checkbox">';
          $catsHTML .= '<span class="checkmark"></span>';
        $catsHTML .= '</label>';
      $catsHTML .=  '</div>';
    }
  }
  $catsFilters = '<fieldset class="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-fieldset grid"><legend>CATEGORY</legend>' . $catsHTML . '</fieldset>';

  // sizes 
  $sizesHTML = '';
  $sizeACFField = get_field_object('field_5f67d4a05bc96');
  if ($sizeACFField && isset($sizeACFField['choices'])) {
    foreach ($sizeACFField['choices'] as $size => $label) {
      $sizesHTML .= '<div>';
        $sizesHTML .= '<label class="ccff-checkbox-container" for="'. $size.'">';
          $sizesHTML .= $size;
          $sizesHTML .= '<input class="size" id="'. $size.'" name="'. $size.'" value="'. $size.'" type="checkbox">';
          $sizesHTML .= '<span class="checkmark"></span>';
        $sizesHTML .= '</label>';
      $sizesHTML .=  '</div>';
    }
    $sizeFilters = '<fieldset class="'.CCREST_FLAVOR_FINDER_PLUGIN_SLUG.'-fieldset">
      <legend>CONTAINER</legend>
      ' . $sizesHTML . '
    </fieldset>';
  }

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