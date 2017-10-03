<?php
if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get() {
		return false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set() {
		return true;
	}
}
