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


/*
 * CSV Import
 */  
function delete_all_ccrest_products(){
  global $wpdb;
  $post_type = 'product';
  $result = $wpdb->query( 
    $wpdb->prepare("
        DELETE posts,pt,pm
        FROM wp_posts posts
        LEFT JOIN wp_term_relationships pt ON pt.object_id = posts.ID
        LEFT JOIN wp_postmeta pm ON pm.post_id = posts.ID
        WHERE posts.post_type = %s
      ", 
      $post_type
    )
  );
  return $result !== false;
}
 
function upload_cedarcrest_data() {
  delete_all_ccrest_products();
  $csv = plugin_dir_url( __DIR__ ) . 'data/products_latest.csv';
  $file = fopen($csv,"r");
  $debug = [];
  $products = [];  
  $headers = [];

  // prep the data
  $rowCount = 0;
  while( !feof($file) ) {
    $row = fgetcsv($file);
    // set headers
    if ($rowCount === 0) {
      $headers = $row;
      $rowCount++;
      continue; // skip to body of data
    }
    
    $title = trim($row[0]);
    $size = $row[1];

    $products[$title]['args'] = [
      'post_title' => $title,
      'post_type' => 'product',
      'post_status' => 'publish',
    ];

    $colCount = 0;
    foreach($row as $col) {
      if (isset($col)) {
        if (isset($headers[$colCount])) {
          $key = $headers[$colCount];
          if (strpos($key, 'category--') !== false) {
            if ( strtolower( $col ) === 'x' ) {
              $products[$title]['cats'][] = str_replace('category--', '', $key);
            }
          } else if (strpos($key, 'allergy_icons--') !== false) {
            if ( strtolower( $col ) === 'x' ) {
              $products[$title]['sizes'][$size]['allergy_icons'][] = str_replace('allergy_icons--', '', $key);
            }
          } else {
            $products[$title]['sizes'][$size][$key] = $col;
          }
        }
      }
      $colCount++;
    }

    $rowCount++;
  }
  fclose($file);

  // use the formatted data
  foreach($products as $product) {
    // create new product post
    $newPostId = wp_insert_post( $product['args'] );

    update_post_meta($newPostId, '_price', 4.00);
    update_post_meta($newPostId, '_regular_price', 4.00);

    // add cats to post
    $catSlugs = [];
    if (isset($product['cats'])) {
      foreach ($product['cats'] as $cat) {
        $catSlugs[] = strtolower( preg_replace('/\s+/', '_', trim( $cat )) );
      }
    }
    if (count($catSlugs) > 0) {
      wp_set_object_terms($newPostId, $catSlugs, 'product_cat', true);
    }
    
    // populate ACF Repeater fields 
    $order = ['three_gallon', 'scround', 'quart', 'pint', 'cup'];
    foreach($order as $o) {
      if (isset($product['sizes']) && isset($product['sizes'][$o])) {
        $size = $product['sizes'][$o];
        add_row('sizes', $size, $newPostId);
      }
    }
  }

  echo json_encode(['success' => true, 'debug' => $debug, 'products' => $products]);
  exit();
}