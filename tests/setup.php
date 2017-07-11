<?php

require_once ( dirname( __FILE__ ) ) . '/../vendor/autoload_52.php';

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

_Request::$_headers = _Response::$_headers = new HeadersEcho;
