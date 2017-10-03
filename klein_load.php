<?php

if ( ! function_exists( 'klein_load' ) ) {
	/**
	 * Includes the PHP 5.2 compatible, non prefixed, version of the klein library.
	 *
	 * @since 1.0.3
	 */
	function klein_load() {
		include_once dirname( __FILE__ ) . '/klein.php';
	}
}

if ( ! function_exists( 'klein_wp_load' ) ) {
	/**
	 * Includes the PHP 5.2 compatible and prefixed version of the klein library.
	 *
	 * @since 1.0.3
	 */
	function klein_wp_load() {
		include_once dirname( __FILE__ ) . '/wp_klein.php';
	}
}
