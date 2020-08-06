<?php
/*
 * Search & Filter
 */
function search_products() {

  $cats = !empty($_POST['cats']) ? $_POST['cats'] : false;
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
  
  // if ($cats):
  //   $search_args['tax_query'] = array(
  //     array (
  //       'taxonomy' => 'product_cat',
  //       'terms' => $cats,
  //     )
  //   );
  // endif;
  
  // if ($includeMeta):
  //   $search_args['meta_query'] = array(
  //     'relation'=> 'AND',
  //   );
  //   foreach ($fieldsWeCareAbout as $field):
  //     if ($_POST[$field]):
  //       $compare = $field === 'minimum_tank_size' ? '>=' : '=';
  //       $type = $field === 'minimum_tank_size' ? 'NUMERIC' : 'CHAR';
  //       $search_args['meta_query'][] = [
  //         'key' => $field,
  //         'value' => $_POST[$field],
  //         'compare' => $compare,
  //         'type' => $type
  //       ];
  //     endif;
  //   endforeach;
  // endif;
  
  $query = new WP_Query( $search_args );
  
  if ( $query->have_posts() ):
    $results['total'] = $query->found_posts;
    while ( $query->have_posts() ) : $query->the_post();
      $product   = wc_get_product( get_the_ID() );
      $image_id  = $product->get_image_id();
      $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
      $results['data'][] = [
        'title' => get_the_title(),
        'permalink' => get_the_permalink(),
        'thumbnail' => $image_url,
      ];
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