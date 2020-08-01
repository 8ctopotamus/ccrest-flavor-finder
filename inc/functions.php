<?php
/*
 * Search & Filter
 */


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
  $csv = plugin_dir_url( __DIR__ ) . 'data/products.csv';
  $file = fopen($csv,"r");
  $count = 0;
  $headers = [];
  while( !feof($file) ) {
    $row = fgetcsv($file);
    // set headers
    if ($count === 0) {
      $headers = $row;
      $count++;
      continue; // skip to body of data
    }
    // format data for new plow post
    $args = [
      'post_title' => '',
      'post_type' => 'product',
      'post_status' => 'publish',
    ];
    $acfData = [];
    $cats = [];
    $colCount = 0;
    foreach($row as $col) {
      $key = $headers[$colCount];
      if ($key === 'flavor') {
       $args['post_title'] = $col;
      } else if (strpos($key, 'category--') !== false) {
        if ( $col === 'x' ) {
          $cats[] = str_replace('category--', '', $key);
        }
      } else {
        $acfData[$key] = $col;
      }
      $colCount++;
    }
    // create new plow post
    $newPostId = wp_insert_post( $args );
    // populate ACF fields
    foreach($acfData as $key => $val) {
      update_field($key, $val, $newPostId);
    }
    // add cats to post
    $catSlugs = [];
    foreach ($cats as $cat) {
      $catSlugs[] = strtolower(preg_replace('/\s+/', '_', $cat));
    }
    wp_set_object_terms($newPostId, $catSlugs, 'product_cat', true);
    $count++;
  }
  fclose($file);
  echo json_encode(['success' => true]);
  exit();
}