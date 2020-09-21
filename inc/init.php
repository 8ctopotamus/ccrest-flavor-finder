<?php

// routes
add_action( 'admin_post_nopriv_ccrest_importer', 'ccrest_woo_filter_actions' );
add_action( 'admin_post_ccrest_importer', 'ccrest_woo_filter_actions' );
add_action( 'admin_post_nopriv_ccrest_woo_filter_actions', 'ccrest_woo_filter_actions' );
add_action( 'admin_post_ccrest_woo_filter_actions', 'ccrest_woo_filter_actions' );
function ccrest_woo_filter_actions() {
  $do = $_POST['do'];
  if ( empty( $do ) ) {
    echo '[cCrest Media Repo] No action specified. :(';
    http_response_code(400);
    wp_die();
  }
  $do();
  wp_die();
}

add_action('init', 'ccrest_woo_filter_init');
function ccrest_woo_filter_init() {
  /* Make sure that ACF is installed and activated */
  if( !class_exists('acf') || !function_exists( 'the_field') ) {
    add_action( 'admin_notices', function() {
      ?>
        <div class="update-nag notice">
          <p><?php _e( '<strong>[cCrest Woo Filter]:</strong> Please install the <a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields PRO</a>. It is required for this plugin to work properly.', CCREST_FLAVOR_FINDER_PLUGIN_SLUG); ?></p>
        </div>
      <?php
    } );
  }
}

// Admin Toolbar buttons
add_action('admin_bar_menu', 'add_ccrest_item', 100);
function add_ccrest_item( $admin_bar ){
  global $pagenow;
	$admin_bar->add_menu( array( 'id' => 'ccrest-import', 'title' => 'Import Cedarcrest Data', 'href' => '#' ) );
}

// Admin Toolbar buttons events
add_action( 'admin_footer', 'ccrest_custom_toolbar_actions' );
function ccrest_custom_toolbar_actions() { ?>
  <script type="text/javascript">
		jQuery("li#wp-admin-bar-ccrest-import .ab-item").on('click', function() {
			const confirmation = confirm('You are about to overwrite all CedarCrest Woocommerce data.');
			$btn = jQuery(this);
      $btn.text('Importing... please be patient 😁')
			jQuery.post(
				'<?php echo esc_url( admin_url('admin-post.php') ); ?>',
				{ 
					action: 'ccrest_woo_filter_actions',
					do: 'upload_cedarcrest_data',
				},
				function(response) {
          console.log(response)

          const res = JSON.parse(response)
					if (res && res.success === true) {
            console.log(res)
            $btn.text('Cedarcrest data imported 👍')
					} else {
            $btn.text('Import Cedarcrest Data')
						console.error(res)
						alert('Plow import failed :(')
					}
				},
			);
		});
  </script>
<?php }

function ccrest_woo_filter_enqueue_scripts_styles() {
  // check if shorcode is used
  global $post, $wpdb;
	$shortcode_found = false;
	if (has_shortcode($post->post_content, 'ccrest-flavor-finder') ) {
		 $shortcode_found = true;
	} else if ( isset($post->ID) ) { // checks post meta
		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT count(*) FROM $wpdb->postmeta " .
			"WHERE post_id = %d and meta_value LIKE '%%ccrest-flavor-finder%%'", $post->ID ) );
		$shortcode_found = ! empty( $result );
  }
  
  wp_register_style( 'animate_css', '//cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css' );
  wp_register_style( CCREST_FLAVOR_FINDER_PLUGIN_SLUG, plugin_dir_url(__DIR__) .'css/style.css' );
  wp_register_script( CCREST_FLAVOR_FINDER_PLUGIN_SLUG, plugin_dir_url(__DIR__) .'js/app.js', array('jquery'), false, true );

  if ( $shortcode_found ) {
    wp_enqueue_style('animate_css');
    wp_enqueue_style(CCREST_FLAVOR_FINDER_PLUGIN_SLUG);
    wp_localize_script( CCREST_FLAVOR_FINDER_PLUGIN_SLUG, 'wp_data', [
      'ADMIN_AJAX_URL' => esc_url( admin_url('admin-post.php')),
      'CCREST_FLAVOR_FINDER_PLUGIN_SLUG' => CCREST_FLAVOR_FINDER_PLUGIN_SLUG,
    ] );
    wp_enqueue_script(CCREST_FLAVOR_FINDER_PLUGIN_SLUG);
  }
}
add_action('wp_enqueue_scripts', 'ccrest_woo_filter_enqueue_scripts_styles');