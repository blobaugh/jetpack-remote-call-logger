<?php
/*
Plugin Name: Jetpack Remote Call Logger
Plugin URI: http://github.com/blobaugh/jetpack-remote-call-logger
Description: Creates a log of all remote calls via the xmlrpc for Jetpack. Built only with single site in mind.
Author: Ben Lobaugh
Version: 0.6
Author URI: http://ben.lobaugh.net
*/

require_once( 'class.jrc-outgoing-http.php' );
require_once( 'class.jrc-incoming-xmlrpc.php' );

add_action( 'init', function() {
	register_post_type( 'jrc_request' );
	jrc_Outgoing_Http::get_instance();
	jrc_Incoming_Xmlrpc::get_instance();
});
/*
 * Setup the subpage that will list all the calls.
 *
 * This subpage exists under the Jetpack menu
 */
add_action( 'jetpack_admin_menu', function( $hook ) {
	$hook = add_submenu_page( 'jetpack', __( 'Remote Calls' ), __( 'Remote Calls' ), 'jetpack_manage_modules', 'jetpack_remote_calls', 'jrc_admin_page' );
});

add_action( 'plugins_loaded', 'jrc_maybe_export' );

function jrc_admin_page() {

	jrc_maybe_empty_logs();

	require_once( 'class.jrc-request-list-table.php' );
	$lt = new jrc_Request_List_Table();
	echo '<div class="wrap"><h2>' . __( 'Remote Calls' ) . '</h2>';
	echo '<form method="post">';
	echo '<p>';
	submit_button( __( 'Export to CSV' ), 'secondary', 'jrc_export', false );
	echo '&nbsp;';
	submit_button( __( 'Empty log' ), 'secondary', 'jrc_empty_log', false );
	echo '</p>';
	$lt->prepare_items();
	$lt->display();
	echo '</form></div>';
}

function jrc_maybe_export() {
	if( !isset( $_POST['jrc_export'] ) ) {
		return;
	}
	
	header('Content-Type: application/csv');
	header('Content-Disposition: inline; filename="jrc_export.csv"');
	header("Pragma: no-cache");
	header("Expires: 0");

	// Get data
	$args = array(
		'post_type' => 'jrc_request',
		'post_status' => array( 'incoming', 'outgoing' ),
		'posts_per_page' => -1
	);
	$requests = get_posts( $args ); 

	$data = array();
	foreach( $requests AS $p ) {
		$item = json_decode( $p->post_content );
		$out = fopen( "php://output", 'w' );
		$data = array(
			ucfirst( $p->post_status ),
			$item->type,
			$item->url,
			date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $item->time),
			$item->duration,
		);
		fputcsv( $out, $data );
	}
	fclose( $out );
	exit();
}


function jrc_maybe_empty_logs() {
	if( !isset( $_POST['jrc_empty_log'] ) ) {
		return;
	}

	// Get data
	$args = array(
		'post_type' => 'jrc_request',
		'post_status' => array( 'incoming', 'outgoing' ),
		'posts_per_page' => -1
	);
	$requests = get_posts( $args ); 

	foreach( $requests AS $p ) {
		$post = wp_delete_post( $p->ID, true );
		error_log( print_r( $post, true ) );
		error_log( "Deleting: " . $p->ID );
	}
}
