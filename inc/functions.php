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
  $allergens = !empty($_POST['allergens']) ? $_POST['allergens'] : false;
  // $postsPerPage = $_POST['postsPerPage'] ? intval($_POST['postsPerPage']) : 12;
  // $paged = $_POST['paged'] ? intval($_POST['paged']) : 0;
  // $includeMeta = $_POST['includeMeta'] === 'true' ? boolval($_POST['includeMeta']) : false;
  // $debug = $_POST['debug'] === 'true' ? boolval($_POST['debug']) : false;

  $results = [
    'data' => [],
    'total' => 0,
    'status' => 200,
  ];

  $search_args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    // 'posts_per_page' => $postsPerPage,
    // 'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    's' => $_POST['s'],
    'product_cat' => $cats,
  );
    
  $query = new WP_Query( $search_args );
  
  if ( $query->have_posts() ):
    $results['total'] = $query->found_posts;
    while ( $query->have_posts() ) : $query->the_post();
      // if product includes allergen, skip it
      $allergensString = strtolower ( get_field('allergens', get_the_id()) );
      if ($allergens && count($allergens) > 0) {
        $found = false;
        foreach ($allergens as $al) {
          if (strpos($allergensString, strtolower($al)) !== false) {
            $found = true;
          }
        }
        if ($found) continue;
      }
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
  $count = 0;
  while( !feof($file) ) {
    $row = fgetcsv($file);
    // set headers
    if ($count === 0) {
      $headers = $row;
      $count++;
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
      $key = $headers[$colCount];
      if (strpos($key, 'category--') !== false) {
        if ( strtolower( $col ) === 'x' ) {
          $products[$title]['cats'][] = str_replace('category--', '', $key);
        }
      } else {
        $products[$title]['sizes'][$size][$key] = $col;
      }
      $colCount++;
    }
    $count++;
  }
  fclose($file);

  // use the formatted data
  foreach($products as $product) {
    // create new product post
    $newPostId = wp_insert_post( $product['args'] );

    update_post_meta($newPostId, '_price', 3.99);

    // add cats to post
    $catSlugs = [];
    if ($product['cats']) {
      foreach ($product['cats'] as $cat) {
        $catSlugs[] = strtolower( preg_replace('/\s+/', '_', trim( $cat )) );
      }
    }
    if (count($catSlugs) > 0) {
      wp_set_object_terms($newPostId, $catSlugs, 'product_cat', true);
    }
    
    // populate ACF Repeater fields
    if ($product['sizes']) {
      foreach($product['sizes'] as $size => $fields) {
        add_row('sizes', $fields, $newPostId);
      }
    }
  }

  echo json_encode(['success' => true, 'debug' => $debug, 'products' => $products]);
  exit();
}