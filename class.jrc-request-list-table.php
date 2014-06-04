<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class jrc_Request_List_Table extends WP_List_Table {

	
	public function get_columns() {
		// site name, status, username connected under
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'post_status' => __( 'Direction' ),
			'type' => __( 'Type' ),
			'url' => __( 'URL' ),
			'time' => __( 'When' ),
		);

		return $columns;
	}

	public function prepare_items() {
		// Deal with bulk actions if any were requested by the user
		$this->process_bulk_action();

		// Get data
		$args = array(
			'post_type' => 'jrc_request',
			'post_status' => array( 'incoming', 'outgoing' ),
			'posts_per_page' => -1
		);
		$requests = get_posts( $args ); 

		
		// Setup pagination
		$per_page = 40;
		$current_page = $this->get_pagenum();
		$total_items = count( $requests );
		$requests = array_slice( $requests, ( ( $current_page-1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $requests;
	}

	public function column_default( $item  ){
		var_dump( $item );
	}

	public function column_post_status( $item ) {
		return ucfirst( $item->post_status );
	}

	public function column_type( $item ) {
		$item = json_decode( $item->post_content );
		return $item->type;
	}

	public function column_url( $item ) {
		$item = json_decode( $item->post_content );
		return $item->url;
	}

	public function column_time( $item ){
		$item = json_decode( $item->post_content );
		$item = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $item->time);
		return $item;
	}

	public function column_duration( $item ) {
		$item = json_decode( $item->post_content );
		return $item->duration;
	}

	public function get_bulk_actions() {
	    $actions = array(
	    );

	    return $actions;
	}

	function column_cb($item) {
        	return sprintf(
            		'<input type="checkbox" name="bulk[]" value="%s" />', $item->blog_id
        	);    
    	}

	/**
	 * @todo Ensure sites are not in/active before performing action
	 */
	public function process_bulk_action() {
	
	}
} // end h
