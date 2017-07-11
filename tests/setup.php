<?php

require_once(dirname(__FILE__)). '/../vendor/autoload_52.php';

class HeadersEcho extends _Headers {
	public function header($key, $value = null) {
		echo $this->_header($key, $value) . "\n";
	}
}

_Request::$_headers = _Response::$_headers = new HeadersEcho;
