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

function jrc_admin_page() {
	require_once( 'class.jrc-request-list-table.php' );
	$lt = new jrc_Request_List_Table();
	echo '<div class="wrap"><h2>' . __( 'Remote Calls' ) . '</h2>';
	echo '<form method="post">';
	$lt->prepare_items();
	$lt->display();
	echo '</form></div>';
}
