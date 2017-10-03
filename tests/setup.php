<?php

require_once ( dirname( __FILE__ ) ) . '/../vendor/autoload_52.php';
require_once ( dirname( __FILE__ ) ) . '/test-closures.php';
require_once ( dirname( __FILE__ ) ) . '/mock-wp-functions.php';

klein_load();
klein_wp_load();

class HeadersEcho extends _Headers {

	protected $silent = false;

	public function header( $key, $value = null ) {
		if ( ! $this->silent ) {
			echo $this->_header( $key, $value ) . "\n";
		}
	}

	public function silent( $silent ) {
		$this->silent = $silent;
	}
}

_Request::$_headers      = _Response::$_headers = new HeadersEcho;
klein_Request::$_headers = klein_Response::$_headers = new HeadersEcho;
