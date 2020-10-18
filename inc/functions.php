<?php

/* 
 * Template Helper Functions
 */
function ccff_find_size($target) {
  $sizes_repeater = get_field('sizes');
  $data = false;
  if ($sizes_repeater) {
    foreach($sizes_repeater as $acfFields) {
      $match = $acfFields['size'] === $target;
      if ($match) {
        $data = $acfFields;
        break;
      }
    }
  }
  return $data;
}


/*
 * Search & Filter API endpoint
 */
function search_products() {
  $cats = !empty($_POST['cats']) ? $_POST['cats'] : false;
  $sizes = !empty($_POST['sizes']) ? $_POST['sizes'] : false;

  $results = [
    'data' => [],
    'total' => 0,
    'status' => 200,
  ];

  $search_args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    's' => $_POST['s'],
    'product_cat' => $cats,
  );
    
  $query = new WP_Query( $search_args );
  
  if ( $query->have_posts() ):
    $results['total'] = $query->found_posts;
    while ( $query->have_posts() ) : $query->the_post();
      
      // check sizes
      if($sizes && have_rows('sizes') ):
        $sizeFound = false;
        while ( have_rows('sizes') ) : the_row();
          $acfSize = get_sub_field('size');
          if ($acfSize && in_array($acfSize, $sizes)) {
            $results['debug'][] = $acfSize . ' FOUND';
            $sizeFound = true;
            break;
          }
        endwhile;
        if (!$sizeFound) 
          continue;
      endif;

      // create product result
      $product   = wc_get_product( get_the_ID() );
      $image_id  = $product->get_image_id();
      $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
      $image_url = $image_url 
        ? $image_url 
        : plugin_dir_url(__DIR__) . 'img/placeholder.jpg';
      $prod = [
        'title' => get_the_title(),
        'permalink' => get_the_permalink(),
        'thumbnail' => $image_url,
      ];
      $results['data'][] = $prod;
      wp_reset_postdata();
    endwhile;
  endif;

  http_response_code (200); 
  echo json_encode($results);
  exit();
}