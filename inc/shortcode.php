<?php

add_shortcode( PLUGIN_SLUG, 'ccrest_woo_filter_shorcode_func' );
function ccrest_woo_filter_shorcode_func( $atts ) {
  return '<div id="'.PLUGIN_SLUG.'"> 
    <form>
      <input id="'.PLUGIN_SLUG.'-search" type="search" />
      <button type="submit">Search</button>
    </form>

    <h6>Filter by</h6>
    <ul>
      <li>Cats</li>
      <li>Containers</li>
      <li>Allergens</li>
    </ul>
  </div>';
}