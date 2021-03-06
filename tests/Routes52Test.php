<?php

class Test52Class {

	static function GET( $r, $m, $a ) {
		echo 'ok';
	}
}

class Routes52Test extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		_Request::$_headers->silent( true );
		global $__klein_routes;
		$__klein_routes = array();

		global $__klein_namespace;
		$__klein_namespace = null;

		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
	}

	protected function tearDown() {
		_Request::$_headers->silent( false );
	}

	protected function assertOutputSame( $expected, $callback, $message = '' ) {
		ob_start();
		call_user_func( $callback );
		$out = ob_get_contents();
		ob_end_clean();
		$this->assertSame( $expected, $out, $message );
	}

	protected function loadExternalRoutes() {
		$route_directory  = __DIR__ . '/routes/';
		$route_files      = scandir( $route_directory );
		$route_namespaces = array();

		foreach ( $route_files as $file ) {
			if ( is_file( $route_directory . $file ) ) {
				$route_namespace    = '/' . basename( $file, '.php' );
				$route_namespaces[] = $route_namespace;

				inNamespace( $route_namespace, $route_directory . $file );
			}
		}

		return $route_namespaces;
	}

	public function testBasic() {
		$this->expectOutputString( 'x' );
		respond( '/', 'closure1' );
		respond( '/something', 'closure2' );
		dispatch( '/' );
	}

	public function testCallable() {
		$this->expectOutputString( 'okok' );
		respond( '/', array('Test52Class', 'GET' ));
		respond( '/', 'Test52Class::GET' );
		dispatch( '/' );
	}

	public function testAppReference() {
		$this->expectOutputString( 'ab' );
		respond( '/', 'closure3' );
		respond( '/', 'closure4' );
		respond( '/', 'closure5' );
		dispatch( '/' );
	}

	public function testCatchallImplicit() {
		$this->expectOutputString( 'b' );

		respond( '/one', 'closure6' );
		respond( 'closure7' );
		respond( '/two', 'closure8' );
		respond( '/three', 'closure9' );
		dispatch( '/two' );
	}

	public function testCatchallAsterisk() {
		$this->expectOutputString( 'b' );
		respond( '/one', 'closure10' );
		respond( '*', 'closure11' );
		respond( '/two', 'closure12' );
		respond( '/three', 'closure13' );
		dispatch( '/two' );
	}

	public function testCatchallImplicitTriggers404() {
		$this->expectOutputString( "b404\n" );;
		respond( 'closure14' );
		respond( 404, 'closure15' );
		dispatch( '/' );
	}

	public function testRegex() {
		$this->expectOutputString( 'z' );

		respond( '@/bar', 'closure16' );
		dispatch( '/bar' );
	}

	public function testRegexNegate() {
		$this->expectOutputString( "y" );

		respond( '!@/foo', 'closure17' );
		dispatch( '/bar' );
	}

	public function test404() {
		$this->expectOutputString( "404\n" );

		respond( '/', 'closure18' );
		respond( 404, 'closure19' );
		dispatch( '/foo' );
	}

	public function testParamsBasic() {
		$this->expectOutputString( 'blue' );

		respond( '/[:color]', 'closure20' );
		dispatch( '/blue' );
	}

	public function testParamsIntegerSuccess() {
		$this->expectOutputString( "987" );

		respond( '/[i:age]', 'closure21' );
		dispatch( '/987' );
	}

	public function testParamsIntegerFail() {
		$this->expectOutputString( '404 Code' );

		respond( '/[i:age]', 'closure22' );
		respond( '404', 'closure23' );
		dispatch( '/blue' );
	}

	public function testParamsAlphaNum() {
		respond( '/[a:audible]', 'closure24' );

		$this->assertOutputSame( 'blue42', 'closure25' );
		$this->assertOutputSame( '', 'closure26' );
		$this->assertOutputSame( '', 'closure27' );
	}

	public function testParamsHex() {
		respond( '/[h:hexcolor]', 'closure28' );

		$this->assertOutputSame( '00f', 'closure29' );
		$this->assertOutputSame( 'abc123', 'closure30' );
		$this->assertOutputSame( '', 'closure31' );
		$this->assertOutputSame( '', 'closure32' );
		$this->assertOutputSame( '', 'closure33' );
	}

	public function test404TriggersOnce() {
		$this->expectOutputString( '404 Code' );

		respond( '404', 'closure35' );
		dispatch( '/notroute' );
	}

	public function testMethodCatchAll() {
		$this->expectOutputString( 'yup!123' );

		respond( 'POST', null, 'closure36' );
		respond( 'POST', '*', 'closure37' );
		respond( 'POST', '/', 'closure38' );
		respond( 'closure39' );
		dispatch( '/', 'POST' );
	}

	public function testLazyTrailingMatch() {
		$this->expectOutputString( 'this-is-a-title-123' );

		respond( '/posts/[*:title][i:id]', 'closure40' );
		dispatch( '/posts/this-is-a-title-123' );
	}

	public function testFormatMatch() {
		$this->expectOutputString( 'xml' );

		respond( '/output.[xml|json:format]', 'closure41' );
		dispatch( '/output.xml' );
	}

	public function testDotSeparator() {
		$this->expectOutputString( 'matchA:slug=ABCD_E--matchB:slug=ABCD_E--' );

		respond( '/[*:cpath]/[:slug].[:format]', 'closure42' );
		respond( '/[*:cpath]/[:slug].[:format]?', 'closure43' );
		respond( '/[*:cpath]/[a:slug].[:format]?', 'closure44' );
		dispatch( "/category1/categoryX/ABCD_E.php" );
		$this->assertOutputSame(
			'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
			'closure45'
		);
		$this->assertOutputSame(
			'matchB:slug=ABCD_E--',
			'closure46'
		);
	}

	public function testControllerActionStyleRouteMatch() {
		$this->expectOutputString( 'donkey-kick' );

		respond( '/[:controller]?/[:action]?', 'closure47' );
		dispatch( '/donkey/kick' );
	}

	public function testRespondArgumentOrder() {
		$this->expectOutputString( 'abcdef' );

		respond( 'closure48' );
		respond( null, 'closure49' );
		respond( '/endpoint', 'closure50' );
		respond( 'GET', null, 'closure51' );
		respond( array( 'GET', 'POST' ), null, 'closure52' );
		respond( array( 'GET', 'POST' ), '/endpoint', 'closure53' );
		dispatch( '/endpoint' );
	}

	public function testTrailingMatch() {
		respond( '/?[*:trailing]/dog/?', 'closure54' );

		$this->assertOutputSame( 'yup', 'closure55' );
		$this->assertOutputSame( 'yup', 'closure56' );
		$this->assertOutputSame( 'yup', 'closure57' );
		$this->assertOutputSame( 'yup', 'closure58' );
		$this->assertOutputSame( 'yup', 'closure59' );
		$this->assertOutputSame( 'yup', 'closure60' );
	}

	public function testTrailingPossessiveMatch() {
		respond( '/sub-dir/[**:trailing]', 'closure61' );

		$this->assertOutputSame( 'yup', 'closure62' );
		$this->assertOutputSame( 'yup', 'closure63' );
		$this->assertOutputSame( 'yup', 'closure64' );
		$this->assertOutputSame( 'yup', 'closure65' );
	}

	public function testNSDispatch() {
		inNamespace( '/u', 'closure80' );
		respond( 404, 'closure68' );

		$this->assertOutputSame( "slash", 'closure69' );
		$this->assertOutputSame( "slash", 'closure70' );
		$this->assertOutputSame( "id", 'closure71' );
		$this->assertOutputSame( "404", 'closure72' );
	}
}
