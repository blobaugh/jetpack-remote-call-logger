<?php

/**
 * Logs all outgoing http calls to WordPress.com
 */

class jrc_Outgoing_Http {

	/**
	 * List of domains that should be monitored for calls
	 *
	 * @var array
	 */
	private $domain_list = array(
		'wordpress.com',
	);

	/**
	 * Start of request in microtime()
	 *
	 * @var float
	 */
	private $timer_start;

	/**
	 * End of request in microtime()
	 *
	 * @var float
	 */
	private $timer_end;

	/**
	 * URL of current request
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Holds a reference to the only running jrc_Ouutgoing_Http object
	 *
	 * @var jrc_Outgoing_Http
	 */
	private static $instance = null;

	private function __construct() {

		/*
		 * If the site admin has elected to disable external calls
		 * there is no reason to continue on with any sort of logging
		 */
		if( defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL ) {
			return;
		}

		// Start timer
		add_filter( 'pre_http_request', array( $this, 'log_request' ), 10, 3 );

		add_filter( 'http_request_args', array( $this, 'log_request' ), 10, 3 );  

		add_filter( 'http_response', array( $this, 'log_request' ), 10, 3 );   
	}	

	/**
	 * Gets access to the object
	 *
	 * @since 0.6
	 * @return jrc_Outgoing_Http
	 */
	public static function get_instance() {
		if( !is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = new jrc_Outgoing_Http();

		return self::$instance;
	}

	/**
	 * Handles logging all requests
	 *
	 * This method begins logging from before the request starts 
	 * through the end to provide an idea of timing.
	 *
	 * Note there are 3 filters that call this method!
	 *
	 * @since 0.6
	 * @param array $data
	 * @param array $request_args
	 * @param string $url
	 * @return array
	 */
	public function log_request( $data = '', $request_args = '', $url = '' ) {
		if( !$this->contains_domain( $url ) ) {
			return $data;
		}
	
		$this->url = $url;

		if( 'pre_http_request' == current_filter() ) {
			// Start the timer
			$this->timer_start = $this->timer_mark();
		} else if( 'http_request_args' == current_filter() ) {
			// We can log additional items here if we want
		} else if( 'http_response' == current_filter() ) {
			// Response ended. Calc the request time and save to db
			$this->timer_end = $this->timer_mark();
			
			$duration = round( $this->timer_end - $this->timer_start, 3 );
			
			$datas = array(
				'post_title'  => time(),
				'post_type'	=> 'jrc_request',
				'post_status' => 'outgoing',
				'post_content' => json_encode( array(
					'type' => $request_args['method'],
					'url' => $url,
					'duration' => $duration,
					'time' 	=> time(),
					'user_agent' => $request_args['user-agent'],
					'body' => $request_args['body']
				) )
			);
			$post_id = wp_insert_post( $datas );
		}
		// Remember this is a filter! Return the data untouched
		return $data;
	}

	private function timer_mark() {
		$mtime = explode(' ', microtime() );
		$mtime = $mtime[1] + $mtime[0];
		return $mtime;
	}

	private function timer_duration() {
		return $this->timer_end - $this->timer_start;
	}

	/**
	 * Checks to see if the given domain is in the list of domains
	 * Jetpack uses
	 *
	 * @since 0.6
	 * @param string $url
	 * @return boolean
	 */
	private function contains_domain( $url ) {
		$contains = false;
		foreach( $this->domain_list AS $d ) {
			if( false != strpos( $url, $d ) ) {
				return true;
			}
		}
		return false;
	}

} // end class
