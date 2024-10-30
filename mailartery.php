<?php
/**
 * Plugin Name: Mailartery
 * Author:      mailartery
 * Author URI:  https://profiles.wordpress.org/mailartery/
 * Version:     0.1.0
 * Description: WordPress newsletter plugin. Use this: [mailartery_newsletter] shortcode to display the subscription form.
 * Text Domain: mailartery
 */

add_action( 'admin_post_mailartery_form_response', 'mailartery_form_response' );

if( !function_exists( 'mailartery_form_response' ) ) {
	function mailartery_form_response() {
		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['mailartery_nonce'] ) && wp_verify_nonce( $post_data['mailartery_nonce'], 'mailartery_action' ) ) {
				if(isset($_POST['submit'])) {
					if( $post_data["mailartery_newsletters"] != "" ) {
						global $wpdb;
						$table_name = $wpdb->prefix . 'mailartery_newsletters';
						$mailartery_newsletters = sanitize_email( $post_data[ "mailartery_newsletters" ] );
						if( !preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^",$mailartery_newsletters ) ) {
							echo '<script>alert("Invalid email");</script>';
						} else {
							$user_id = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE email = '".$mailartery_newsletters."' ");
							if( empty( $user_id ) ) {
								$success = $wpdb->insert(
									$table_name,
									array(
										'email' => $mailartery_newsletters,
										'status' => 'active',
										'created_date' => date('Y-m-d h:i:s')
									)
								);
								if( $success ) {
									$message = __( "Thanks for subscribing!", "mailartery" );
								} else{
									$message = __( "Email not subscribe", "mailartery" );
								}
							} else {
								$message = __( "Your email already exist", "mailartery" );
							}
						}
					} else {
						$message = __( "Please enter an email address", "mailartery" );
					}
				}
		}  else {
			$message = __( 'nonce verification failed',  'mailartery' );
		}

		;
		wp_redirect( $post_data['_wp_http_referer'] .'?mailartery_message='.$message );
		exit();
	}
}


if ( ! function_exists( 'mailartery_subs_form' ) ) {
	function mailartery_subs_form(){
		?>
    	<div class="newsletter-email-main">
    		<?php
				if( isset( $_GET['mailartery_message'] ) ) {
					$message  = sanitize_text_field(  wp_unslash( $_GET['mailartery_message'] ) );
					echo '<div id="message"><p><strong>' . esc_html($message) . '</strong></p></div>';
				}
			?>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="newsletter" method="post" name="newsletter" id="newsletter">
				<input type="hidden" name="action" value="mailartery_form_response">
				<?php wp_nonce_field( 'mailartery_action', 'mailartery_nonce' ); ?>
                <input type="email" id="mailartery_newsletters" name="mailartery_newsletters" class="newsletter_email_field" placeholder="Enter email address" />
                <input type="submit" name="submit" class="newsletter-submit-form"  value="<?php esc_attr_e('subscribe','mailartery'); ?>">
            </form>
            <span id="email_error"></span>
        </div>
        <div id="ajaxloader"></div>
<?php
	}
}
// Newsletter Form shortcode: [mailartery_newsletter]

add_shortcode( 'mailartery_newsletter', 'mailartery_subs_form_shortcode' );

if ( ! function_exists( 'mailartery_subs_form_shortcode' ) ) {
	function mailartery_subs_form_shortcode() {
    	ob_start();
    	mailartery_subs_form();
    	return ob_get_clean();
	}
}

// Create Newsletter Menu Dashboard Sidepanel Start
function mailartery_dashboard_menu(){
    add_menu_page('Mailartery Newsletters', 'Mailartery', 'manage_options', 'mailartery', 'mailartery_listing_page', 'dashicons-email-alt2' );
}
add_action( 'admin_menu', 'mailartery_dashboard_menu' );
// Create Newsletter Menu Dashboard Sidepanel End



//Download Csv File Function Start
add_action( "admin_init", "mailartery_download_csv" );

if ( !function_exists( 'mailartery_download_csv' ) ) {
	function mailartery_download_csv() {
		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['mailartery_nonce'] ) && wp_verify_nonce( $post_data['mailartery_nonce'], 'mailartery_action' ) ) {
			if( isset( $post_data['download_csv'] ) ) {
				global $wpdb;
				$delimiter = ",";
				$filename = "mailartery_newsletters" . date('Y-m-d') . ".csv";
				$f = fopen('php://output', 'w');
				$fields = array('ID', 'Email', 'Created Date');
				fputcsv($f, $fields, $delimiter);
				$query = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."mailartery_newsletters");
				$rowNumber=0;
				foreach($query as $val){
					$rowNumber++;
					$Email = $val->email;
					$Date = $val->created_date;
					$lineData = array($rowNumber, $Email, $Date);
					fputcsv($f, $lineData, $delimiter);
				}
				fseek($f, 0);
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="' . $filename . '";');
				fpassthru($f);
				exit;
			}
		}
	}
}
//Download Csv File Function End


// Create Newsletter Email List Function Start

if( !function_exists('mailartery_listing_page')) {
	function mailartery_listing_page(){?>
	    <div class="email-listing">
	    	<form class="exportcsv" method="post" name="exportcsv" id="exportcsv" action="">
	    		<input type="hidden" name="action" value="mailartery_csv_response">
				<?php wp_nonce_field( 'mailartery_action', 'mailartery_nonce' ); ?>
	            <input type="submit" name="download_csv" class="btn-export"  value="<?php echo esc_html__('export to csv','mailartery');?>">
	        </form>
			<?php
			global $wpdb;
	    	$result = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."mailartery_newsletters");?>
	    	<table id="myTable" class="table" width="100%" border="1" rules="all">
	            <thead>
	              <tr>
	                <th> <strong> <?php  esc_html_e('ID','mailartery'); ?> </strong> </th>
	                <th> <strong> <?php  esc_html_e('EMAIL ADRRESS','mailartery'); ?> </strong> </th>
	                <th> <strong> <?php  esc_html_e('STATUS','mailartery'); ?> </strong> </th>
	                <th> <strong> <?php  esc_html_e('CREATED DATE','mailartery'); ?> </strong> </td>
	              </tr>
	            </thead>
	            <tbody>
	                <?php foreach( $result as $row ) { ?>
	                	<tr>
							<td> <?php esc_html_e( $row->id, 'mailartery' ); ?> </td>
							<td> <?php esc_html_e( $row->email, 'mailartery' ); ?> </td>
							<td> <?php esc_html_e( $row->status, 'mailartery' ); ?> </td>
							<td> <?php esc_html_e( $row->created_date, 'mailartery'); ?> </td>
	                    </tr>
	                <?php } ?>
	            </tbody>
	        </table>
	    </div>
		<?php
	}
}
// Create Newsletter Email List Function End

// Admin Panel Css Add Function Start Here
add_action( 'admin_enqueue_scripts', 'mailartery_listing_admin_script' );

if( !function_exists( 'mailartery_listing_admin_script' ) ) {
	function mailartery_listing_admin_script( $hook ) {

		if ( 'toplevel_page_mailartery' == $hook ) {
			wp_enqueue_style( 'mailartery-admin-css', plugin_dir_url( __FILE__ ) . 'css/email-list.css',false , '1.0', 'all' );
  			wp_enqueue_style( 'mailartery-datatable-css', plugin_dir_url( __FILE__ ) . 'css/jquery.dataTables.min.css',false , '1.0', 'all' );

	  		wp_register_script( 'mailartery-datatable-min-js', plugin_dir_url( __FILE__ ) . 'js/jquery.dataTables.min.js', [ 'jquery' ], '1.0', true );
	  		wp_register_script( 'mailartery-custom-min-js', plugin_dir_url( __FILE__ ) . 'js/mailartery.js', [ 'jquery' ], '1.0', true );

	  		wp_enqueue_script( 'mailartery-datatable-min-js' );
	  		wp_enqueue_script( 'mailartery-custom-min-js' );
	  	}
	}
}
// Admin Panel Css Add Function End Here

add_action( 'wp_enqueue_scripts', 'mailartery_frontend_script' );

if( !function_exists( 'mailartery_frontend_script' ) ) {
	function mailartery_frontend_script() {
		wp_enqueue_style( 'mailartery-style-css', plugin_dir_url( __FILE__ ) . 'css/style.css', false , '1.0', 'all' );
	}
}

add_action( 'init', 'mailartery_load_textdomain' );

/**
 * Load plugin textdomain.
 */
if( !function_exists('mailartery_load_textdomain')) {
	function mailartery_load_textdomain() {
  		load_plugin_textdomain( 'mailartery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}


// Create Database Table Function Start Here

if( !function_exists( 'mailartery_newsletter_table' ) ) {
	function mailartery_newsletter_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mailartery_newsletters';
		$sql = "CREATE TABLE $table_name (
			id int(10) NOT NULL AUTO_INCREMENT,
			email varchar(100) NOT NULL,
			status varchar(100) NOT NULL DEFAULT 'active',
			created_date datetime NOT NULL,
			PRIMARY KEY  (id)
		);";
	 	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	 	dbDelta( $sql );
	}
}

register_activation_hook( __FILE__, 'mailartery_newsletter_table' );
// Create Database Table Function End Here